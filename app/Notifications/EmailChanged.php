<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Sent by the UpdateProfile Action. Shown on the notifications page only.
 */
class EmailChanged extends Notification
{
    public function __construct(private string $email) {}

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
            'message' => __('Your email address was changed to :email.', ['email' => $this->email]),
        ];
    }
}
