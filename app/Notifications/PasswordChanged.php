<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Sent by the UpdatePassword Action. Shown on the notifications page only.
 */
class PasswordChanged extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => __('Your password was changed. If this was not you, reset your password now.'),
        ];
    }
}
