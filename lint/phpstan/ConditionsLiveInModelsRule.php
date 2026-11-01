<?php

namespace Lint\PHPStan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

/**
 * Principle 1 in docs/golden-principles.md: a condition on a model's fields or
 * on an enum's values is written once, in the model or in the enum. The rest
 * of app/ calls that method instead of comparing the field again.
 *
 * Catches comparisons (===, <, ...), match, switch and in_array() on a model
 * field (including $this->field inside a Resource), on an enum case or on an
 * enum's value ($status->value), in any file under app/ except app/Models and
 * app/Enums.
 *
 * @implements Rule<Node>
 */
final class ConditionsLiveInModelsRule implements Rule
{
    /**
     * @var list<class-string<BinaryOp>>
     */
    private const COMPARISONS = [
        BinaryOp\Identical::class,
        BinaryOp\NotIdentical::class,
        BinaryOp\Equal::class,
        BinaryOp\NotEqual::class,
        BinaryOp\Smaller::class,
        BinaryOp\SmallerOrEqual::class,
        BinaryOp\Greater::class,
        BinaryOp\GreaterOrEqual::class,
        BinaryOp\Spaceship::class,
    ];

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->appliesTo($scope->getFile())) {
            return [];
        }

        foreach ($this->comparedExpressions($node) as $expr) {
            $what = $this->describe($expr, $scope);

            if ($what !== null) {
                return [
                    RuleErrorBuilder::message("conditions on {$what} live in the model or the enum")
                        ->identifier('ezdev.conditionOutsideModel')
                        ->tip('Add a method to the model (isOwnedBy(), canBeCompleted()) or to the enum (isOpen()) and call it here. A list query uses a #[Scope]. See docs/rules/models.md.')
                        ->build(),
                ];
            }
        }

        return [];
    }

    private function appliesTo(string $file): bool
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 2)).'/';
        $file = str_replace('\\', '/', $file);

        if (! str_starts_with($file, $root)) {
            return false;
        }

        $relative = substr($file, strlen($root));

        return str_starts_with($relative, 'app/')
            && ! str_starts_with($relative, 'app/Models/')
            && ! str_starts_with($relative, 'app/Enums/');
    }

    /**
     * @return list<Expr>
     */
    private function comparedExpressions(Node $node): array
    {
        foreach (self::COMPARISONS as $class) {
            if ($node instanceof $class) {
                return [$node->left, $node->right];
            }
        }

        if ($node instanceof Expr\Match_ || $node instanceof Node\Stmt\Switch_) {
            return [$node->cond];
        }

        if ($node instanceof Expr\FuncCall
            && $node->name instanceof Node\Name
            && ! $node->isFirstClassCallable()
            && in_array($node->name->toLowerString(), ['in_array', 'array_search'], true)) {
            $args = $node->getArgs();

            return isset($args[0]) ? [$args[0]->value] : [];
        }

        return [];
    }

    private function describe(Expr $expr, Scope $scope): ?string
    {
        if ($expr instanceof Expr\PropertyFetch || $expr instanceof Expr\NullsafePropertyFetch) {
            $holder = TypeCombinator::removeNull($scope->getType($expr->var));

            if ((new ObjectType(Model::class))->isSuperTypeOf($holder)->yes()) {
                return 'model fields';
            }

            $isThis = $expr->var instanceof Expr\Variable && $expr->var->name === 'this';

            if ($isThis && (new ObjectType(JsonResource::class))->isSuperTypeOf($holder)->yes()) {
                return 'model fields';
            }

            if ($holder->isEnum()->yes()) {
                return 'enum values';
            }
        }

        if ($expr instanceof Expr\ClassConstFetch && $scope->getType($expr)->isEnum()->yes()) {
            return 'enum values';
        }

        return null;
    }
}
