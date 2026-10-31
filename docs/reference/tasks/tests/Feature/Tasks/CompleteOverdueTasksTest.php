<?php

use App\Actions\Tasks\CompleteOverdueTasks;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('nothing happens when the user has no overdue tasks', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create();

    expect(app(CompleteOverdueTasks::class)->handle($user))->toBe(0);
});
