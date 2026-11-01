<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

/**
 * Who may act on a notification. State rules (for example "already read") live in the Actions.
 */
class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function markAllRead(User $user): bool
    {
        return true;
    }

    public function markRead(User $user, Notification $notification): bool
    {
        return $notification->isOwnedBy($user);
    }
}
