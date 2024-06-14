<?php

declare(strict_types=1);

namespace Nektria\PHPStan;

use DateTimeInterface;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BinaryOp\Spaceship;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<New_>
 */
class DisallowClassUsages implements Rule
{
    public function getNodeType(): string
    {
        return New_::class;
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classNames = [];
        if ($node->class instanceof Name) {
            $classNames[] = $node->class;
        } elseif ($node->class instanceof Expr) {
            $type = $scope->getType($node->class);
            foreach ($type->getConstantStrings() as $constantString) {
                $classNames[] = new Name($constantString->getValue());
            }
        }

        var_dump($classNames);

        return [RuleErrorBuilder::message("Cannot compare clocks (}).")->identifier('nektria.comparation')->build()];
    }
}
