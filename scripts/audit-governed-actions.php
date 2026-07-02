#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * audit-governed-actions.php — Starisian Technologies audit script.
 *
 * Rule: governed mutation entry points must call the platform authority-layer gate
 *       before mutating state.
 * Ref:  docs/standards-handbook.md §1.2 (AUTH-001).
 *       CI-Enforcement-Matrix.md AUTH-001.
 *       standards/phpstan-rules/GovernedActionGateRule.php (static analysis equivalent).
 *
 * This script detects entry points that are annotated @governed-mutation but do
 * not contain a call to the required gate function, as a fast grep-level check
 * before PHPStan runs.
 *
 * Detection scope:
 *   - Functions/methods with @governed-mutation in their docblock.
 *   - Top-level functions whose names match WP AJAX/admin-post patterns
 *     (wp_ajax_*, wp_ajax_nopriv_*, admin_post_*).
 *
 * The PHPStan GovernedActionGateRule provides AST-level enforcement.
 * This script provides a file-level fast check for CI.
 *
 * Exit codes:
 *   0 — no violations found
 *   1 — violations found (or script error)
 *
 * Usage:
 *   php scripts/audit-governed-actions.php [--path=src] [--warn-only] [--gate-function=assert_governed_action]
 */

$options      = getopt('', ['path:', 'warn-only', 'exclude:', 'gate-function:']);
$scanPath     = $options['path'] ?? 'src';
$warnOnly     = array_key_exists('warn-only', $options);
$exclude      = $options['exclude'] ?? '';
$gateFunction = $options['gate-function'] ?? 'assert_governed_action';

$excludeDirs = array_filter(array_map('trim', explode(',', $exclude)));
$excludeDirs = array_merge($excludeDirs, ['vendor', 'node_modules', 'build', 'dist']);

if ( ! is_dir($scanPath) && ! is_file($scanPath) ) {
    fwrite(STDERR, "audit-governed-actions: path not found: {$scanPath}\n");
    exit(1);
}

/**
 * @param string       $dir
 * @param list<string> $excludeDirs
 * @return list<string>
 */
function collect_php_files_governed(string $dir, array $excludeDirs): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $current) use ($excludeDirs): bool {
                if ($current->isDir()) {
                    return ! in_array($current->getBasename(), $excludeDirs, true);
                }
                return true;
            }
        )
    );
    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getRealPath();
        }
    }
    return $files;
}

$files      = is_file($scanPath) ? [$scanPath] : collect_php_files_governed($scanPath, $excludeDirs);
$violations = [];

// WP entry-point function name patterns (mirror GovernedActionGateRule::ENTRY_POINT_PATTERNS).
$entryPointPatterns = [
    '/^(wp_ajax_|wp_ajax_nopriv_)/',
    '/^(admin_post_|admin_post_nopriv_)/',
];

foreach ($files as $file) {
    $source = file_get_contents($file);
    if ($source === false) {
        fwrite(STDERR, "audit-governed-actions: cannot read {$file}\n");
        continue;
    }

    // Tokenize for lightweight function/method extraction.
    $tokens = token_get_all($source);
    $count  = count($tokens);

    // State machine: track docblock → function name → body boundaries.
    $i            = 0;
    $lastDocblock = '';

    while ($i < $count) {
        $token = $tokens[$i];

        // Capture docblocks.
        if (is_array($token) && $token[0] === T_DOC_COMMENT) {
            $lastDocblock = $token[1];
            $i++;
            continue;
        }

        // Clear docblock on non-whitespace, non-attribute tokens before function.
        if (is_array($token) && $token[0] === T_WHITESPACE) {
            $i++;
            continue;
        }

        // Look for function/method declarations.
        if (is_array($token) && in_array($token[0], [T_FUNCTION, T_FN], true)) {
            // Skip forward to function name.
            $j = $i + 1;
            while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                $j++;
            }

            $funcName = '';
            if ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                $funcName = $tokens[$j][1];
            }

            // Determine if this is a governed entry point.
            $isGoverned = str_contains($lastDocblock, '@governed-mutation');
            if ( ! $isGoverned && $funcName !== '' ) {
                foreach ($entryPointPatterns as $pattern) {
                    if (preg_match($pattern, $funcName)) {
                        $isGoverned = true;
                        break;
                    }
                }
            }

            if ($isGoverned) {
                // Extract the function body — scan from opening brace to matching closing brace.
                $k     = $j;
                $depth = 0;
                $body  = '';
                while ($k < $count) {
                    $t = $tokens[$k];
                    $char = is_array($t) ? $t[1] : $t;
                    if ($char === '{') {
                        $depth++;
                    } elseif ($char === '}') {
                        $depth--;
                        if ($depth === 0) {
                            break;
                        }
                    }
                    if ($depth > 0) {
                        $body .= $char;
                    }
                    $k++;
                }

                // Check whether the gate function is called anywhere in the body.
                if ( ! preg_match('/\b' . preg_quote($gateFunction, '/') . '\s*\(/', $body) ) {
                    $lineNo = is_array($tokens[$i]) ? $tokens[$i][2] : 0;
                    $violations[] = sprintf(
                        '%s:%d: %s() is a governed entry point but does not call %s() (AUTH-001)',
                        $file,
                        $lineNo,
                        $funcName ?: '<anonymous>',
                        $gateFunction
                    );
                }
            }

            $lastDocblock = '';
            $i = $j + 1;
            continue;
        }

        // Reset docblock on any non-whitespace token that is not function/fn/attribute.
        if (is_array($token) && ! in_array($token[0], [T_ATTRIBUTE, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $lastDocblock = '';
        }

        $i++;
    }
}

if ($violations === []) {
    echo "audit-governed-actions: OK — all governed entry points call {$gateFunction}().\n";
    exit(0);
}

$count = count($violations);
$label = $warnOnly ? '::warning::' : '::error::';

foreach ($violations as $violation) {
    echo "{$label}{$violation}\n";
}

$summary = "audit-governed-actions: {$count} violation(s) found.";
if ($warnOnly) {
    echo "::warning::{$summary}\n";
    exit(0);
}

echo "::error::{$summary}\n";
exit(1);
