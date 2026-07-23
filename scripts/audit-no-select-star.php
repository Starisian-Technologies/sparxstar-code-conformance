#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * audit-no-select-star.php — Starisian Technologies audit script.
 *
 * Rule: SELECT * is forbidden. All queries must enumerate columns explicitly.
 * Ref:  docs/php-wordpress-standard.md §5 (database).
 *       CI-Enforcement-Matrix.md PHP-002.
 *
 * SELECT * bypasses column-level security, breaks caching, and causes
 * unnecessary data transfer. Always list the columns you need.
 *
 * Exit codes:
 *   0 — no violations found
 *   1 — violations found (or script error)
 *
 * Usage:
 *   php scripts/audit-no-select-star.php [--path=src] [--warn-only]
 */

$options  = getopt('', ['path:', 'warn-only', 'exclude:']);
$scanPath = $options['path'] ?? 'src';
$warnOnly = array_key_exists('warn-only', $options);
$exclude  = $options['exclude'] ?? '';

$excludeDirs = array_filter(array_map('trim', explode(',', $exclude)));
$excludeDirs = array_merge($excludeDirs, ['vendor', 'node_modules', 'build', 'dist']);

if ( ! is_dir($scanPath) && ! is_file($scanPath) ) {
    fwrite(STDERR, "audit-no-select-star: path not found: {$scanPath}\n");
    exit(1);
}

/**
 * @param string       $dir
 * @param list<string> $excludeDirs
 * @return list<string>
 */
function collect_php_files_select(string $dir, array $excludeDirs): array
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

$files = is_file($scanPath) ? [$scanPath] : collect_php_files_select($scanPath, $excludeDirs);

$violations = [];

// Pattern: SELECT followed by * and then FROM (case-insensitive, allows whitespace).
$pattern = '/\bSELECT\s+\*\s+FROM\b/i';

foreach ($files as $file) {
    $source = file_get_contents($file);
    if ($source === false) {
        $violations[] = sprintf('%s:0: cannot read file (audit-no-select-star script error)', $file);
        continue;
    }

    // Strip comments/docblocks while preserving line numbers for reporting.
    $tokens = token_get_all($source);
    $clean  = '';
    foreach ($tokens as $token) {
        if ( ! is_array($token)) {
            $clean .= $token;
            continue;
        }
        if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            $clean .= str_repeat("\n", substr_count($token[1], "\n"));
            continue;
        }
        $clean .= $token[1];
    }

    if (preg_match_all($pattern, $clean, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as [$match, $offset]) {
            $lineNo = substr_count($clean, "\n", 0, $offset) + 1;
            $violations[] = sprintf(
                '%s:%d: SELECT * is forbidden — enumerate columns explicitly (PHP-002)',
                $file,
                $lineNo
            );
        }
    }
}

if ($violations === []) {
    echo "audit-no-select-star: OK — no SELECT * found.\n";
    exit(0);
}

$count = count($violations);
$label = $warnOnly ? '::warning::' : '::error::';

foreach ($violations as $violation) {
    echo "{$label}{$violation}\n";
}

$summary = "audit-no-select-star: {$count} violation(s) found.";
if ($warnOnly) {
    echo "::warning::{$summary}\n";
    exit(0);
}

echo "::error::{$summary}\n";
exit(1);
