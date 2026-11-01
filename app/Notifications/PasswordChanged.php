<?php

namespace App\Notifications;

/**
 * Sent by the UpdatePassword Action. Shown on the notifications page only.
 */
class PasswordChanged extends InAppNotification
{
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
