<?php

namespace Lint\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use ReflectionMethod;

/**
 * Principles 3, 5, 6 and 7 in docs/golden-principles.md: the folder a class is
 * in decides its shape.
 *
 * - An Action is final, has handle() and no other public method.
 * - A controller has only the standard methods.
 * - A FormRequest has no authorize().
 * - app/Enums holds enums only (traits: TraitPlacementRule).
 * - No interfaces, Service, Repository or DTO classes.
 * - Controllers, FormRequests, Resources and Policies end in their suffix.
 *
 * @implements Rule<InClassNode>
 */
final class ClassShapeRule implements Rule
{
    private const CONTROLLER_METHODS = ['__construct', '__invoke', 'index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

    /**
     * Namespace => the suffix every class in it ends in.
     */
    private const SUFFIXES = [
        'App\\Http\\Controllers\\' => 'Controller',
        'App\\Http\\Requests\\' => 'Request',
        'App\\Http\\Resources\\' => 'Resource',
        'App\\Policies\\' => 'Policy',
    ];

    /**
     * Class name ending => what to write instead.
     */
    private const EXTRA_LAYERS = [
        'service' => 'write an Action instead',
        'repository' => 'query through model scopes instead',
        'dto' => 'pass validated arrays, models and enums instead',
    ];

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        $name = $class->getName();

        if (! str_starts_with($name, 'App\\')) {
            return [];
        }

        $short = substr($name, (int) strrpos($name, '\\') + 1);
        $errors = [];

        if ($class->isInterface()) {
            $errors[] = $this->error(
                "{$name} is an interface",
                'ezdev.noInterface',
                'Use the concrete class; add an interface only when the user asks for one.',
            );
        }

        foreach (self::EXTRA_LAYERS as $ending => $instead) {
            if (str_ends_with(strtolower($short), $ending)) {
                $errors[] = $this->error(
                    "{$name} adds a layer the template does not use",
                    'ezdev.extraLayer',
                    ucfirst($instead).'. See ARCHITECTURE.md.',
                );
            }
        }

        foreach (self::SUFFIXES as $namespace => $suffix) {
            if (str_starts_with($name, $namespace) && ! str_ends_with($short, $suffix)) {
                $errors[] = $this->error(
                    "{$name} does not end in {$suffix}",
                    'ezdev.classSuffix',
                    "Rename it to {$short}{$suffix}, or move it to the folder it belongs in. See ARCHITECTURE.md.",
                );
            }
        }

        if (str_starts_with($name, 'App\\Enums\\') && ! $class->isEnum()) {
            $errors[] = $this->error(
                "{$name} is in app/Enums but is not an enum",
                'ezdev.enumsOnly',
                'app/Enums holds enums only. Put other code in the layer it belongs to. See ARCHITECTURE.md.',
            );
        }

        if (str_starts_with($name, 'App\\Actions\\') && $class->isClass()) {
            if (! $class->isFinalByKeyword()) {
                $errors[] = $this->error(
                    "Action {$name} is not final",
                    'ezdev.actionShape',
                    'Declare it as final class. To share steps, call one Action from another. See docs/rules/actions.md.',
                );
            }

            if (! $class->hasNativeMethod('handle')) {
                $errors[] = $this->error(
                    "Action {$name} has no handle() method",
                    'ezdev.actionShape',
                    'An Action does its one operation in handle(). See docs/rules/actions.md.',
                );
            }

            foreach ($this->ownPublicMethods($class) as $method) {
                if (! in_array($method, ['__construct', 'handle'], true)) {
                    $errors[] = $this->error(
                        "Action {$name} has the public method {$method}()",
                        'ezdev.actionShape',
                        'One Action does one operation: make the method private, or give the operation its own Action. See docs/rules/actions.md.',
                    );
                }
            }
        }

        if (str_starts_with($name, 'App\\Http\\Controllers\\') && $class->isClass()) {
            foreach ($this->ownPublicMethods($class) as $method) {
                if (! in_array($method, self::CONTROLLER_METHODS, true)) {
                    $errors[] = $this->error(
                        "Controller {$name} has the public method {$method}()",
                        'ezdev.controllerMethods',
                        'A controller has only '.implode(', ', self::CONTROLLER_METHODS).'. Give any other operation its own controller, like NotificationReadController@store. See docs/rules/controllers.md.',
                    );
                }
            }
        }

        if (str_starts_with($name, 'App\\Http\\Requests\\') && $class->hasNativeMethod('authorize')) {
            $errors[] = $this->error(
                "FormRequest {$name} has authorize()",
                'ezdev.formRequestAuthorize',
                'Remove it: the controller asks the Policy with Gate::authorize(). See docs/rules/form-requests.md.',
            );
        }

        return $errors;
    }

    /**
     * Names of the public methods the class declares itself (methods from its
     * traits count, methods from its parents do not).
     *
     * @return list<string>
     */
    private function ownPublicMethods(ClassReflection $class): array
    {
        $names = [];

        foreach ($class->getNativeReflection()->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === $class->getName()) {
                $names[] = $method->getName();
            }
        }

        return $names;
    }

    private function error(string $message, string $identifier, string $tip): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)->identifier($identifier)->tip($tip)->build();
    }
}
