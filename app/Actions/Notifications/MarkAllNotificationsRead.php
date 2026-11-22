<?php

namespace App\Actions\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Marks every unread notification of a user as read. With nothing unread it
 * refuses; the same check (User::hasUnreadNotifications) hides the
 * "Mark all as read" button.
 */
final class MarkAllNotificationsRead
{
    /**
     * @return int the number of notifications marked as read
     */
    public function handle(User $user): int
    {
        return DB::transaction(function () use ($user): int {
            if (! $user->hasUnreadNotifications()) {
                throw ValidationException::withMessages([
                    'notifications' => __('There are no unread notifications.'),
                ]);
            }

            return Notification::query()->ownedBy($user)->unread()->update(['read_at' => now()]);
        });
    }
}
