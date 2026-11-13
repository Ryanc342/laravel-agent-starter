<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Deletes the tasks of a bulk selection. It calls DeleteTask for each one
 * instead of copying its writes, all in one transaction. With no task it
 * refuses.
 */
final class DeleteTasks
{
    public function __construct(private DeleteTask $deleteTask) {}

    /**
     * @param  Collection<int, Task>  $tasks
     * @return int the number of tasks deleted
     */
    public function handle(Collection $tasks): int
    {
        if ($tasks->isEmpty()) {
            throw ValidationException::withMessages([
                'tasks' => __('Select at least one task.'),
            ]);
        }

        return DB::transaction(function () use ($tasks): int {
            $tasks->each(fn (Task $task) => $this->deleteTask->handle($task));

            return $tasks->count();
        });
    }
}
