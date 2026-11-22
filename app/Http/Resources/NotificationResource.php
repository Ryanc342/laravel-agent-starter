<?php

namespace App\Http\Resources;

use App\Actions\Notifications\MarkNotificationRead;
use App\Http\OperationGate;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One notification as a page receives it.
 * Every flag and every can.* calls an existing owner; none writes a condition.
 */
#[TypeScript]
final class NotificationResource extends ResourceData
{
    public function __construct(
        public string $id,
        public string $message,
        public ?string $created_at_diff,
        public bool $is_unread,
        public NotificationCan $can,
    ) {}

    public static function from(Model $record, ?User $viewer): static
    {
        assert($record instanceof Notification);

        return new self(
            id: $record->id,
            message: $record->message(),
            created_at_diff: $record->created_at?->diffForHumans(),
            is_unread: $record->isUnread(),
            can: new NotificationCan(
                mark_read: $viewer !== null && OperationGate::allows($viewer, MarkNotificationRead::class, $record),
            ),
        );
    }
}
