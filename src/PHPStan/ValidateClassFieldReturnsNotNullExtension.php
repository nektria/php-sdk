<?php

declare(strict_types=1);

namespace Nektria\PHPStan;

use Nektria\Util\Validate;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\TypeCombinator;

class ValidateClassFieldReturnsNotNullExtension implements TypeSpecifierAwareExtension
{
    private TypeSpecifier $typeSpecifier;

    public function getClass(): string
    {
        return Validate::class;
    }

    public function isStaticMethodSupported(
        MethodReflection $staticMethodReflection,
        StaticCall $node,
        TypeSpecifierContext $context
    ): bool {
        return $staticMethodReflection->getName() === 'classFieldReturnsNotNull' && $context->null();
    }

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }

    public function specifyTypes(
        MethodReflection $staticMethodReflection,
        StaticCall $node,
        Scope $scope,
        TypeSpecifierContext $context
    ): SpecifiedTypes {
        $expr = $node->getArgs()[2]->value;
        $typeBefore = $scope->getType($expr);
        $type = TypeCombinator::removeNull($typeBefore);

        return $this->typeSpecifier->create($expr, $type, TypeSpecifierContext::createTruthy(), $scope);
    }
}
