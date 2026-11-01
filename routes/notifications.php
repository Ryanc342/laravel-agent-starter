<?php

use App\Http\Controllers\AllNotificationsReadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationReadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Fixed paths must be registered before the {notification} wildcard routes.
    Route::post('notifications/read', [AllNotificationsReadController::class, 'store'])->name('notifications.read-all.store');

    Route::post('notifications/{notification}/read', [NotificationReadController::class, 'store'])->name('notifications.read.store');
});
