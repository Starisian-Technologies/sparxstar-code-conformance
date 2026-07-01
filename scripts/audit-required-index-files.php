#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * audit-required-index-files.php — Starisian Technologies audit script.
 *
 * Rule: every directory under the scanned path must contain an index.php.
 * Ref:  docs/php-wordpress-standard.md §9 (security hardening).
 *       WordPress security practice: prevent directory listing.
 *
 * The required index.php content is a silent exit:
 *   <?php // Silence is golden.
 *
 * Exit codes:
 *   0 — no violations found
 *   1 — violations found (or script error)
 *
 * Usage:
 *   php scripts/audit-required-index-files.php [--path=src] [--warn-only]
 */

$options  = getopt('', ['path:', 'warn-only', 'exclude:']);
$scanPath = $options['path'] ?? 'src';
$warnOnly = array_key_exists('warn-only', $options);
$exclude  = $options['exclude'] ?? '';

$excludeDirs = array_filter(array_map('trim', explode(',', $exclude)));
$excludeDirs = array_merge($excludeDirs, ['vendor', 'node_modules', 'build', 'dist', '.git']);

if ( ! is_dir($scanPath) ) {
    fwrite(STDERR, "audit-required-index-files: path not found or not a directory: {$scanPath}\n");
    exit(1);
}

$violations = [];

/**
 * Recursively check that every directory contains index.php.
 *
 * @param string       $dir
 * @param list<string> $excludeDirs
 */
function check_index_files(string $dir, array $excludeDirs, array &$violations): void
{
    $entries = scandir($dir);
    if ($entries === false) {
        return;
    }

    // Check the current directory.
    if ( ! file_exists($dir . '/index.php') ) {
        $violations[] = $dir . '/index.php is missing (every directory must contain index.php)';
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . '/' . $entry;
        if ( is_dir($path) ) {
            if ( ! in_array($entry, $excludeDirs, true) ) {
                check_index_files($path, $excludeDirs, $violations);
            }
        }
    }
}

check_index_files(rtrim($scanPath, '/'), $excludeDirs, $violations);

if ($violations === []) {
    echo "audit-required-index-files: OK — all directories have index.php.\n";
    exit(0);
}

$count = count($violations);
$label = $warnOnly ? '::warning::' : '::error::';

foreach ($violations as $violation) {
    echo "{$label}{$violation}\n";
}

$summary = "audit-required-index-files: {$count} missing index.php file(s).";
if ($warnOnly) {
    echo "::warning::{$summary}\n";
    exit(0);
}

echo "::error::{$summary}\n";
exit(1);
