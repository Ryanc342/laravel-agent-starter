<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Webauthn\Exception\WebauthnException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // One Inertia page (resources/js/pages/errors/show.tsx) for 403, 404, 500 and
        // 503, so a failing page looks like the rest of the app. JSON requests keep
        // their JSON, and while debugging a 500 keeps Laravel's page with the exception.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();

            if (! in_array($status, [403, 404, 500, 503], true)
                || $response instanceof JsonResponse
                || ($status === 500 && config('app.debug'))) {
                return $response;
            }

            return Inertia::render('errors/show', [
                'status' => $status,
                'signedIn' => $request->user() !== null,
            ])->toResponse($request)->setStatusCode($status);
        });

        // A passkey the WebAuthn library rejects (a wrong signature, for example) is
        // a refused input, not a server error.
        $exceptions->map(WebauthnException::class, fn () => ValidationException::withMessages([
            'credential' => __('This passkey could not be verified. Please try again.'),
        ]));
    })->create();
