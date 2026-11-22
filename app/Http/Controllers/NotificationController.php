<?php

namespace App\Http\Controllers;

use App\Actions\Notifications\MarkAllNotificationsRead;
use App\Actions\Notifications\MarkNotificationRead;
use App\Enums\PerPage;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * The user's notifications, newest first, one page at a time.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Notification::class);

        $user = $request->user();
        $page = Notification::query()
            ->visibleTo($user)
            ->listOrder()
            ->paginate(PerPage::fromQuery($request->query('per_page'))->value)
            ->withQueryString()
            ->through(fn (Notification $notification): NotificationResource => NotificationResource::from($notification, $user));

        // JsonResource only wraps the page in Laravel's paginated shape: data, links, meta.
        return Inertia::render('notifications/index', [
            'notifications' => JsonResource::collection($page)->response($request)->getData(true),
        ]);
    }

    /**
     * Mark one notification as read.
     */
    public function markRead(Notification $notification, MarkNotificationRead $markNotificationRead): RedirectResponse
    {
        Gate::authorize('markRead', $notification);

        $markNotificationRead->handle($notification);

        return back();
    }

    /**
     * Mark every unread notification of the user as read.
     */
    public function markAllRead(Request $request, MarkAllNotificationsRead $markAllNotificationsRead): RedirectResponse
    {
        Gate::authorize('markAllRead', Notification::class);

        $marked = $markAllNotificationsRead->handle($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(':count notification marked as read.|:count notifications marked as read.', $marked)]);

        return back();
    }
}
