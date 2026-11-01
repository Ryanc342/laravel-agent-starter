<?php

namespace App\Models;

use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

/**
 * A message for one user, shown on the notifications page. Laravel writes the
 * rows when an Action calls $user->notify() (User::notifications() points here).
 *
 * "Unread" is decided by the framework's unread() scope. Ask it through
 * isUnread(); do not call the framework's unread() instance method, which
 * compares read_at a second time.
 *
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property array<string, mixed> $data
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Notification extends DatabaseNotification
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    /**
     * Whether this notification belongs to the user. Asks the ownedBy() scope, so the rule is written once.
     */
    public function isOwnedBy(User $user): bool
    {
        return self::query()->ownedBy($user)->whereKey($this->getKey())->exists();
    }

    /**
     * Whether this notification is unread. Asks the unread() scope, so the rule is written once.
     */
    public function isUnread(): bool
    {
        return self::query()->unread()->whereKey($this->getKey())->exists();
    }

    /**
     * Only unread notifications can be marked as read.
     */
    public function canBeMarkedRead(): bool
    {
        return $this->isUnread();
    }

    /**
     * The text the notification class stored in data.message (app/Notifications).
     */
    public function message(): string
    {
        $message = $this->data['message'] ?? '';

        return is_string($message) ? $message : '';
    }

    /**
     * Notifications that belong to the user.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ownedBy(Builder $query, User $user): void
    {
        $query->whereMorphedTo('notifiable', $user);
    }

    /**
     * The order notifications are listed in: newest first.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function listOrder(Builder $query): void
    {
        $query->latest();
    }
}
