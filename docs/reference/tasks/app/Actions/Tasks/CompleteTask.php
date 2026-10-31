<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Validation\ValidationException;

final class CompleteTask
{
    public function handle(Task $task): Task
    {
        if (! $task->canBeCompleted()) {
            throw ValidationException::withMessages([
                'task' => __('This task is already completed.'),
            ]);
        }

        $task->update([
            'status' => TaskStatus::Done,
            'completed_at' => now(),
        ]);

        return $task;
    }
}
