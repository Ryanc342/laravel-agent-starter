<?php

namespace App\Http\Middleware;

use App\Actions\Notifications\MarkAllNotificationsRead;
use App\Enums\PerPage;
use App\Http\OperationGate;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\NotificationSummary;
use App\Http\Resources\NotificationSummaryCan;
use App\Http\Resources\SharedAuth;
use App\Http\Resources\Today;
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
            // Whether someone is signed in, for the pages a guest sees too (the error page gets the same flag).
            'signedIn' => $user !== null,
            'auth' => new SharedAuth($user === null ? null : UserResource::from($user, $user)),
            // The notification bell in the top bar (components/notifications/notification-menu.tsx)
            // and the notifications page read these.
            'notificationSummary' => fn (): ?NotificationSummary => $user === null ? null : new NotificationSummary(
                unread: Notification::query()->ownedBy($user)->unread()->count(),
                can: new NotificationSummaryCan(
                    mark_all_read: OperationGate::allows($user, MarkAllNotificationsRead::class),
                ),
            ),
            // Sent only when the bell or the dashboard block asks for it (router.reload({ only: ['latestNotifications'] })).
            'latestNotifications' => Inertia::optional(fn (): array => $user === null
                ? []
                : Notification::query()->ownedBy($user)->listOrder()->limit(5)->get()
                    ->map(fn (Notification $notification): NotificationResource => NotificationResource::from($notification, $user))
                    ->all()),
            // Today in the app's timezone, for the calendar's and the date picker's today mark and the
            // footer's year (resources/js/hooks/use-today.ts): React never reads the clock.
            'today' => new Today(
                date: now(config('app.timezone'))->toDateString(),
                year: now(config('app.timezone'))->year,
            ),
            // The rows-per-page choices under every paginated list (components/shared/pagination.tsx).
            'perPageOptions' => PerPage::values(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
