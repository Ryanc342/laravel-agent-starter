<?php

namespace App\Notifications;

/**
 * Sent by the CreateUser Action. Shown on the notifications page only.
 */
class AccountCreated extends InAppNotification
{
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
