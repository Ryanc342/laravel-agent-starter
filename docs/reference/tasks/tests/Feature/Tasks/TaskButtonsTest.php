<?php

use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

/*
| Consistency tests. A button the Resource sends to React must be shown exactly
| when pressing it succeeds. The button and the endpoint ask the same owners
| (the Policy ability and the Model method), so these tests fail as soon as one
| of them writes its own copy of a condition and the copies drift apart.
*/

dataset('who and which task', [
    'own open task' => [true, false],
    'own completed task' => [true, true],
    'another user\'s open task' => [false, false],
    'another user\'s completed task' => [false, true],
]);

/**
 * Creates the case, returns the user and the task.
 *
 * @return array{0: User, 1: Task}
 */
function taskCase(bool $own, bool $completed): array
{
    $user = User::factory()->create();
    $factory = $completed ? Task::factory()->completed() : Task::factory();
    $task = $own ? $factory->for($user)->create() : $factory->create();

    return [$user, $task];
}

/**
 * What the Resource tells React for this user.
 *
 * @return array<string, bool>
 */
function taskButtonsFor(User $user, Task $task): array
{
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    return TaskResource::make($task)->resolve($request)['can'];
}

test('the complete button is shown exactly when completing succeeds', function (bool $own, bool $completed) {
    [$user, $task] = taskCase($own, $completed);

    $shown = taskButtonsFor($user, $task)['complete'];
    $response = $this->actingAs($user)->post(route('tasks.completion.store', $task));

    expect($shown)->toBe($response->isRedirect() && ! session()->has('errors'));
})->with('who and which task');

test('the edit button is shown exactly when editing succeeds', function (bool $own, bool $completed) {
    [$user, $task] = taskCase($own, $completed);

    $shown = taskButtonsFor($user, $task)['update'];
    $response = $this->actingAs($user)->put(route('tasks.update', $task), ['title' => 'Changed', 'due_on' => null]);

    expect($shown)->toBe($response->isRedirect() && ! session()->has('errors'));
})->with('who and which task');

test('the delete button is shown exactly when deleting succeeds', function (bool $own, bool $completed) {
    [$user, $task] = taskCase($own, $completed);

    $shown = taskButtonsFor($user, $task)['delete'];
    $response = $this->actingAs($user)->delete(route('tasks.destroy', $task));

    expect($shown)->toBe($response->isRedirect() && ! session()->has('errors'));
})->with('who and which task');
