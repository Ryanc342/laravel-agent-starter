<?php

namespace App\Actions\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Marks every unread notification of a user as read. It calls
 * MarkNotificationRead for each one instead of copying its check and write,
 * all in one transaction. With nothing unread it refuses, so the "Mark all as
 * read" button (can.mark_all_read) shows exactly when pressing it succeeds.
 */
final class MarkAllNotificationsRead
{
    public function __construct(private MarkNotificationRead $markNotificationRead) {}

    /**
     * @return int the number of notifications marked as read
     */
    public function handle(User $user): int
    {
        if (! $user->hasUnreadNotifications()) {
            throw ValidationException::withMessages([
                'notifications' => __('There are no unread notifications.'),
            ]);
        }

        return DB::transaction(function () use ($user): int {
            $notifications = Notification::query()->ownedBy($user)->unread()->get();

            $notifications->each(fn (Notification $notification) => $this->markNotificationRead->handle($notification));

            return $notifications->count();
        });
    }
}
