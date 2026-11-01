<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Completes every overdue task of a user. It calls CompleteTask for each one
 * instead of copying its checks and writes, all in one transaction. With
 * nothing overdue it refuses, so the button (can.complete_overdue) shows
 * exactly when pressing it succeeds.
 */
final class CompleteOverdueTasks
{
    public function __construct(private CompleteTask $completeTask) {}

    /**
     * @return int the number of tasks completed
     */
    public function handle(User $user): int
    {
        if (! Task::hasOverdueFor($user)) {
            throw ValidationException::withMessages([
                'tasks' => __('You have no overdue tasks.'),
            ]);
        }

        return DB::transaction(function () use ($user): int {
            $tasks = Task::query()->ownedBy($user)->overdue()->get();

            $tasks->each(fn (Task $task) => $this->completeTask->handle($task));

            return $tasks->count();
        });
    }
}
