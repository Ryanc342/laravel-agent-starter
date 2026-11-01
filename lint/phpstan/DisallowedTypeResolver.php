<?php

namespace Lint\PHPStan;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Spaze\PHPStan\Rules\Disallowed\Type\TypeResolver;

/**
 * Keep the existing forbidden-call rules active for nullable receivers such
 * as Request::user(). Spaze otherwise skips User|null before matching calls.
 */
final class DisallowedTypeResolver extends TypeResolver
{
    /**
     * @param  Name|Expr  $class
     */
    public function getType($class, Scope $scope): Type
    {
        return TypeCombinator::removeNull(parent::getType($class, $scope));
    }
}
