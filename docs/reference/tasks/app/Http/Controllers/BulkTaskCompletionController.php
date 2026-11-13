<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\CompleteTasks;
use App\Http\Requests\Tasks\BulkTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Bulk operation: complete the ticked tasks, or every task the list shows.
 */
class BulkTaskCompletionController extends Controller
{
    public function store(BulkTaskRequest $request, CompleteTasks $completeTasks): RedirectResponse
    {
        Gate::authorize('completeMany', Task::class);

        $tasks = Task::query()
            ->pickedBy($request->user(), $request->ids(), $request->search(), $request->statuses(), $request->dueFrom(), $request->dueTo())
            ->get();

        $tasks->each(fn (Task $task) => Gate::authorize('complete', $task));

        $completed = $completeTasks->handle($tasks);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count task completed.|:count tasks completed.', $completed),
        ]);

        return back();
    }
}
