<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * A message shown on the notifications page only: stored in the database,
 * never mailed. Each notification adds its own toArray().
 */
abstract class InAppNotification extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }
}
