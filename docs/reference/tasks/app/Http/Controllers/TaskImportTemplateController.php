<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Support\TaskCsv;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Non-CRUD operation: download the CSV template for the import (the column names only).
 */
class TaskImportTemplateController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        Gate::authorize('import', Task::class);

        return response()->streamDownload(
            fn () => TaskCsv::outputTemplate(),
            'tasks-template.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
