<?php

namespace App\Http\Controllers;

use App\Actions\Notifications\MarkNotificationRead;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Non-CRUD operation: mark one notification as read.
 */
class NotificationReadController extends Controller
{
    public function store(Notification $notification, MarkNotificationRead $markNotificationRead): RedirectResponse
    {
        Gate::authorize('markRead', $notification);

        $markNotificationRead->handle($notification);

        return to_route('notifications.index');
    }
}
