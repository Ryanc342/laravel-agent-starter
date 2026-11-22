<?php

namespace App\Http\Controllers;

use App\Actions\UserlessOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LogicException;

/**
 * The entry point for calls another system sends here (part of the
 * outside-service part). A route registered with
 * Route::webhook() names the service and the operation. The signature must
 * match the body signed with the service's webhook_secret
 * (config/services.php; HMAC-SHA256, in the header signature_header, default
 * X-Signature-256, written as sha256=<hex> or <hex>). Only then the system
 * runs the operation with the call's data as its input.
 */
final class WebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $route = $request->route();
        $class = $route?->getAction('operation');
        $service = $route?->getAction('webhook_service');

        if (! is_string($class) || ! is_subclass_of($class, UserlessOperation::class) || ! is_string($service)) {
            throw new LogicException('A route of WebhookController names its service and a UserlessOperation with Route::webhook().');
        }

        $secret = config("services.{$service}.webhook_secret");
        $header = config("services.{$service}.signature_header", 'X-Signature-256');

        if (! is_string($secret) || $secret === '' || ! is_string($header)) {
            throw new LogicException("config/services.php has no webhook_secret for the outside service [{$service}].");
        }

        $given = Str::after((string) $request->headers->get($header), 'sha256=');

        if (! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $given)) {
            abort(403);
        }

        app($class)->runBySystem(null, $request->all());

        return response()->json(['received' => true]);
    }
}
