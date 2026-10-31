<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class CreateTask
{
    /**
     * Business rule: a user may have at most this many open tasks.
     */
    public const MAX_OPEN_TASKS = 100;

    /**
     * @param  array<string, mixed>  $data  validated input: title, due_on
     */
    public function handle(User $user, array $data): Task
    {
        if (Task::query()->ownedBy($user)->open()->count() >= self::MAX_OPEN_TASKS) {
            throw ValidationException::withMessages([
                'title' => __('You can have at most :max open tasks.', ['max' => self::MAX_OPEN_TASKS]),
            ]);
        }

        $task = new Task([
            'title' => $data['title'],
            'due_on' => $data['due_on'] ?? null,
            'status' => TaskStatus::Open,
        ]);

        $task->user()->associate($user);
        $task->save();

        return $task;
    }
}
