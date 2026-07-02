#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * audit-no-dbdelta.php — Starisian Technologies audit script.
 *
 * Rule: dbDelta() is forbidden except at approved schema migration entry points.
 * Ref:  docs/php-wordpress-standard.md §5 (database).
 *
 * dbDelta() is a schema mutation function. Ad-hoc calls outside an approved
 * migration entry point create uncontrolled schema drift and violate the
 * deployment safety rules (DIST-003, DIST-004).
 *
 * To request an approved exception, add an entry to
 * .standards/standards-exceptions.yml with rule "NO-DBDELTA" and obtain
 * the required approval fields before the call will be allowed.
 *
 * Exit codes:
 *   0 — no violations found (or all calls are in approved exception files)
 *   1 — violations found (or script error)
 *
 * Usage:
 *   php scripts/audit-no-dbdelta.php [--path=src] [--warn-only] [--exceptions=.standards/standards-exceptions.yml]
 */

$options        = getopt('', ['path:', 'warn-only', 'exclude:', 'exceptions:']);
$scanPath       = $options['path'] ?? 'src';
$warnOnly       = array_key_exists('warn-only', $options);
$exclude        = $options['exclude'] ?? '';
$exceptionsFile = $options['exceptions'] ?? '.standards/standards-exceptions.yml';

$excludeDirs = array_filter(array_map('trim', explode(',', $exclude)));
$excludeDirs = array_merge($excludeDirs, ['vendor', 'node_modules', 'build', 'dist']);

if ( ! is_dir($scanPath) && ! is_file($scanPath) ) {
    fwrite(STDERR, "audit-no-dbdelta: path not found: {$scanPath}\n");
    exit(1);
}

/**
 * Load approved dbDelta exception file paths from standards-exceptions.yml.
 *
 * @return list<string>
 */
function load_dbdelta_exception_paths(string $exceptionsFile): array
{
    if ( ! file_exists($exceptionsFile) ) {
        return [];
    }
    $content = file_get_contents($exceptionsFile);
    if ($content === false) {
        return [];
    }
    // Simple pattern extraction — does not require a YAML parser.
    // Looks for exceptions with rule: "NO-DBDELTA" and an approved_files list.
    $approved = [];
    if (preg_match_all('/rule:\s*["\']?NO-DBDELTA["\']?.*?approved_files:\s*\n((?:\s+-\s+\S+\n?)+)/ms', $content, $matches)) {
        foreach ($matches[1] as $fileList) {
            preg_match_all('/\s+-\s+(\S+)/', $fileList, $fileMatches);
            $approved = array_merge($approved, $fileMatches[1]);
        }
    }
    return $approved;
}

/**
 * @param string       $dir
 * @param list<string> $excludeDirs
 * @return list<string>
 */
function collect_php_files_dbdelta(string $dir, array $excludeDirs): array
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

$approvedPaths = load_dbdelta_exception_paths($exceptionsFile);
$files         = is_file($scanPath) ? [$scanPath] : collect_php_files_dbdelta($scanPath, $excludeDirs);
$violations    = [];

foreach ($files as $file) {
    // Normalize path for comparison.
    $normalizedFile = ltrim(str_replace(getcwd() . '/', '', $file), '/');

    // Skip approved exception files.
    foreach ($approvedPaths as $approvedPath) {
        if (str_ends_with($normalizedFile, ltrim($approvedPath, '/'))) {
            continue 2;
        }
    }

    $source = file_get_contents($file);
    if ($source === false) {
        fwrite(STDERR, "audit-no-dbdelta: cannot read {$file}\n");
        continue;
    }

    // Use PHP tokenizer to skip dbDelta occurrences inside comments and strings.
    $tokens   = token_get_all($source);
    $tokCount = count($tokens);
    for ($i = 0; $i < $tokCount; $i++) {
        $token = $tokens[$i];
        if ( ! is_array($token)) {
            continue;
        }
        [$type, $value, $lineNo] = $token;
        // T_STRING covers function-name identifiers; skip T_COMMENT / T_DOC_COMMENT.
        if ($type !== T_STRING || strtolower($value) !== 'dbdelta') {
            continue;
        }
        // Confirm the next non-whitespace token is `(` — i.e. a function call.
        $j = $i + 1;
        while ($j < $tokCount && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if ($j < $tokCount && ! is_array($tokens[$j]) && $tokens[$j] === '(') {
            $violations[] = sprintf(
                '%s:%d: dbDelta() is forbidden except at approved migration entry points — add exception to %s',
                $file,
                $lineNo,
                $exceptionsFile
            );
        }
    }
}

if ($violations === []) {
    echo "audit-no-dbdelta: OK — no unapproved dbDelta() calls found.\n";
    exit(0);
}

$count = count($violations);
$label = $warnOnly ? '::warning::' : '::error::';

foreach ($violations as $violation) {
    echo "{$label}{$violation}\n";
}

$summary = "audit-no-dbdelta: {$count} violation(s) found.";
if ($warnOnly) {
    echo "::warning::{$summary}\n";
    exit(0);
}

echo "::error::{$summary}\n";
exit(1);
