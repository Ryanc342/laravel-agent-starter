<?php

use App\Http\Controllers\OverdueTaskCompletionController;
use App\Http\Controllers\TaskCompletionController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');

    // Fixed paths must be registered before the {task} wildcard routes.
    Route::post('tasks/overdue/completion', [OverdueTaskCompletionController::class, 'store'])->name('tasks.overdue-completion.store');

    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('tasks/{task}/completion', [TaskCompletionController::class, 'store'])->name('tasks.completion.store');
});
