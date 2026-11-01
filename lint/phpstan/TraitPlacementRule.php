<?php

namespace Lint\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Principles 3 and 7 in docs/golden-principles.md, for traits. PHPStan checks a
 * trait only inside the classes that use it, so ClassShapeRule never sees one.
 *
 * - app/Enums holds enums only.
 * - app/Actions holds Actions only: steps two Actions share go in a third
 *   Action that both call.
 *
 * @implements Rule<Trait_>
 */
final class TraitPlacementRule implements Rule
{
    public function getNodeType(): string
    {
        return Trait_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $name = $node->namespacedName?->toString() ?? '';

        if (str_starts_with($name, 'App\\Enums\\')) {
            return [
                RuleErrorBuilder::message("{$name} is in app/Enums but is not an enum")
                    ->identifier('ezdev.enumsOnly')
                    ->tip('app/Enums holds enums only. A trait several classes share goes in app/Concerns. See ARCHITECTURE.md.')
                    ->build(),
            ];
        }

        if (str_starts_with($name, 'App\\Actions\\')) {
            return [
                RuleErrorBuilder::message("{$name} is a trait in app/Actions")
                    ->identifier('ezdev.actionShape')
                    ->tip('app/Actions holds Actions only. Put the shared steps in an Action and call it from the others. See docs/rules/actions.md.')
                    ->build(),
            ];
        }

        return [];
    }
}
