<?php

use App\Actions\Tasks\CompleteTask;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('an open task can be completed', function () {
    $task = Task::factory()->create();

    app(CompleteTask::class)->handle($task);

    expect($task->refresh()->status)->toBe(TaskStatus::Done)
        ->and($task->completed_at)->not->toBeNull();
});

test('a completed task cannot be completed again', function () {
    $task = Task::factory()->completed()->create(['completed_at' => '2026-01-01 10:00:00']);

    expect(fn () => app(CompleteTask::class)->handle($task))
        ->toThrow(ValidationException::class);

    expect($task->refresh()->completed_at?->toDateTimeString())->toBe('2026-01-01 10:00:00');
});
