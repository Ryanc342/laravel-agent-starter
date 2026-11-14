<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Enums\PerPage;
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
            // The language of this request (Locale::resolve) and, for Chinese, the sentences
            // React translates with t() (lib/i18n.ts). English needs none: a sentence is its own
            // English text. Sent once per page load and again when the language changes.
            'locale' => Locale::current()->value,
            'locales' => Locale::options(),
            'translations' => Inertia::once(fn (): array => Locale::current()->sentences())->as('translations-'.Locale::current()->value),
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
            // Sent only when the bell or the dashboard block asks for it (router.reload({ only: ['latestNotifications'] })).
            'latestNotifications' => Inertia::optional(fn () => $user === null
                ? []
                : NotificationResource::collection(
                    Notification::query()->ownedBy($user)->listOrder()->limit(5)->get(),
                )->resolve($request)),
            // Today in the app's timezone, for the calendar's and the date picker's today mark and the
            // footer's year (resources/js/hooks/use-today.ts): React never reads the clock.
            'today' => [
                'date' => now(config('app.timezone'))->toDateString(),
                'year' => now(config('app.timezone'))->year,
            ],
            // The rows-per-page choices under every paginated list (components/shared/pagination.tsx).
            'perPageOptions' => PerPage::values(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
