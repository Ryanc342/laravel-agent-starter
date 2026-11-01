<?php

namespace App\Http\Controllers;

use App\Actions\Notifications\MarkAllNotificationsRead;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Bulk operation: mark all of the user's unread notifications as read.
 */
class AllNotificationsReadController extends Controller
{
    public function store(Request $request, MarkAllNotificationsRead $markAllNotificationsRead): RedirectResponse
    {
        Gate::authorize('markAllRead', Notification::class);

        $marked = $markAllNotificationsRead->handle($request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count notification marked as read.|:count notifications marked as read.', $marked),
        ]);

        return to_route('notifications.index');
    }
}
