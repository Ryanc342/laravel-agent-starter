<?php

use App\Http\Controllers\NotFoundController;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome/index')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard/index')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/notifications.php';

// Whatever matches no route is a 404 that runs the web middleware (this file is in the web group), so
// the session is read and the error page knows a signed-in user. Laravel matches a fallback last, wherever
// it is in the list. The 404 page is rendered by the exception handler with what it needs, so the
// Inertia middleware (version check, shared props) stays out, as it did when no route matched.
Route::fallback(NotFoundController::class)
    ->withoutMiddleware(HandleInertiaRequests::class)
    ->name('fallback');
