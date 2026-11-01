<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome/index')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard/index')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/notifications.php';
