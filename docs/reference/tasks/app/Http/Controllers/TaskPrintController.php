<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The print page of one task: the sheet the browser prints, with its "Print"
 * and "Download PDF" buttons. A task that is not the user's is a 403 here and
 * on the PDF.
 */
class TaskPrintController extends Controller
{
    public function show(Request $request, Task $task): Response
    {
        Gate::authorize('view', $task);

        return Inertia::render('tasks/print', [
            'task' => TaskResource::make($task)->resolve($request),
        ]);
    }
}
