<?php

namespace App\Actions\Tasks;

use App\Models\Task;

final class DeleteTask
{
    public function handle(Task $task): void
    {
        $task->delete();
    }
}
