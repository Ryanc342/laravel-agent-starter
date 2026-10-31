<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\User;

/**
 * Completes every overdue task of a user. It calls CompleteTask for each one
 * instead of copying its checks and writes.
 */
final class CompleteOverdueTasks
{
    public function __construct(private CompleteTask $completeTask) {}

    /**
     * @return int the number of tasks completed
     */
    public function handle(User $user): int
    {
        $tasks = Task::query()->ownedBy($user)->overdue()->get();

        $tasks->each(fn (Task $task) => $this->completeTask->handle($task));

        return $tasks->count();
    }
}
