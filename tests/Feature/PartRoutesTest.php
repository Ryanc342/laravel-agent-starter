<?php

use App\Actions\Operation;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\WebhookController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;

/*
| The test part: every route a part opens (Route::operation, Route::listing,
| Route::record, Route::download) is called by a guest and by a signed-in user, with keys
| that match no record. No feature has to write these checks again:
| - nobody ever gets a server error;
| - an operation that needs a user sends a guest to sign in;
| - a key in the URL that matches no record is a 404.
| A new route of a part is checked as soon as it exists.
*/

uses(RefreshDatabase::class);

/**
 * @return list<Route>
 */
function partRoutes(): array
{
    return array_values(array_filter(
        app('router')->getRoutes()->getRoutes(),
        fn (Route $route): bool => in_array($route->getControllerClass(), [OperationController::class, ListingController::class, RecordController::class, DownloadController::class], true),
    ));
}

/**
 * The route's address with every URL record replaced by a key that matches nothing.
 */
function withMissingRecords(Route $route): string
{
    return '/'.(string) preg_replace('/\{[^}]+\}/', '0', $route->uri());
}

test('a guest never gets a server error, and an operation that needs a user sends the guest to sign in', function () {
    $problems = [];

    foreach (partRoutes() as $route) {
        $method = $route->methods()[0];
        $response = $this->call($method, withMissingRecords($route));
        $status = $response->getStatusCode();
        $operation = $route->getAction('operation');

        if ($status >= 500) {
            $problems[] = "{$method} /{$route->uri()} answers a guest with {$status}";
        }

        if (is_string($operation) && is_subclass_of($operation, Operation::class)
            && ! $response->isRedirect(route('login')) && $status !== 401) {
            $problems[] = "{$method} /{$route->uri()} runs {$operation} for a guest ({$status})";
        }
    }

    expect($problems)->toBe([]);
});

test('for a signed-in user a key that matches no record is a 404, and nothing is a server error', function () {
    $this->actingAs(User::factory()->create());
    $problems = [];

    foreach (partRoutes() as $route) {
        $method = $route->methods()[0];
        $status = $this->call($method, withMissingRecords($route))->getStatusCode();

        if ($status >= 500) {
            $problems[] = "{$method} /{$route->uri()} answers {$status}";
        } elseif (str_contains($route->uri(), '{') && $status !== 404) {
            $problems[] = "{$method} /{$route->uri()} answers {$status} for a key that matches no record";
        }
    }

    expect($problems)->toBe([]);
});

test('the template\'s own parts open routes, so the checks above check something', function () {
    expect(partRoutes())->not->toBe([]);
});

test('every route of the app that changes data runs an operation, through Route::operation or Route::webhook', function () {
    $problems = [];

    foreach (app('router')->getRoutes()->getRoutes() as $route) {
        $controller = $route->getControllerClass();
        $changesData = array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']) !== [];

        if ($changesData && is_string($controller) && str_starts_with($controller, 'App\\')
            && ! in_array($controller, [OperationController::class, WebhookController::class], true)) {
            $problems[] = implode('|', $route->methods())." /{$route->uri()} runs {$controller}";
        }
    }

    expect($problems)->toBe([]);
});
