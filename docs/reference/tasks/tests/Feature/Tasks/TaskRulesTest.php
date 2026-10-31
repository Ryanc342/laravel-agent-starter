<?php

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
| The facts about a task, tested through their owners. Each case is listed
| with a dataset instead of picking one example.
*/

dataset('due dates', [
    'due yesterday' => [-1, true],
    'due today' => [0, false],
    'due tomorrow' => [1, false],
]);

test('an open task is overdue only when its due date is before today', function (int $days, bool $overdue) {
    $task = Task::factory()->create(['due_on' => today()->addDays($days)]);

    expect($task->isOverdue())->toBe($overdue);
})->with('due dates');

test('a task without a due date is never overdue', function () {
    $task = Task::factory()->create(['due_on' => null]);

    expect($task->isOverdue())->toBeFalse();
});

test('a completed task is never overdue', function () {
    $task = Task::factory()->completed()->overdue()->create();

    expect($task->isOverdue())->toBeFalse();
});

test('a task belongs only to its creator', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create();

    expect($task->isOwnedBy($owner))->toBeTrue()
        ->and($task->isOwnedBy(User::factory()->create()))->toBeFalse();
});

test('only the open status counts as open', function () {
    expect(TaskStatus::openValues())->toBe([TaskStatus::Open->value]);
});

test('the list shows open tasks first, then by due date', function () {
    $user = User::factory()->create();
    $done = Task::factory()->for($user)->completed()->create(['due_on' => today()->subDays(5)]);
    $noDate = Task::factory()->for($user)->create(['due_on' => null]);
    $later = Task::factory()->for($user)->create(['due_on' => today()->addDays(3)]);
    $sooner = Task::factory()->for($user)->create(['due_on' => today()->addDay()]);

    $ids = Task::query()->ownedBy($user)->listOrder()->pluck('id')->all();

    expect($ids)->toBe([$sooner->id, $later->id, $noDate->id, $done->id]);
});
