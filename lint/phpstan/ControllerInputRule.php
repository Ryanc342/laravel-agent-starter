<?php

namespace Lint\PHPStan;

use Illuminate\Http\Request;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Principle 5 in docs/golden-principles.md: input is checked once, at the edge.
 * A controller reads input only through a FormRequest: $request->validated(),
 * $request->safe() or a method the FormRequest declares ($request->status()).
 *
 * On a request in a controller, only the methods Laravel's Request declares
 * for the request itself are allowed (user(), session(), route() and the like).
 * Every method that reads input (input(), all(), query(), boolean(), enum(),
 * has(), validationData(), ...), every property ($request->status,
 * $request->query), $request['field'], request('field') and the Request facade
 * are rejected. Methods from FormRequest, from app/ and from
 * packages (Fortify's ensureStateIsValid()) are allowed.
 *
 * @implements Rule<Node>
 */
final class ControllerInputRule implements Rule
{
    /**
     * Request methods that do not read input.
     */
    private const ALLOWED = [
        'user', 'session', 'hasSession', 'route', 'routeIs', 'ip', 'ips', 'userAgent', 'inertia',
        'expectsJson', 'wantsJson', 'ajax', 'pjax', 'prefetch', 'secure', 'isSecure', 'isJson',
        'accepts', 'acceptsJson', 'acceptsHtml', 'acceptsAnyContentType', 'prefers',
        'method', 'getMethod', 'isMethod', 'url', 'fullUrl', 'fullUrlIs', 'path', 'is', 'uri', 'root',
        'host', 'httpHost', 'getHost', 'getScheme', 'schemeAndHttpHost', 'segment', 'segments',
        'fingerprint', 'header', 'hasHeader', 'bearerToken', 'hasValidSignature', 'setUserResolver',
    ];

    /**
     * Methods that read the raw input although a FormRequest declares them.
     */
    private const DENIED = ['validationData'];

    private const TIP = 'Create app/Http/Requests/{Domain}/{Verb}{Model}Request.php with the rules, type-hint it in the controller method and call $request->validated(). To turn a value into an enum or a date, add a method to the FormRequest (for example $request->status()). A list page with filters gets a FormRequest too. See docs/rules/form-requests.md.';

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $scope->isInClass() || ! str_starts_with($scope->getClassReflection()->getName(), 'App\\Http\\Controllers\\')) {
            return [];
        }

        if (($node instanceof Expr\MethodCall || $node instanceof Expr\NullsafeMethodCall) && $node->name instanceof Identifier) {
            return $this->checkMethod($node->var, $node->name->toString(), $scope);
        }

        if (($node instanceof Expr\PropertyFetch || $node instanceof Expr\NullsafePropertyFetch) && $this->isRequest($node->var, $scope)) {
            $property = $node->name instanceof Identifier ? $node->name->toString() : '…';

            return [$this->error("A controller reads \$request->{$property}")];
        }

        if ($node instanceof Expr\ArrayDimFetch && $this->isRequest($node->var, $scope)) {
            return [$this->error('A controller reads $request[…]')];
        }

        if ($node instanceof Expr\StaticCall && $node->class instanceof Name
            && in_array($scope->resolveName($node->class), ['Illuminate\\Support\\Facades\\Request', 'Request'], true)) {
            return [$this->error('A controller uses the Request facade')];
        }

        if ($node instanceof Expr\FuncCall && $node->name instanceof Name
            && strtolower($node->name->toString()) === 'request' && $node->getArgs() !== []) {
            return [$this->error('A controller reads input with request(…)')];
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkMethod(Expr $var, string $method, Scope $scope): array
    {
        if (! $this->isRequest($var, $scope) || in_array($method, self::ALLOWED, true)) {
            return [];
        }

        if (in_array($method, self::DENIED, true)) {
            return [$this->error("A controller reads input with \$request->{$method}()")];
        }

        $type = $scope->getType($var);

        if ($type->hasMethod($method)->yes()) {
            $declaring = $type->getMethod($method, $scope)->getDeclaringClass()->getName();

            if (! in_array($declaring, [Request::class, 'Symfony\\Component\\HttpFoundation\\Request'], true)) {
                return [];
            }
        }

        return [$this->error("A controller reads input with \$request->{$method}()")];
    }

    private function isRequest(Expr $var, Scope $scope): bool
    {
        return (new ObjectType(Request::class))->isSuperTypeOf($scope->getType($var))->yes();
    }

    private function error(string $message): IdentifierRuleError
    {
        return RuleErrorBuilder::message("{$message}: controllers read input only from a FormRequest")
            ->identifier('ezdev.controllerInput')
            ->tip(self::TIP)
            ->build();
    }
}
