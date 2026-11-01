<?php

namespace Lint\PHPStan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Principles 7 and 8 in docs/golden-principles.md: some classes always come
 * with a second file.
 *
 * - Every Action has a feature test: tests/Feature/{Domain}/{Action}Test.php.
 * - Every model has a factory: database/factories/{Model}Factory.php.
 * - Every Resource that sends can.* has a button consistency test:
 *   tests/Feature/{Domain}/{Model}ButtonsTest.php.
 *
 * The rule tells PHPStan which files it looked for, so the result cache checks
 * the class again when such a file appears (PHPStan 2.3 and later).
 *
 * @implements Rule<InClassNode>
 */
final class CompanionFilesRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        $name = $class->getName();
        $root = dirname(__DIR__, 2);

        if (str_starts_with($name, 'App\\Actions\\') && $class->isClass()) {
            $test = 'tests/Feature/'.str_replace('\\', '/', substr($name, strlen('App\\Actions\\'))).'Test.php';

            return $this->expect($scope, $root, $test, "Action {$name} has no feature test", 'ezdev.actionTest', "Add {$test}: the happy path and every ValidationException the Action throws. See docs/rules/tests.md.");
        }

        if (str_starts_with($name, 'App\\Models\\') && $class->is(Model::class) && ! $class->isAbstract()) {
            $factory = 'database/factories/'.str_replace('\\', '/', substr($name, strlen('App\\Models\\'))).'Factory.php';

            return $this->expect($scope, $root, $factory, "Model {$name} has no factory", 'ezdev.modelFactory', "Add {$factory} with the states the tests need. See docs/rules/models.md.");
        }

        if (str_starts_with($name, 'App\\Http\\Resources\\') && $class->is(JsonResource::class) && $this->sendsCan($node)) {
            $model = preg_replace('/Resource$/', '', $class->getNativeReflection()->getShortName());
            $testName = "{$model}ButtonsTest.php";

            if ($scope instanceof DependencyTracker) {
                foreach (glob("{$root}/tests/Feature/*", GLOB_ONLYDIR) ?: [] as $folder) {
                    $scope->trackFileDependency("{$folder}/{$testName}");
                }

                if (method_exists($scope, 'trackDirectoryDependency')) {
                    $scope->trackDirectoryDependency("{$root}/tests/Feature", $testName);
                }
            }

            if ((glob("{$root}/tests/Feature/*/{$testName}") ?: []) === []) {
                return [
                    RuleErrorBuilder::message("Resource {$name} sends can.* but has no button consistency test")
                        ->identifier('ezdev.buttonsTest')
                        ->tip("Add tests/Feature/{Domain}/{$testName}: every can.* flag matches what the Policy allows. Copy docs/reference/tasks/tests/Feature/Tasks/TaskButtonsTest.php. See docs/rules/tests.md.")
                        ->build(),
                ];
            }
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function expect(Scope $scope, string $root, string $file, string $message, string $identifier, string $tip): array
    {
        if ($scope instanceof DependencyTracker) {
            $scope->trackFileDependency("{$root}/{$file}");
        }

        if (file_exists("{$root}/{$file}")) {
            return [];
        }

        return [RuleErrorBuilder::message($message)->identifier($identifier)->tip($tip)->build()];
    }

    /**
     * Whether the Resource has a 'can' key anywhere in its code.
     */
    private function sendsCan(InClassNode $node): bool
    {
        return (new NodeFinder)->findFirst(
            $node->getOriginalNode()->stmts,
            fn (Node $n): bool => $n instanceof Node\ArrayItem
                && $n->key instanceof Node\Scalar\String_
                && $n->key->value === 'can',
        ) !== null;
    }
}
