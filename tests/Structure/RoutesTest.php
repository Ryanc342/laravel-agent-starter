<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

/*
| Principle 7 in docs/golden-principles.md: routes are named controllers, and
| no route hides another. The routes come from the booted app, so every way of
| registering one is seen.
*/

uses(TestCase::class);

/**
 * @return list<Route>
 */
function appRoutes(): array
{
    return array_values(Router::getRoutes()->getRoutes());
}

function routeFromRoutesFolder(Route $route): bool
{
    $uses = $route->getAction('uses');

    if ($uses instanceof Closure) {
        return str_starts_with((string) (new ReflectionFunction($uses))->getFileName(), base_path('routes').DIRECTORY_SEPARATOR);
    }

    $uses = is_string($uses) ? ltrim($uses, '\\') : '';

    return str_starts_with($uses, 'App\\') || str_starts_with($uses, 'Inertia\\');
}

test('no route in routes/ is a closure: it calls a controller (or Route::inertia for a page with no data)', function () {
    $closures = array_map(
        fn (Route $route): string => implode('|', $route->methods()).' '.$route->uri(),
        array_filter(appRoutes(), fn (Route $route): bool => $route->getAction('uses') instanceof Closure && routeFromRoutesFolder($route)),
    );

    expect(array_values($closures))->toBe([]);
});

test('every route of the app is named, so Wayfinder and route() can reach it', function () {
    $unnamed = array_map(
        fn (Route $route): string => implode('|', $route->methods()).' '.$route->uri(),
        array_filter(appRoutes(), fn (Route $route): bool => routeFromRoutesFolder($route) && $route->getName() === null),
    );

    expect(array_values($unnamed))->toBe([]);
});

test('a fixed path comes before a wildcard path that would catch it (quotes/create before quotes/{quote})', function () {
    $routes = appRoutes();
    $hidden = [];

    foreach ($routes as $later => $route) {
        $path = '/'.ltrim((string) preg_replace('/\{[^}]+\}/', 'x', $route->uri()), '/');

        foreach (array_slice($routes, 0, $later) as $earlier) {
            if ($earlier->uri() === $route->uri() || array_intersect($earlier->methods(), $route->methods()) === [] || $earlier->getDomain() !== $route->getDomain()) {
                continue;
            }

            if (! str_contains($earlier->uri(), '{') || preg_match($earlier->toSymfonyRoute()->compile()->getRegex(), $path) !== 1) {
                continue;
            }

            $hidden[] = implode('|', $route->methods())." {$route->uri()} is caught by {$earlier->uri()}, registered before it";
        }
    }

    expect($hidden)->toBe([]);
});
