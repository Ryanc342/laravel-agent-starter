<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Fixed paths must be registered before the {notification} wildcard routes.
    Route::post('notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read-all.store');

    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read.store');
});
