<?php

namespace App\Actions\Notifications;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Marks one notification as read. The row is read again with a lock, so the
 * check sees what is stored now.
 */
final class MarkNotificationRead
{
    public function handle(Notification $notification): void
    {
        DB::transaction(function () use ($notification): void {
            $locked = Notification::query()->whereKey($notification->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->canBeMarkedRead()) {
                throw ValidationException::withMessages([
                    'notification' => __('This notification is already read.'),
                ]);
            }

            $locked->markAsRead();
        });
    }
}
