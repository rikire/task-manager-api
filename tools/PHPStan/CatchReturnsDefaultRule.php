<?php

declare(strict_types=1);

namespace App\Tools\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reports a catch block that returns a literal default (null, a boolean, a number, a string, an array of
 * literals): the failure disappears and the caller gets a plausible value (RUL-CODE-fail-fast). A computed
 * value is not reported, since its meaning cannot be judged from the code. No ready-made rule covers this
 * (change finish-init, group 6).
 *
 * @implements Rule<Stmt\Catch_>
 */
final class CatchReturnsDefaultRule implements Rule
{
    public function getNodeType(): string
    {
        return Stmt\Catch_::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        foreach ($node->stmts as $statement) {
            if (!$statement instanceof Stmt\Return_ || null === $statement->expr || !$this->isLiteral($statement->expr)) {
                continue;
            }
            $value = new Standard()->prettyPrintExpr($statement->expr);
            $errors[] = RuleErrorBuilder::message(\sprintf(
                'Catch block returns a default value (%s); rethrow or let it fail (RUL-CODE-fail-fast).',
                $value,
            ))->identifier('app.catchReturnsDefault')->line($statement->getStartLine())->build();
        }

        return $errors;
    }

    private function isLiteral(Expr $expr): bool
    {
        if ($expr instanceof Expr\ConstFetch) {
            return \in_array($expr->name->toLowerString(), ['null', 'true', 'false'], true);
        }
        if ($expr instanceof Expr\Array_) {
            foreach ($expr->items as $item) {
                if (!$this->isLiteral($item->value)) {
                    return false;
                }
            }

            return true;
        }

        return $expr instanceof Scalar\Int_ || $expr instanceof Scalar\Float_ || $expr instanceof Scalar\String_;
    }
}
