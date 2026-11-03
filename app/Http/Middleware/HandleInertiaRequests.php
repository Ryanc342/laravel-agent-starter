<?php

namespace App\Http\Middleware;

use App\Http\Resources\NotificationResource;
use App\Http\Resources\UserResource;
use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user === null ? null : UserResource::make($user)->resolve($request),
            ],
            // The notification bell in the top bar (components/notifications/notification-menu.tsx)
            // and the notifications page read these.
            'notificationSummary' => fn () => $user === null ? null : [
                'unread' => Notification::query()->ownedBy($user)->unread()->count(),
                'can' => [
                    'mark_all_read' => $user->can('markAllRead', Notification::class) && $user->hasUnreadNotifications(),
                ],
            ],
            // Sent only when the bell asks for it (router.reload({ only: ['latestNotifications'] })).
            'latestNotifications' => Inertia::optional(fn () => $user === null
                ? []
                : NotificationResource::collection(
                    Notification::query()->ownedBy($user)->listOrder()->limit(5)->get(),
                )->resolve($request)),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
