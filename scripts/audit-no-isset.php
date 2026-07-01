#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * audit-no-isset.php — Starisian Technologies audit script.
 *
 * Rule: isset() is forbidden in this codebase.
 * Ref:  docs/php-wordpress-standard.md §2 (strict types), §4 (no isset).
 *       CI-Enforcement-Matrix.md PHP-001.
 *
 * isset() masks type errors that strict types would catch. Replace with:
 *   - null coalescing operator (??) for default values
 *   - array_key_exists() for explicit key presence checks
 *   - Typed properties with constructor enforcement
 *
 * Exit codes:
 *   0 — no violations found
 *   1 — violations found (or script error)
 *
 * Usage:
 *   php scripts/audit-no-isset.php [--path=src] [--warn-only]
 */

$options   = getopt('', ['path:', 'warn-only', 'exclude:']);
$scanPath  = $options['path'] ?? 'src';
$warnOnly  = array_key_exists('warn-only', $options);
$exclude   = $options['exclude'] ?? '';

$excludeDirs = array_filter(array_map('trim', explode(',', $exclude)));
$excludeDirs = array_merge($excludeDirs, ['vendor', 'node_modules', 'build', 'dist']);

if ( ! is_dir($scanPath) && ! is_file($scanPath) ) {
    fwrite(STDERR, "audit-no-isset: path not found: {$scanPath}\n");
    exit(1);
}

/**
 * Recursively collect PHP files under $dir, honouring $excludeDirs.
 *
 * @param string        $dir
 * @param list<string>  $excludeDirs
 * @return list<string>
 */
function collect_php_files(string $dir, array $excludeDirs): array
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

$files = is_file($scanPath) ? [$scanPath] : collect_php_files($scanPath, $excludeDirs);

$violations = [];

foreach ($files as $file) {
    $source = file_get_contents($file);
    if ($source === false) {
        fwrite(STDERR, "audit-no-isset: cannot read {$file}\n");
        continue;
    }

    // Use PHP's tokenizer to skip isset() inside comments and strings.
    $tokens = token_get_all($source);
    foreach ($tokens as $token) {
        if ( ! is_array($token)) {
            continue;
        }
        [$type, $value, $lineNo] = $token;
        // Only flag T_ISSET tokens (actual isset() keyword), not occurrences in comments/strings.
        if ($type === T_ISSET) {
            $violations[] = sprintf('%s:%d: isset() is forbidden — use ?? or array_key_exists()', $file, $lineNo);
        }
    }
}

if ($violations === []) {
    echo "audit-no-isset: OK — no isset() calls found.\n";
    exit(0);
}

$count = count($violations);
$label = $warnOnly ? '::warning::' : '::error::';

foreach ($violations as $violation) {
    echo "{$label}{$violation}\n";
}

$summary = "audit-no-isset: {$count} violation(s) found.";
if ($warnOnly) {
    echo "::warning::{$summary}\n";
    exit(0);
}

echo "::error::{$summary}\n";
exit(1);
