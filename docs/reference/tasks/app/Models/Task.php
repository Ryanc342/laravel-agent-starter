<?php

namespace App\Models;

use App\Enums\TaskSort;
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
     * Whether the user has any overdue task. Asks the scopes, so the rule is written once.
     */
    public static function hasOverdueFor(User $user): bool
    {
        return self::query()->ownedBy($user)->overdue()->exists();
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
     * Tasks whose title contains the text. No text means no filter. A % or _ in the text is plain text.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function matching(Builder $query, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $query->where('title', 'like', '%'.addcslashes($search, '\\%_').'%');
    }

    /**
     * Tasks in any of the statuses. No statuses means no filter.
     *
     * @param  Builder<self>  $query
     * @param  list<TaskStatus>  $statuses
     */
    #[Scope]
    protected function withStatuses(Builder $query, array $statuses): void
    {
        if ($statuses === []) {
            return;
        }

        $query->whereIn('status', array_map(fn (TaskStatus $status): string => $status->value, $statuses));
    }

    /**
     * Tasks due from the first day to the last day, both days included (Y-m-d). A missing end is open.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function dueBetween(Builder $query, ?string $from, ?string $to): void
    {
        if ($from !== null) {
            $query->whereDate('due_on', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('due_on', '<=', $to);
        }
    }

    /**
     * The order the user asked for; tasks without a due date come last either way, and equal rows
     * show the newest first. No sort asked means listOrder().
     *
     * @param  Builder<self>  $query
     * @param  'asc'|'desc'  $direction
     */
    #[Scope]
    protected function sortedBy(Builder $query, ?TaskSort $sort, string $direction): void
    {
        if ($sort === null) {
            $query->listOrder();

            return;
        }

        if ($sort === TaskSort::DueOn) {
            $query->orderByRaw('case when due_on is null then 1 else 0 end');
        }

        $query->orderBy($sort->value, $direction)->latest('id');
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
