<?php

declare(strict_types=1);

/**
 * Starisian Technologies — Rector safe config.
 * Ref: docs/php-wordpress-standard.md §1 (PHP 8.2 minimum).
 *
 * Safe set: rules with zero behavior change risk.
 * Run before committing; safe to apply to any codebase.
 *
 * Usage:
 *     vendor/bin/rector process src --config vendor/starisian-technologies/coding-standards/config/rector/rector-safe.php
 *
 * Consumer override (in project rector.php):
 *     $rectorConfig->import(__DIR__ . '/vendor/starisian-technologies/coding-standards/config/rector/rector-safe.php');
 *     // then add project-specific paths/rules
 */

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\Php80\Rector\FunctionLike\UnionTypesRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;

return RectorConfig::configure()
    ->withImportNames(importDocBlockNames: false)

    // PHP 8.2 minimum — upgrade path only, never downgrade.
    ->withPhpSets(php82: true)

    // Safe, high-confidence type inference.
    ->withRules([
        TypedPropertyFromStrictConstructorRector::class,
        TypedPropertyFromStrictSetUpRector::class,
        AddReturnTypeDeclarationBasedOnParentClassMethodRector::class,
        AddArrowFunctionReturnTypeRector::class,
        // Dead code removal — zero risk.
        RemoveUnusedPrivateMethodRector::class,
        RemoveUnusedPrivatePropertyRector::class,
    ])

    // Standard code-quality set: array unpacking, null-safe operators, etc.
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
    ])

    // Never touch vendor or generated directories.
    ->withSkip([
        '*/vendor/*',
        '*/node_modules/*',
        '*/build/*',
        '*/dist/*',
    ]);
