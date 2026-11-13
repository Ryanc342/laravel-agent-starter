<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\IndexNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Show the user's notifications, newest first, with the rows per page the user picked (20 by default).
     */
    public function index(IndexNotificationRequest $request): Response
    {
        Gate::authorize('viewAny', Notification::class);

        $notifications = Notification::query()
            ->ownedBy($request->user())
            ->listOrder()
            ->paginate($request->perPage())
            ->withQueryString();

        // can.mark_all_read is shared with every page (notificationSummary in HandleInertiaRequests).
        return Inertia::render('notifications/index', [
            'notifications' => NotificationResource::collection($notifications)->response($request)->getData(true),
        ]);
    }
}
