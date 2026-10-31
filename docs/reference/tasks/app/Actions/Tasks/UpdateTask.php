<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use Illuminate\Validation\ValidationException;

final class UpdateTask
{
    /**
     * @param  array<string, mixed>  $data  validated input: title, due_on
     */
    public function handle(Task $task, array $data): Task
    {
        if (! $task->canBeEdited()) {
            throw ValidationException::withMessages([
                'title' => __('A completed task cannot be edited.'),
            ]);
        }

        $task->update([
            'title' => $data['title'],
            'due_on' => $data['due_on'] ?? null,
        ]);

        return $task;
    }
}
