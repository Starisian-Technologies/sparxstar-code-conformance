<?php

declare(strict_types=1);

/**
 * Starisian Technologies — Rector safe config.
 * Ref: docs/php-wordpress-standard.md §1 (PHP 8.2 minimum).
 *
 * Safe set: rules that are strictly additive — they only insert type
 * declarations that can be inferred with certainty from the existing code.
 * No dead-code removal, no structural rewrites, no behavioral changes.
 *
 * Usage (in consuming project):
 *     vendor/bin/rector process src --config vendor/starisian-technologies/coding-standards/config/rector/rector-safe.php
 *
 * Consumer override (in project rector.php):
 *     $rectorConfig->import(__DIR__ . '/vendor/starisian-technologies/coding-standards/config/rector/rector-safe.php');
 *     // then add project-specific paths/rules
 */

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;

return RectorConfig::configure()
    ->withImportNames(importDocBlockNames: false)

    // PHP 8.2 minimum — upgrade path only, never downgrade.
    ->withPhpSets(php82: true)

    // Strictly additive type inference: insert declarations that the compiler
    // can already prove from strict constructor/setUp assignments and parent
    // class contracts. No dead-code removal, no CODE_QUALITY or TYPE_DECLARATION
    // sets (those include structural rewrites with behavior-change risk).
    ->withRules([
        TypedPropertyFromStrictConstructorRector::class,
        TypedPropertyFromStrictSetUpRector::class,
        AddReturnTypeDeclarationBasedOnParentClassMethodRector::class,
        AddArrowFunctionReturnTypeRector::class,
    ])

    // Never touch vendor or generated directories.
    ->withSkip([
        '*/vendor/*',
        '*/node_modules/*',
        '*/build/*',
        '*/dist/*',
    ]);
