<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * A message shown on the notifications page only: stored in the database,
 * never mailed. Each notification adds its own toArray().
 *
 * A notification stores its sentence and the values of its :placeholders;
 * Notification::message() fills them in when it is shown. Build the stored
 * data with sentence().
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

    /**
     * What toArray() stores: the sentence, written once, and the values its
     * :placeholders take.
     *
     * @param  array<string, string|int>  $values
     * @return array{message: string, values: array<string, string|int>}
     */
    protected function sentence(string $english, array $values = []): array
    {
        return ['message' => $english, 'values' => $values];
    }
}
