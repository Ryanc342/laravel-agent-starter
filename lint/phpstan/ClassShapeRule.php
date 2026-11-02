<?php

namespace Lint\PHPStan;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeCombinator;
use ReflectionMethod;

/**
 * Principles 3, 5, 6 and 7 in docs/golden-principles.md: the folder a class is
 * in decides its shape.
 *
 * - An Action is final, has handle() and no other public method, and
 *   handle() takes no bool (a mode switch is two Actions).
 * - No events, listeners or observers (app/Events, app/Listeners, app/Observers,
 *   #[ObservedBy] or $dispatchesEvents on a model).
 * - A Resource never sends $this->resource whole (toArray(), getAttributes(),
 *   an (array) cast and the like), nor a relation without its own Resource.
 * - An Action sits in app/Actions/{Domain}/; app/Http holds only controllers,
 *   FormRequests, Resources, middleware and the Fortify adapters.
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
     * The folders app/Http may hold; a controller anywhere else would escape
     * the controller rules.
     */
    private const HTTP_FOLDERS = ['Controllers', 'Requests', 'Resources', 'Middleware', 'Fortify'];

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

        foreach (['App\\Events\\', 'App\\Listeners\\', 'App\\Observers\\'] as $namespace) {
            if (str_starts_with($name, $namespace)) {
                $errors[] = $this->error(
                    "{$name} is an event, listener or observer",
                    'ezdev.noEvents',
                    'Do the work in the Action, and call another Action from it when a second step is needed. Events and listeners hide who does what. See docs/rules/actions.md.',
                );
            }
        }

        if (str_starts_with($name, 'App\\Models\\') && $this->usesModelEvents($class)) {
            $errors[] = $this->error(
                "Model {$name} uses #[ObservedBy] or \$dispatchesEvents",
                'ezdev.noEvents',
                'Do the work in the Action, and call another Action from it when a second step is needed. Events and listeners hide who does what. See docs/rules/actions.md.',
            );
        }

        if (str_starts_with($name, 'App\\Http\\Resources\\')) {
            foreach ([...$this->wholeResourceReads($node), ...$this->rawRelations($node)] as $read) {
                $errors[] = $this->error(
                    "Resource {$name} sends the whole model ({$read})",
                    'ezdev.resourceFields',
                    'A Resource names every field it sends to React, so nothing private leaks and the TypeScript type can match it. See docs/rules/frontend.md.',
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

        if (str_starts_with($name, 'App\\Http\\') && ! in_array(explode('\\', $name)[2] ?? '', self::HTTP_FOLDERS, true)) {
            $errors[] = $this->error(
                "{$name} is in app/Http but not in ".implode(', ', self::HTTP_FOLDERS),
                'ezdev.httpFolders',
                'A controller goes in app/Http/Controllers, a FormRequest in app/Http/Requests, a Resource in app/Http/Resources. See ARCHITECTURE.md.',
            );
        }

        if (str_starts_with($name, 'App\\Actions\\') && substr_count($name, '\\') !== 3) {
            $errors[] = $this->error(
                "Action {$name} is not in app/Actions/{Domain}/",
                'ezdev.actionFolder',
                'Put the Action in app/Actions/{Domain}/{Verb}{Noun}.php, one folder per business domain (app/Actions/Quotes/SendQuote.php). See docs/rules/actions.md.',
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

            foreach ($this->boolParameters($class) as $parameter) {
                $errors[] = $this->error(
                    "Action {$name}::handle() takes the bool parameter \${$parameter}",
                    'ezdev.actionModeSwitch',
                    'A bool that changes what the Action does is two behaviours: write two Actions (for example ArchiveTask and RestoreTask). See docs/rules/actions.md.',
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

    /**
     * Names of handle()'s parameters typed bool: natively (also ?bool, true,
     * false or a union with bool) or in the docblock (@param bool $force).
     *
     * @return list<string>
     */
    private function boolParameters(ClassReflection $class): array
    {
        if (! $class->hasNativeMethod('handle')) {
            return [];
        }

        $names = [];

        foreach ($class->getNativeReflection()->getMethod('handle')->getParameters() as $parameter) {
            $types = array_map(
                fn (string $type): string => strtolower(ltrim($type, '?')),
                explode('|', (string) $parameter->getType()),
            );

            if (array_intersect($types, ['bool', 'true', 'false']) !== []) {
                $names[] = $parameter->getName();
            }
        }

        foreach ($class->getNativeMethod('handle')->getVariants() as $variant) {
            foreach ($variant->getParameters() as $parameter) {
                if (TypeCombinator::removeNull($parameter->getType())->isBoolean()->yes()) {
                    $names[] = $parameter->getName();
                }
            }
        }

        return array_values(array_unique($names));
    }

    private function usesModelEvents(ClassReflection $class): bool
    {
        $native = $class->getNativeReflection();

        foreach ($native->getAttributes() as $attribute) {
            if ($attribute->getName() === 'Illuminate\\Database\\Eloquent\\Attributes\\ObservedBy') {
                return true;
            }
        }

        return $native->hasProperty('dispatchesEvents')
            && $native->getProperty('dispatchesEvents')->getDeclaringClass()->getName() === $class->getName();
    }

    /**
     * Ways a Resource sends $this->resource whole: $this->resource->toArray(),
     * ->getAttributes() and the like, (array) $this->resource, get_object_vars().
     *
     * @return list<string>
     */
    private function wholeResourceReads(InClassNode $node): array
    {
        $isResource = fn (Node $n): bool => $n instanceof Node\Expr\PropertyFetch
            && $n->var instanceof Node\Expr\Variable && $n->var->name === 'this'
            && $n->name instanceof Node\Identifier && $n->name->toString() === 'resource';
        $whole = ['toArray', 'attributesToArray', 'relationsToArray', 'getAttributes', 'getOriginal', 'getRawOriginal', 'toJson', 'jsonSerialize'];
        $reads = [];

        foreach ((new NodeFinder)->find($node->getOriginalNode()->stmts, fn (Node $n): bool => true) as $n) {
            if ($n instanceof Node\Expr\MethodCall && $isResource($n->var) && $n->name instanceof Node\Identifier && in_array($n->name->toString(), $whole, true)) {
                $reads[] = "\$this->resource->{$n->name->toString()}()";
            } elseif ($n instanceof Node\Expr\Cast\Array_ && $isResource($n->expr)) {
                $reads[] = '(array) $this->resource';
            } elseif ($n instanceof Node\Expr\FuncCall && $n->name instanceof Node\Name && $n->name->toString() === 'get_object_vars'
                && isset($n->getArgs()[0]) && $isResource($n->getArgs()[0]->value)) {
                $reads[] = 'get_object_vars($this->resource)';
            }
        }

        return $reads;
    }

    /**
     * Relations a Resource sends as models: $this->whenLoaded('lines') that is
     * not wrapped in LineResource::collection(...) or LineResource::make(...).
     *
     * @return list<string>
     */
    private function rawRelations(InClassNode $node): array
    {
        $finder = new NodeFinder;
        $stmts = $node->getOriginalNode()->stmts;
        $wrapped = [];

        $isResourceCall = fn (Node $n): bool => ($n instanceof Node\Expr\StaticCall
                && $n->class instanceof Node\Name && str_ends_with($n->class->toString(), 'Resource')
                && $n->name instanceof Node\Identifier && in_array($n->name->toLowerString(), ['make', 'collection'], true))
            || ($n instanceof Node\Expr\New_ && $n->class instanceof Node\Name && str_ends_with($n->class->toString(), 'Resource'));

        foreach ($finder->find($stmts, $isResourceCall) as $call) {
            foreach ($finder->find([$call], fn (Node $n): bool => $this->isWhenLoaded($n)) as $inside) {
                $wrapped[spl_object_id($inside)] = true;
            }
        }

        $raw = [];

        foreach ($finder->find($stmts, fn (Node $n): bool => $this->isWhenLoaded($n)) as $call) {
            if (! isset($wrapped[spl_object_id($call)])) {
                $raw[] = '$this->whenLoaded() without a Resource';
            }
        }

        return $raw;
    }

    private function isWhenLoaded(Node $n): bool
    {
        return $n instanceof Node\Expr\MethodCall
            && $n->var instanceof Node\Expr\Variable && $n->var->name === 'this'
            && $n->name instanceof Node\Identifier && $n->name->toString() === 'whenLoaded'
            && count($n->getArgs()) === 1;
    }

    private function error(string $message, string $identifier, string $tip): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)->identifier($identifier)->tip($tip)->build();
    }
}
