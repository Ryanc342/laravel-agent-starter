<?php

use App\Http\Controllers\NotFoundController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\OnlyInLocalEnvironment;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome/index')->name('home');

// A development aid: the design's tokens, components and pattern demos on one page.
Route::inertia('style', 'style/index')
    ->middleware(OnlyInLocalEnvironment::class)
    ->name('style');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard/index')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/notifications.php';

// Whatever matches no route is a 404 that runs the web middleware, so the session is read and the
// error page knows a signed-in user. Laravel always matches a fallback last; registering it once the
// app has booted also puts it last in the route list, after the framework's own `storage/{path}`; the
// route is outside this file's web group by then, so it asks for the group itself. The 404 page is
// rendered by the exception handler with what it needs, so the Inertia middleware (version check, shared
// props) stays out, as it did when no route matched.
app()->booted(fn () => Route::fallback(NotFoundController::class)
    ->middleware('web')
    ->withoutMiddleware(HandleInertiaRequests::class)
    ->name('fallback'));
