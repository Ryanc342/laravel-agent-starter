<?php

namespace App\Actions\Notifications;

use App\Actions\After;
use App\Actions\Operation;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Marks every unread notification of a user as read. It runs
 * MarkNotificationRead for each one instead of copying its check and write;
 * the part puts all of them in one transaction. With nothing unread it
 * refuses, and the same refusal hides the "Mark all as read" button.
 */
final class MarkAllNotificationsRead extends Operation
{
    public function __construct(private MarkNotificationRead $markNotificationRead) {}

    public function ability(): string
    {
        return 'markAllRead';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function refusal(User $user, ?Model $subject): ?array
    {
        return $user->hasUnreadNotifications()
            ? null
            : ['notifications' => __('There are no unread notifications.')];
    }

    /**
     * @return int the number of notifications marked as read
     */
    protected function perform(User $user, ?Model $subject, array $input): int
    {
        $marked = 0;

        // In batches: a user's unread notifications grow with use.
        Notification::query()->ownedBy($user)->unread()->chunkById(100, function (Collection $notifications) use ($user, &$marked): void {
            foreach ($notifications as $notification) {
                $this->markNotificationRead->run($user, $notification);
                $marked++;
            }
        });

        return $marked;
    }

    public function after(mixed $result): After
    {
        return After::back(trans_choice(':count notification marked as read.|:count notifications marked as read.', (int) $result));
    }
}
