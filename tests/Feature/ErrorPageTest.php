<?php

use App\Models\Notification;
use App\Models\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a page that does not exist returns 404 with the error page', function () {
    $this->get('/there-is-no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/show')
            ->where('status', 404)
            ->where('signedIn', false),
        );
});

test('a signed-in user who opens a URL that matches no route gets the error page that knows them', function () {
    $this->actingAs(User::factory()->create())
        ->get('/there-is-no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/show')
            ->where('status', 404)
            ->where('signedIn', true),
        );
});

// `actingAs` puts the user on the guard without a session, so the test above cannot tell whether a
// browser's session cookie is read. This one checks what makes it read: the fallback route runs the
// web middleware (cookies, session), which a URL that matches no route would otherwise skip.
test('a URL that matches no route runs the web middleware, so the session is read and a signed-in user is known', function () {
    $this->get('/there-is-no-such-page');

    $fallback = collect(Route::getRoutes()->getRoutes())->first(fn ($route) => $route->isFallback);

    expect($fallback)->not->toBeNull()
        ->and(Route::gatherRouteMiddleware($fallback))->toContain(StartSession::class)
        ->and(Route::gatherRouteMiddleware($fallback))->toContain(EncryptCookies::class);
});

test('a record that does not exist returns 404 with the error page, which knows who is signed in', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('notifications.read.store', ['notification' => 'no-such-notification']))
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/show')
            ->where('status', 404)
            ->where('signedIn', true),
        );
});

test('an action the user may not do returns 403 with the error page', function () {
    $someoneElses = Notification::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('notifications.read.store', $someoneElses))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/show')
            ->where('status', 403)
            ->where('signedIn', true),
        );
});

test('a server error returns 500 with the error page and no detail', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/boom', fn () => throw new RuntimeException('secret detail'));

    $this->get('/boom')
        ->assertStatus(500)
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/show')
            ->where('status', 500),
        )
        ->assertDontSee('secret detail');
});

test('while debugging, a server error keeps Laravel\'s own page so a developer sees the exception', function () {
    config(['app.debug' => true]);
    Route::middleware('web')->get('/boom', fn () => throw new RuntimeException('secret detail'));

    $this->get('/boom')
        ->assertStatus(500)
        ->assertSee('secret detail');
});

test('maintenance returns 503 with the error page', function () {
    Route::middleware('web')->get('/maintenance', fn () => abort(503));

    $this->get('/maintenance')
        ->assertStatus(503)
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/show')
            ->where('status', 503),
        );
});

test('an Inertia visit to a missing page gets the error page to show in place', function () {
    $this->get('/there-is-no-such-page', ['X-Inertia' => 'true'])
        ->assertNotFound()
        ->assertJsonPath('component', 'errors/show')
        ->assertJsonPath('props.status', 404);
});

test('a request that wants JSON still gets JSON, not the error page', function () {
    $this->getJson('/there-is-no-such-page')
        ->assertNotFound()
        ->assertJsonMissingPath('component');
});

test('an error that is not one of the four keeps its own response', function () {
    Route::middleware('web')->get('/teapot', fn () => abort(418));

    $this->get('/teapot')->assertStatus(418);
});

test('an expired page or too many tries sends the user back with a message, not an error page', function (int $status, string $message) {
    Route::middleware('web')->post('/refused', fn () => abort($status));

    $this->from('/dashboard')
        ->post('/refused')
        ->assertRedirect('/dashboard')
        ->assertSessionHas('inertia.flash_data', ['toast' => ['type' => 'error', 'message' => $message]]);
})->with([
    'expired page (419)' => [419, 'This page has expired. Please try again.'],
    'too many tries (429)' => [429, 'Too many attempts. Please try again later.'],
]);

test('a JSON request for a record that does not exist gets a plain 404 that does not name the model', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/json-user/{user}', fn (User $user) => $user->name);

    $this->getJson('/json-user/no-such-user')
        ->assertNotFound()
        ->assertExactJson(['message' => 'Not found.']);
});
