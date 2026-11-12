<?php

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
