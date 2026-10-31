<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Who may act on a task. State rules (for example "already completed") live in the Actions.
 */
class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function completeOverdue(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        return $task->isOwnedBy($user);
    }

    public function delete(User $user, Task $task): bool
    {
        return $task->isOwnedBy($user);
    }

    public function complete(User $user, Task $task): bool
    {
        return $task->isOwnedBy($user);
    }
}
