<?php

namespace Lint\PHPStan;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Principle 3 in docs/golden-principles.md: a scope is a protected method
 * marked #[Scope], never scopeFoo(). One form, so a condition has one place to
 * look for.
 *
 * @implements Rule<InClassNode>
 */
final class ModelScopesRule implements Rule
{
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

        $errors = [];

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
        }

        return $errors;
    }
}
