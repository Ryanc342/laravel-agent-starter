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
     * Show the user's notifications, newest first, 20 per page.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Notification::class);

        $notifications = Notification::query()
            ->ownedBy($request->user())
            ->listOrder()
            ->paginate(20);

        // can.mark_all_read is shared with every page (notificationSummary in HandleInertiaRequests).
        return Inertia::render('notifications/index', [
            'notifications' => NotificationResource::collection($notifications)->response($request)->getData(true),
        ]);
    }
}
