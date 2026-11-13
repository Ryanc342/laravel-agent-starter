<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Completes the tasks of a bulk selection that can be completed; a task that
 * is already completed is skipped. It calls CompleteTask for each one instead
 * of copying its checks and writes, all in one transaction. With nothing to
 * complete it refuses.
 */
final class CompleteTasks
{
    public function __construct(private CompleteTask $completeTask) {}

    /**
     * @param  Collection<int, Task>  $tasks
     * @return int the number of tasks completed
     */
    public function handle(Collection $tasks): int
    {
        $completable = $tasks->filter(fn (Task $task): bool => $task->canBeCompleted());

        if ($completable->isEmpty()) {
            throw ValidationException::withMessages([
                'tasks' => __('None of the selected tasks can be completed.'),
            ]);
        }

        return DB::transaction(function () use ($completable): int {
            $completable->each(fn (Task $task) => $this->completeTask->handle($task));

            return $completable->count();
        });
    }
}
