<?php

use App\Actions\Tasks\CreateTask;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('a new task is open and belongs to its creator', function () {
    $user = User::factory()->create();

    $task = app(CreateTask::class)->handle($user, ['title' => 'Write report', 'due_on' => '2026-11-10']);

    $task->refresh();
    expect($task->title)->toBe('Write report')
        ->and($task->status)->toBe(TaskStatus::Open)
        ->and($task->isOwnedBy($user))->toBeTrue()
        ->and($task->due_on?->toDateString())->toBe('2026-11-10');
});

test('a user cannot have more than the maximum number of open tasks', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(CreateTask::MAX_OPEN_TASKS)->create();

    expect(fn () => app(CreateTask::class)->handle($user, ['title' => 'One too many']))
        ->toThrow(ValidationException::class);

    expect(Task::query()->ownedBy($user)->count())->toBe(CreateTask::MAX_OPEN_TASKS);
});

test('completed tasks do not count towards the open task limit', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->completed()->count(CreateTask::MAX_OPEN_TASKS)->create();

    app(CreateTask::class)->handle($user, ['title' => 'Still allowed']);

    expect(Task::query()->ownedBy($user)->open()->count())->toBe(1);
});
