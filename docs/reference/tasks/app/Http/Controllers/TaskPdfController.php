<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/**
 * Non-CRUD operation: download one task as a PDF, made on the server by DOMPDF
 * (LARAVEL_PDF_DRIVER in .env) from resources/views/tasks/pdf.blade.php, which
 * shows the same values as the print page.
 */
class TaskPdfController extends Controller
{
    public function __invoke(Request $request, Task $task): PdfBuilder
    {
        Gate::authorize('view', $task);

        return Pdf::view('tasks.pdf', ['task' => TaskResource::make($task)->resolve($request)])
            ->format(Format::A4)
            ->download("task-{$task->id}.pdf");
    }
}
