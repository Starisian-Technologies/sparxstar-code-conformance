<?php

declare(strict_types=1);

namespace Starisian\Standards\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * STD-TOOLCHAIN-001 §5 — Governed-action gate rule.
 *
 * A governed mutation entry point must call the platform consent/ability gate
 * before mutating state. Scope: REST route handlers, admin-post handlers,
 * AJAX handlers, WP-CLI mutation commands, and service methods annotated
 * as governed mutations.
 *
 * STATUS: warn-only — not required until backing ADR is ratified (§10 directive 10).
 *
 * SCOPE: Entry points only — not every function that writes. The gate is asserted
 * at the entry point; internal helpers below it are out of scope.
 */
class GovernedActionGateRule implements Rule
{
    /** Docblock tags that mark a method as a governed mutation entry point. */
    private const GOVERNED_TAGS = ['@governed-mutation'];

    /** Function name patterns that identify WP entry points. */
    private const ENTRY_POINT_PATTERNS = [
        '/^(wp_ajax_|wp_ajax_nopriv_)/',  // AJAX handlers
        '/^admin_post_/',                   // admin-post handlers
    ];

    /** Gate function the platform requires to be called. */
    private const GATE_FUNCTION = 'assert_governed_action';

    public function getNodeTypes(): array
    {
        return [\PhpParser\Node\Stmt\ClassMethod::class, \PhpParser\Node\Stmt\Function_::class];
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!($node instanceof Function_) && !($node instanceof ClassMethod)) {
            return [];
        }

        if (!$this->isGovernedEntryPoint($node, $scope)) {
            return [];
        }

        if ($this->callsGate($node)) {
            return [];
        }

        $name = $node instanceof ClassMethod
            ? $scope->getClassReflection()?->getName() . '::' . $node->name->name
            : $node->name->name;

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'STD-TOOLCHAIN-001 §5: Governed mutation entry point %s() must call %s() before mutating state.',
                    $name,
                    self::GATE_FUNCTION
                )
            )
            ->tip('Add ' . self::GATE_FUNCTION . '() as the first call in this entry point.')
            ->build(),
        ];
    }

    private function isGovernedEntryPoint(Function_|ClassMethod $node, Scope $scope): bool
    {
        // Check for explicit annotation
        $docComment = $node->getDocComment()?->getText() ?? '';
        foreach (self::GOVERNED_TAGS as $tag) {
            if (str_contains($docComment, $tag)) {
                return true;
            }
        }

        // Check WP entry point naming patterns (top-level functions only)
        if ($node instanceof Function_) {
            $name = $node->name->name;
            foreach (self::ENTRY_POINT_PATTERNS as $pattern) {
                if (preg_match($pattern, $name)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function callsGate(Function_|ClassMethod $node): bool
    {
        // Walk the function body looking for a call to the gate function
        $stmts = $node->stmts ?? [];
        return $this->stmtsCallGate($stmts);
    }

    private function stmtsCallGate(array $stmts): bool
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Expression &&
                $stmt->expr instanceof Node\Expr\FuncCall &&
                $stmt->expr->name instanceof Node\Name &&
                $stmt->expr->name->toString() === self::GATE_FUNCTION
            ) {
                return true;
            }
            // Recurse into nested blocks (if/try/etc.)
            foreach ($stmt->getSubNodeNames() as $subName) {
                $sub = $stmt->$subName;
                if (is_array($sub) && $this->stmtsCallGate($sub)) {
                    return true;
                }
            }
        }
        return false;
    }
}
