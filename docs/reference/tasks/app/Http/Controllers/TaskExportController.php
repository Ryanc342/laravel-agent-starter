<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tasks\IndexTaskRequest;
use App\Models\Task;
use App\Support\TaskCsv;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Non-CRUD operation: download the task list as a CSV file, as the user sees it
 * (the same search, sort and filters) and over every page.
 */
class TaskExportController extends Controller
{
    public function __invoke(IndexTaskRequest $request): StreamedResponse
    {
        Gate::authorize('viewAny', Task::class);

        $tasks = Task::query()
            ->ownedBy($request->user())
            ->narrowedBy($request->search(), $request->statuses(), $request->dueFrom(), $request->dueTo())
            ->sortedBy($request->sort(), $request->direction());

        return response()->streamDownload(
            fn () => TaskCsv::output($tasks->lazy()),
            'tasks.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
