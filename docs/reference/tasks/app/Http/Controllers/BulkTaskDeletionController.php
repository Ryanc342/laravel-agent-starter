<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\DeleteTasks;
use App\Http\Requests\Tasks\BulkTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Bulk operation: delete the ticked tasks, or every task the list shows.
 */
class BulkTaskDeletionController extends Controller
{
    public function destroy(BulkTaskRequest $request, DeleteTasks $deleteTasks): RedirectResponse
    {
        Gate::authorize('deleteMany', Task::class);

        $tasks = Task::query()
            ->pickedBy($request->user(), $request->ids(), $request->search(), $request->statuses(), $request->dueFrom(), $request->dueTo())
            ->get();

        $tasks->each(fn (Task $task) => Gate::authorize('delete', $task));

        $deleted = $deleteTasks->handle($tasks);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count task deleted.|:count tasks deleted.', $deleted),
        ]);

        return back();
    }
}
