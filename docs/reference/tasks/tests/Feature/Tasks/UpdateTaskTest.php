<?php

use App\Actions\Tasks\UpdateTask;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('an open task can be edited', function () {
    $task = Task::factory()->create(['title' => 'Original']);

    app(UpdateTask::class)->handle($task, ['title' => 'Changed', 'due_on' => '2026-12-01']);

    $task->refresh();
    expect($task->title)->toBe('Changed')
        ->and($task->due_on?->toDateString())->toBe('2026-12-01');
});

test('a completed task cannot be edited', function () {
    $task = Task::factory()->completed()->create(['title' => 'Original']);

    expect(fn () => app(UpdateTask::class)->handle($task, ['title' => 'Changed']))
        ->toThrow(ValidationException::class);

    expect($task->refresh()->title)->toBe('Original');
});
