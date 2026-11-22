<?php

namespace App\Http\Resources;

use App\Models\Notification;
use App\Models\User;

/**
 * One notification as a page receives it.
 * can.mark_read makes the same checks as marking it read (the Policy and
 * Notification::canBeMarkedRead), so the button shows exactly when it succeeds.
 */
final class NotificationResource extends ResourceData
{
    public function __construct(
        public string $id,
        public string $message,
        public ?string $created_at_diff,
        public bool $is_unread,
        public NotificationCan $can,
    ) {}

    public static function from(Notification $record, ?User $viewer): self
    {
        return new self(
            id: $record->id,
            message: $record->message(),
            created_at_diff: $record->created_at?->diffForHumans(),
            is_unread: $record->isUnread(),
            can: new NotificationCan(
                mark_read: $viewer !== null && $viewer->can('markRead', $record) && $record->canBeMarkedRead(),
            ),
        );
    }
}
