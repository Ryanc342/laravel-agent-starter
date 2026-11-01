<?php

namespace App\Notifications;

/**
 * Sent by the UpdateProfile Action. Shown on the notifications page only.
 */
class EmailChanged extends InAppNotification
{
    public function __construct(private string $email) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => __('Your email address was changed to :email.', ['email' => $this->email]),
        ];
    }
}
