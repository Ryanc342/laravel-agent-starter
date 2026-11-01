<?php

use App\Actions\Tasks\CompleteOverdueTasks;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('every overdue task of the user is completed, and nothing else', function () {
    $user = User::factory()->create();
    $overdue = Task::factory()->for($user)->overdue()->count(2)->create();
    $notDueYet = Task::factory()->for($user)->create(['due_on' => today()->addDay()]);
    $someoneElses = Task::factory()->overdue()->create();

    $completed = app(CompleteOverdueTasks::class)->handle($user);

    expect($completed)->toBe(2);
    $overdue->each(fn (Task $task) => expect($task->refresh()->status)->toBe(TaskStatus::Done));
    expect($notDueYet->refresh()->status)->toBe(TaskStatus::Open)
        ->and($someoneElses->refresh()->status)->toBe(TaskStatus::Open);
});

test('with nothing overdue it refuses and changes nothing', function () {
    $user = User::factory()->create();
    $notDueYet = Task::factory()->for($user)->create(['due_on' => today()->addDay()]);
    $someoneElses = Task::factory()->overdue()->create();

    expect(fn () => app(CompleteOverdueTasks::class)->handle($user))->toThrow(ValidationException::class);
    expect($notDueYet->refresh()->status)->toBe(TaskStatus::Open)
        ->and($someoneElses->refresh()->status)->toBe(TaskStatus::Open);
});
