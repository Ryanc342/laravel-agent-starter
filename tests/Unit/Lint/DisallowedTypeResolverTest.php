<?php

use Lint\PHPStan\DisallowedTypeResolver;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\TypeCombinator;
use Spaze\PHPStan\Rules\Disallowed\Normalizer\Normalizer;

test('forbidden calls still inspect the model when its receiver is nullable', function () {
    $receiver = new Variable('user');
    $scope = Mockery::mock(Scope::class);
    $scope->shouldReceive('getType')->once()->with($receiver)
        ->andReturn(TypeCombinator::union(new ObjectWithoutClassType, new NullType));

    $resolver = new DisallowedTypeResolver(new Normalizer);

    expect($resolver->getType($receiver, $scope)->equals(new ObjectWithoutClassType))->toBeTrue();
});

test('forbidden static calls keep resolving the class name', function () {
    $name = new Name('User');
    $scope = Mockery::mock(Scope::class);
    $scope->shouldReceive('resolveName')->once()->with($name)->andReturn(stdClass::class);

    $resolver = new DisallowedTypeResolver(new Normalizer);

    expect($resolver->getType($name, $scope)->equals(new ObjectType(stdClass::class)))->toBeTrue();
});
