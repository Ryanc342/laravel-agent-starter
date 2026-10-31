<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property Carbon|null $due_on
 * @property TaskStatus $status
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'due_on', 'status', 'completed_at'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this task belongs to the user. Asks the ownedBy() scope, so the rule is written once.
     */
    public function isOwnedBy(User $user): bool
    {
        return self::query()->ownedBy($user)->whereKey($this->getKey())->exists();
    }

    /**
     * The status decides; the rule itself is TaskStatus::isOpen().
     */
    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    /**
     * Whether this task is overdue. Asks the overdue() scope, so the rule is written once.
     */
    public function isOverdue(): bool
    {
        return self::query()->overdue()->whereKey($this->getKey())->exists();
    }

    /**
     * Only open tasks can be completed.
     */
    public function canBeCompleted(): bool
    {
        return $this->isOpen();
    }

    /**
     * Only open tasks can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->isOpen();
    }

    /**
     * Tasks that belong to the user.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ownedBy(Builder $query, User $user): void
    {
        $query->whereBelongsTo($user);
    }

    /**
     * Tasks that still need work. TaskStatus decides which statuses count.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', TaskStatus::openValues());
    }

    /**
     * Open tasks whose due date is before today.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->open()
            ->whereNotNull('due_on')
            ->whereDate('due_on', '<', today()->toDateString());
    }

    /**
     * Open tasks first, then by due date (tasks without a date last), then newest first.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function listOrder(Builder $query): void
    {
        $openValues = TaskStatus::openValues();
        $placeholders = implode(', ', array_fill(0, count($openValues), '?'));

        $query->orderByRaw("case when status in ({$placeholders}) then 0 else 1 end", $openValues)
            ->orderByRaw('case when due_on is null then 1 else 0 end')
            ->orderBy('due_on')
            ->latest('id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'status' => TaskStatus::class,
            'completed_at' => 'datetime',
        ];
    }
}
