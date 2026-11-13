<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\ImportTasks;
use App\Http\Requests\Tasks\ImportTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Bulk operation: import the tasks of an uploaded CSV file, all or none.
 */
class TaskImportController extends Controller
{
    public function store(ImportTaskRequest $request, ImportTasks $importTasks): RedirectResponse
    {
        Gate::authorize('import', Task::class);

        $imported = $importTasks->handle($request->user(), $request->rows());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice('Imported :count task.|Imported :count tasks.', $imported),
        ]);

        return back();
    }
}
