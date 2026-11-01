<?php

namespace Tests\PHPStan;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Principle 4 in docs/golden-principles.md: every model reaches React through
 * a JsonResource. The props of Inertia::render() and inertia(), and the shared
 * props returned by HandleInertiaRequests::share(), never hold a model, a model
 * collection or a paginator of models: not directly, not inside an array, not
 * returned by a closure or by Inertia::defer(), optional(), merge() and the like.
 *
 * An array literal is checked item by item, because spreading another array
 * (...parent::share($request)) makes the type of the whole array too loose.
 *
 * @implements Rule<Node>
 */
final class PagePropsUseResourcesRule implements Rule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $props = $this->props($node, $scope);

        if ($props === null || ! $this->expressionHoldsModel($props, $scope, 0)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('send models to React through a JsonResource')
                ->identifier('ezdev.modelInPageProps')
                ->tip('Use app/Http/Resources/{Model}Resource.php: {Model}Resource::make($model)->resolve($request) for one record, {Model}Resource::collection($models)->response($request)->getData(true) for a list. See docs/rules/frontend.md.')
                ->build(),
        ];
    }

    private function props(Node $node, Scope $scope): ?Expr
    {
        if ($node instanceof Expr\StaticCall
            && $node->class instanceof Node\Name
            && $scope->resolveName($node->class) === 'Inertia\Inertia'
            && $node->name instanceof Node\Identifier
            && $node->name->toLowerString() === 'render') {
            return $node->getArgs()[1]->value ?? null;
        }

        if ($node instanceof Expr\FuncCall
            && $node->name instanceof Node\Name
            && $node->name->toLowerString() === 'inertia') {
            return $node->getArgs()[1]->value ?? null;
        }

        if ($node instanceof Node\Stmt\Return_
            && $node->expr !== null
            && $scope->getFunction()?->getName() === 'share'
            && $scope->getClassReflection()?->isSubclassOf('Inertia\Middleware') === true) {
            return $node->expr;
        }

        return null;
    }

    /**
     * Inertia helpers that wrap a prop; their first argument is the value or a closure returning it.
     */
    private const WRAPPERS = ['defer', 'optional', 'lazy', 'always', 'merge', 'deepmerge', 'scroll', 'once'];

    private function expressionHoldsModel(Expr $expr, Scope $scope, int $depth): bool
    {
        if ($depth > 5) {
            return false;
        }

        if ($expr instanceof Expr\Array_) {
            foreach ($expr->items as $item) {
                if ($item !== null && ! $item->unpack && $this->expressionHoldsModel($item->value, $scope, $depth + 1)) {
                    return true;
                }
            }

            return false;
        }

        if ($expr instanceof Expr\StaticCall
            && $expr->class instanceof Node\Name
            && $scope->resolveName($expr->class) === 'Inertia\Inertia'
            && $expr->name instanceof Node\Identifier
            && in_array($expr->name->toLowerString(), self::WRAPPERS, true)) {
            $wrapped = $expr->getArgs()[0]->value ?? null;

            return $wrapped !== null && $this->expressionHoldsModel($wrapped, $scope, $depth + 1);
        }

        return $this->holdsModel($scope->getType($expr), $scope, $depth);
    }

    private function holdsModel(Type $type, Scope $scope, int $depth): bool
    {
        if ($depth > 5) {
            return false;
        }

        $type = TypeCombinator::removeNull($type);

        foreach ([Model::class, EloquentCollection::class] as $class) {
            if ((new ObjectType($class))->isSuperTypeOf($type)->yes()) {
                return true;
            }
        }

        $constantArrays = $type->getConstantArrays();

        if ($constantArrays !== []) {
            foreach ($constantArrays as $array) {
                foreach ($array->getValueTypes() as $valueType) {
                    if ($this->holdsModel($valueType, $scope, $depth + 1)) {
                        return true;
                    }
                }
            }

            return false;
        }

        if ($type->isArray()->yes()) {
            return $this->holdsModel($type->getIterableValueType(), $scope, $depth + 1);
        }

        if ($type->isObject()->yes()
            && $type->isIterable()->yes()
            && ! (new ObjectType(JsonResource::class))->isSuperTypeOf($type)->yes()) {
            return $this->holdsModel($type->getIterableValueType(), $scope, $depth + 1);
        }

        if ($type->isCallable()->yes()) {
            foreach ($type->getCallableParametersAcceptors($scope) as $acceptor) {
                if ($this->holdsModel($acceptor->getReturnType(), $scope, $depth + 1)) {
                    return true;
                }
            }
        }

        return false;
    }
}
