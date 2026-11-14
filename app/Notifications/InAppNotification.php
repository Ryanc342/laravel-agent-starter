<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * A message shown on the notifications page only: stored in the database,
 * never mailed. Each notification adds its own toArray().
 *
 * A notification stores its English sentence and its values, not a finished
 * sentence, so the reader sees it in their own language (Notification::message()
 * translates it when shown). Build the stored data with sentence().
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
     * What toArray() stores: the English sentence, written once, and the values
     * its :placeholders take. Its Chinese is in lang/zh_CN.json under the same
     * English text (docs/rules/translations.md).
     *
     * @param  array<string, string|int>  $values
     * @return array{message: string, values: array<string, string|int>}
     */
    protected function sentence(string $english, array $values = []): array
    {
        return ['message' => $english, 'values' => $values];
    }
}
