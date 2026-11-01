<?php

namespace App\Actions\Notifications;

use App\Models\Notification;
use Illuminate\Validation\ValidationException;

final class MarkNotificationRead
{
    public function handle(Notification $notification): void
    {
        if (! $notification->canBeMarkedRead()) {
            throw ValidationException::withMessages([
                'notification' => __('This notification is already read.'),
            ]);
        }

        $notification->markAsRead();
    }
}
