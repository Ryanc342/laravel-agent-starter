<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\CompleteOverdueTasks;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Bulk operation: complete all of the user's overdue tasks.
 */
class OverdueTaskCompletionController extends Controller
{
    public function store(Request $request, CompleteOverdueTasks $completeOverdueTasks): RedirectResponse
    {
        Gate::authorize('completeOverdue', Task::class);

        $completed = $completeOverdueTasks->handle($request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count task completed.|:count tasks completed.', $completed),
        ]);

        return to_route('tasks.index');
    }
}
