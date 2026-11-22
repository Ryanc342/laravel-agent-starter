<?php

namespace App\Actions\Notifications;

use App\Actions\Operation;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Marks one notification as read.
 */
final class MarkNotificationRead extends Operation
{
    public function ability(): string
    {
        return 'markRead';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function refusal(User $user, ?Model $subject): ?array
    {
        return $subject instanceof Notification && $subject->canBeMarkedRead()
            ? null
            : ['notification' => __('This notification is already read.')];
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        assert($subject instanceof Notification);
        $subject->markAsRead();

        return null;
    }
}
