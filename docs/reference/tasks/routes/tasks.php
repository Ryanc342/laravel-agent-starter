<?php

use App\Http\Controllers\BulkTaskCompletionController;
use App\Http\Controllers\BulkTaskDeletionController;
use App\Http\Controllers\OverdueTaskCompletionController;
use App\Http\Controllers\TaskCompletionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskExportController;
use App\Http\Controllers\TaskImportController;
use App\Http\Controllers\TaskImportTemplateController;
use App\Http\Controllers\TaskPdfController;
use App\Http\Controllers\TaskPrintController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');

    // Fixed paths must be registered before the {task} wildcard routes.
    Route::post('tasks/overdue/completion', [OverdueTaskCompletionController::class, 'store'])->name('tasks.overdue-completion.store');

    Route::get('tasks/export', TaskExportController::class)->name('tasks.export');
    Route::get('tasks/import/template', TaskImportTemplateController::class)->name('tasks.import.template');
    Route::post('tasks/import', [TaskImportController::class, 'store'])->name('tasks.import.store');
    Route::post('tasks/bulk/completion', [BulkTaskCompletionController::class, 'store'])->name('tasks.bulk-completion.store');
    Route::delete('tasks/bulk', [BulkTaskDeletionController::class, 'destroy'])->name('tasks.bulk-deletion.destroy');

    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('tasks/{task}/print', [TaskPrintController::class, 'show'])->name('tasks.print');
    Route::get('tasks/{task}/pdf', TaskPdfController::class)->name('tasks.pdf');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('tasks/{task}/completion', [TaskCompletionController::class, 'store'])->name('tasks.completion.store');
});
