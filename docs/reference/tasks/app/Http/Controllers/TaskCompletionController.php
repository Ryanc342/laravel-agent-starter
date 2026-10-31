<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\CompleteTask;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Non-CRUD operation: complete one task.
 */
class TaskCompletionController extends Controller
{
    public function store(Task $task, CompleteTask $completeTask): RedirectResponse
    {
        Gate::authorize('complete', $task);

        $completeTask->handle($task);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task completed.')]);

        return to_route('tasks.index');
    }
}
