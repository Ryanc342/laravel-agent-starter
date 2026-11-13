<?php

use App\Actions\Tasks\CompleteTasks;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('every open task of the selection is completed', function () {
    $tasks = Task::factory()->count(3)->create();

    $completed = app(CompleteTasks::class)->handle($tasks);

    expect($completed)->toBe(3);
    $tasks->each(fn (Task $task) => expect($task->refresh()->status)->toBe(TaskStatus::Done)
        ->and($task->completed_at)->not->toBeNull());
});

test('a task that is already completed is skipped and keeps its completion time', function () {
    $open = Task::factory()->create();
    $done = Task::factory()->completed()->create(['completed_at' => '2026-01-01 10:00:00']);

    $completed = app(CompleteTasks::class)->handle(new Collection([$open, $done]));

    expect($completed)->toBe(1)
        ->and($open->refresh()->status)->toBe(TaskStatus::Done)
        ->and($done->refresh()->completed_at?->toDateTimeString())->toBe('2026-01-01 10:00:00');
});

test('with nothing that can be completed it refuses and changes nothing', function () {
    $done = Task::factory()->completed()->create(['completed_at' => '2026-01-01 10:00:00']);

    expect(fn () => app(CompleteTasks::class)->handle(new Collection([$done])))->toThrow(ValidationException::class);
    expect(fn () => app(CompleteTasks::class)->handle(new Collection))->toThrow(ValidationException::class);

    expect($done->refresh()->completed_at?->toDateTimeString())->toBe('2026-01-01 10:00:00');
});

test('every task is completed or none is', function () {
    $first = Task::factory()->create();
    $second = Task::factory()->create();

    Task::updating(function (Task $task) use ($second) {
        if ($task->is($second)) {
            throw new RuntimeException('The second write failed.');
        }
    });

    expect(fn () => app(CompleteTasks::class)->handle(new Collection([$first, $second])))->toThrow(RuntimeException::class);

    expect($first->refresh()->status)->toBe(TaskStatus::Open)
        ->and($second->refresh()->status)->toBe(TaskStatus::Open);
});
