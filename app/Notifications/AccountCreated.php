<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Sent by the CreateUser Action. Shown on the notifications page only.
 */
class AccountCreated extends Notification
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
            'message' => __('Welcome to :app. Your account is ready.', ['app' => config('app.name')]),
        ];
    }
}
