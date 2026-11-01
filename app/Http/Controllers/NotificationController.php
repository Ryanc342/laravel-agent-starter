<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Show the user's 50 newest notifications.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Notification::class);

        $user = $request->user();

        $notifications = Notification::query()
            ->ownedBy($user)
            ->listOrder()
            ->take(50)
            ->get();

        return Inertia::render('notifications/index', [
            'notifications' => NotificationResource::collection($notifications)->response($request)->getData(true),
            'can' => [
                'mark_all_read' => $user->can('markAllRead', Notification::class) && $user->hasUnreadNotifications(),
            ],
        ]);
    }
}
