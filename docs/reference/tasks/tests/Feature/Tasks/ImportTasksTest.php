<?php

use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\ImportTasks;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('every row becomes an open task of the user', function () {
    $user = User::factory()->create();

    $imported = app(ImportTasks::class)->handle($user, [
        ['title' => 'Buy milk', 'due_on' => '2026-11-10'],
        ['title' => 'Send quote', 'due_on' => null],
    ]);

    expect($imported)->toBe(2)
        ->and(Task::query()->ownedBy($user)->count())->toBe(2);
    $milk = Task::query()->where('title', 'Buy milk')->firstOrFail();
    expect($milk->due_on?->toDateString())->toBe('2026-11-10')
        ->and($milk->status)->toBe(TaskStatus::Open)
        ->and($milk->user_id)->toBe($user->id);
});

test('the open-task limit of CreateTask counts, so an import cannot get around it', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(CreateTask::MAX_OPEN_TASKS - 2)->create();

    expect(fn () => app(ImportTasks::class)->handle($user, [
        ['title' => 'One', 'due_on' => null],
        ['title' => 'Two', 'due_on' => null],
        ['title' => 'Three', 'due_on' => null],
    ]))->toThrow(ValidationException::class);

    expect(Task::query()->ownedBy($user)->count())->toBe(CreateTask::MAX_OPEN_TASKS - 2);
});

test('every row is imported or none is', function () {
    $user = User::factory()->create();

    Task::creating(function (Task $task) {
        if ($task->title === 'Second') {
            throw new RuntimeException('The second write failed.');
        }
    });

    expect(fn () => app(ImportTasks::class)->handle($user, [
        ['title' => 'First', 'due_on' => null],
        ['title' => 'Second', 'due_on' => null],
    ]))->toThrow(RuntimeException::class);

    expect(Task::query()->count())->toBe(0);
});
