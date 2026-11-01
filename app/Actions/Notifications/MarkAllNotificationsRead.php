<?php

namespace App\Actions\Notifications;

use App\Models\Notification;
use App\Models\User;

/**
 * Marks every unread notification of a user as read. It calls
 * MarkNotificationRead for each one instead of copying its check and write.
 */
final class MarkAllNotificationsRead
{
    public function __construct(private MarkNotificationRead $markNotificationRead) {}

    /**
     * @return int the number of notifications marked as read
     */
    public function handle(User $user): int
    {
        $notifications = Notification::query()->ownedBy($user)->unread()->get();

        $notifications->each(fn (Notification $notification) => $this->markNotificationRead->handle($notification));

        return $notifications->count();
    }
}
