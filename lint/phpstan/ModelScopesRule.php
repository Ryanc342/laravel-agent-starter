<?php

namespace Lint\PHPStan;

use Illuminate\Database\Eloquent\Attributes\Scope as ScopeAttribute;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Principles 1 and 3 in docs/golden-principles.md, for the scopes of a model:
 *
 * - A scope is a protected method marked #[Scope], never scopeFoo().
 * - A condition is written once. When a where() in the model filters on a
 *   column, no method of the model compares that column again; it asks the
 *   scope with whereKey($this->getKey())->exists() (see Notification::isUnread()).
 *
 * @implements Rule<InClassNode>
 */
final class ModelScopesRule implements Rule
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
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();

        if (! str_starts_with($class->getName(), 'App\\Models\\') || ! $class->is(Model::class)) {
            return [];
        }

        $finder = new NodeFinder;
        $errors = [];
        $filteredBy = [];

        foreach ($node->getOriginalNode()->getMethods() as $method) {
            $name = $method->name->toString();

            if (preg_match('/^scope[A-Z]/', $name) === 1) {
                $scopeName = lcfirst(substr($name, 5));
                $errors[] = RuleErrorBuilder::message("{$name}() is an old-style scope")
                    ->identifier('ezdev.scopeAttribute')
                    ->line($method->getStartLine())
                    ->tip("Rename it to a protected {$scopeName}() marked #[Scope]. See docs/rules/models.md.")
                    ->build();
            }

            foreach ($this->filteredColumns($finder, $method) as $column) {
                $filteredBy[$column][] = $method;
            }
        }

        foreach ($node->getOriginalNode()->getMethods() as $method) {
            foreach ($this->comparisons($finder, $method) as [$column, $line]) {
                $filter = $this->otherFilter($filteredBy[$column] ?? [], $method);

                if ($filter === null) {
                    continue;
                }

                $filterName = $filter->name->toString();
                $errors[] = RuleErrorBuilder::message("{$method->name->toString()}() compares {$column}, which {$filterName}() already filters on")
                    ->identifier('ezdev.conditionTwice')
                    ->line($line)
                    ->tip($this->isScope($filter)
                        ? "Write the condition once: ask the scope with self::query()->{$filterName}()->whereKey(\$this->getKey())->exists(). See docs/rules/models.md."
                        : "Write the condition once: move it into a #[Scope] method and ask that scope with self::query()->scopeName()->whereKey(\$this->getKey())->exists(). See docs/rules/models.md.")
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * A method other than $method that filters on the column, a scope first.
     *
     * @param  list<ClassMethod>  $filters
     */
    private function otherFilter(array $filters, ClassMethod $method): ?ClassMethod
    {
        $others = array_values(array_filter($filters, fn (ClassMethod $filter): bool => $filter !== $method));

        foreach ($others as $filter) {
            if ($this->isScope($filter)) {
                return $filter;
            }
        }

        return $others[0] ?? null;
    }

    /**
     * Columns the method filters on: the first argument of where*() and
     * orWhere*(). Relation filters (whereHas('user'), whereBelongsTo(),
     * whereMorphedTo('notifiable', ...)) name a relation, not a column.
     *
     * @return list<string>
     */
    private function filteredColumns(NodeFinder $finder, ClassMethod $method): array
    {
        $columns = [];

        foreach ($finder->find((array) $method->stmts, fn (Node $n): bool => $n instanceof Expr\MethodCall || $n instanceof Expr\NullsafeMethodCall || $n instanceof Expr\StaticCall) as $call) {
            /** @var Expr\MethodCall|Expr\NullsafeMethodCall|Expr\StaticCall $call */
            if (! $call->name instanceof Node\Identifier
                || $call->isFirstClassCallable()
                || preg_match('/^(or)?where/i', $call->name->toString()) !== 1
                || preg_match('/^(or)?where(has|doesnthave|relation|morph|notmorph|belongsto|attachedto)/i', $call->name->toString()) === 1) {
                continue;
            }

            $first = $call->getArgs()[0]->value ?? null;

            if ($first instanceof Node\Scalar\String_) {
                $parts = explode('.', $first->value);
                $columns[] = end($parts);
            }
        }

        return $columns;
    }

    /**
     * $this->column comparisons in the method, as [column, line].
     *
     * @return list<array{string, int}>
     */
    private function comparisons(NodeFinder $finder, ClassMethod $method): array
    {
        if ($this->isScope($method)) {
            return [];
        }

        $found = [];

        foreach ($finder->find((array) $method->stmts, fn (Node $n): bool => true) as $n) {
            $compared = [];

            foreach (self::COMPARISONS as $comparison) {
                if ($n instanceof $comparison) {
                    $compared = [$n->left, $n->right];
                }
            }

            if ($n instanceof Expr\Match_ || $n instanceof Node\Stmt\Switch_) {
                $compared = [$n->cond];
            }

            if ($n instanceof Expr\FuncCall && $n->name instanceof Node\Name && ! $n->isFirstClassCallable() && in_array($n->name->toLowerString(), ['in_array', 'array_search', 'is_null'], true)) {
                $compared = isset($n->getArgs()[0]) ? [$n->getArgs()[0]->value] : [];
            }

            foreach ($compared as $expr) {
                if (($expr instanceof Expr\PropertyFetch || $expr instanceof Expr\NullsafePropertyFetch)
                    && $expr->var instanceof Expr\Variable
                    && $expr->var->name === 'this'
                    && $expr->name instanceof Node\Identifier) {
                    $found[] = [$expr->name->toString(), $n->getStartLine()];
                }
            }
        }

        return $found;
    }

    private function isScope(ClassMethod $method): bool
    {
        foreach ($method->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                if ($attribute->name->toString() === ScopeAttribute::class) {
                    return true;
                }
            }
        }

        return preg_match('/^scope[A-Z]/', $method->name->toString()) === 1;
    }
}
