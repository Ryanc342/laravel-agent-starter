<?php

use App\Actions\Notifications\MarkAllNotificationsRead;
use App\Actions\Notifications\MarkNotificationRead;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::listing('notifications', Notification::class, NotificationResource::class, 'notifications/index', 'notifications', ['listOrder'])->name('notifications.index');

    // Fixed paths must be registered before the {notification} wildcard routes.
    Route::operation('notifications/read', MarkAllNotificationsRead::class)->name('notifications.read-all.store');

    Route::operation('notifications/{notification}/read', MarkNotificationRead::class)->name('notifications.read.store');
});
