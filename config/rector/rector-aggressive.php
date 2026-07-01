<?php

declare(strict_types=1);

/**
 * Starisian Technologies — Rector aggressive config.
 * Ref: docs/php-wordpress-standard.md §1, §2 (PHP 8.2+ strict types).
 *
 * Aggressive set: includes structural modernization rules that change call sites
 * or require constructor-injection refactors. Review changes before committing.
 *
 * Usage (review diff before commit):
 *     vendor/bin/rector process src --config vendor/starisian-technologies/coding-standards/config/rector/rector-aggressive.php --dry-run
 *
 * Consumer override (in project rector.php):
 *     $rectorConfig->import(__DIR__ . '/vendor/starisian-technologies/coding-standards/config/rector/rector-aggressive.php');
 */

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnDirectArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictBoolReturnExprRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\Rector\FunctionLike\UnionTypesRector;
use Rector\Php81\Rector\Class_\MyCLabsMethodCallToEnumConstRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\DeadCode\Rector\If_\UnwrapFutureCompatibleIfPhpVersionRector;

return RectorConfig::configure()
    ->withImportNames(importDocBlockNames: false)

    // PHP 8.2 minimum — aggressive path.
    ->withPhpSets(php82: true)

    ->withRules([
        // Type declarations — aggressive inference.
        TypedPropertyFromStrictConstructorRector::class,
        TypedPropertyFromStrictSetUpRector::class,
        AddArrowFunctionReturnTypeRector::class,
        AddMethodCallBasedStrictParamTypeRector::class,
        ReturnTypeFromReturnDirectArrayRector::class,
        ReturnTypeFromStrictNativeCallRector::class,
        ReturnTypeFromStrictBoolReturnExprRector::class,
        // PHP 8.x native upgrades.
        UnionTypesRector::class,
        ReadOnlyClassRector::class,
        // Dead code — aggressive removal.
        RemoveUnusedPrivateMethodRector::class,
        RemoveUnusedPrivatePropertyRector::class,
        UnwrapFutureCompatibleIfPhpVersionRector::class,
    ])

    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
    ])

    // Never touch vendor or generated directories.
    ->withSkip([
        '*/vendor/*',
        '*/node_modules/*',
        '*/build/*',
        '*/dist/*',
    ]);
