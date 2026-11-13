<?php

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
| Bulk complete and bulk delete from the task list. They take either the ticked
| ids or "all results" with the list's own search and filters, check the
| Policy for every task, and change everything or nothing.
*/

/**
 * The user's tasks: Apple (open, due 10 Nov), Banana (done, due 5 Nov), Cherry
 * (open, no due date) and Apricot (open, due 20 Nov); plus a stranger's open
 * task called Apple pie.
 *
 * @return array{0: User, 1: array<string, Task>}
 */
function bulkTaskCase(): array
{
    $user = User::factory()->create();

    return [$user, [
        'Apple' => Task::factory()->for($user)->create(['title' => 'Apple', 'due_on' => '2026-11-10']),
        'Banana' => Task::factory()->for($user)->completed()->create(['title' => 'Banana', 'due_on' => '2026-11-05']),
        'Cherry' => Task::factory()->for($user)->create(['title' => 'Cherry']),
        'Apricot' => Task::factory()->for($user)->create(['title' => 'Apricot', 'due_on' => '2026-11-20']),
        'Apple pie' => Task::factory()->create(['title' => 'Apple pie']),
    ]];
}

test('guests are redirected to the login page', function () {
    $this->post(route('tasks.bulk-completion.store'), ['all' => true])->assertRedirect(route('login'));
    $this->delete(route('tasks.bulk-deletion.destroy'), ['all' => true])->assertRedirect(route('login'));
});

test('ticked tasks are completed and the user goes back to the list', function () {
    [$user, $tasks] = bulkTaskCase();

    $this->actingAs($user)
        ->from(route('tasks.index', ['search' => 'a']))
        ->post(route('tasks.bulk-completion.store'), ['ids' => [$tasks['Apple']->id, $tasks['Cherry']->id]])
        ->assertRedirect(route('tasks.index', ['search' => 'a']));

    expect($tasks['Apple']->refresh()->status)->toBe(TaskStatus::Done)
        ->and($tasks['Cherry']->refresh()->status)->toBe(TaskStatus::Done)
        ->and($tasks['Apricot']->refresh()->status)->toBe(TaskStatus::Open);
});

test('ticked tasks are deleted', function () {
    [$user, $tasks] = bulkTaskCase();

    $this->actingAs($user)
        ->delete(route('tasks.bulk-deletion.destroy'), ['ids' => [$tasks['Apple']->id, $tasks['Banana']->id]])
        ->assertRedirect();

    $this->assertModelMissing($tasks['Apple']);
    $this->assertModelMissing($tasks['Banana']);
    $this->assertModelExists($tasks['Cherry']);
});

test('a task of another user in the ticked ids refuses the whole request', function () {
    [$user, $tasks] = bulkTaskCase();
    $ids = [$tasks['Apple']->id, $tasks['Apple pie']->id];

    $this->actingAs($user)->post(route('tasks.bulk-completion.store'), ['ids' => $ids])->assertForbidden();
    $this->actingAs($user)->delete(route('tasks.bulk-deletion.destroy'), ['ids' => $ids])->assertForbidden();

    expect($tasks['Apple']->refresh()->status)->toBe(TaskStatus::Open)
        ->and($tasks['Apple pie']->refresh()->status)->toBe(TaskStatus::Open);
    $this->assertModelExists($tasks['Apple']);
    $this->assertModelExists($tasks['Apple pie']);
});

test('all results completes exactly the tasks the list shows, and only the user\'s own', function (array $filters, array $titles) {
    [$user, $tasks] = bulkTaskCase();

    $this->actingAs($user)
        ->post(route('tasks.bulk-completion.store'), ['all' => true, ...$filters])
        ->assertRedirect();

    $completed = collect($tasks)->filter(fn (Task $task) => $task->refresh()->status === TaskStatus::Done
        && $task->completed_at !== null && $task->user_id === $user->id)->keys()->sort()->values()->all();
    $expected = collect([...$titles, 'Banana'])->unique()->sort()->values()->all();

    expect($completed)->toBe($expected)
        ->and($tasks['Apple pie']->refresh()->status)->toBe(TaskStatus::Open);
})->with([
    'with no filter' => [[], ['Apple', 'Cherry', 'Apricot']],
    'with a search' => [['search' => 'Ap'], ['Apple', 'Apricot']],
    'with a status filter' => [['status' => ['open']], ['Apple', 'Cherry', 'Apricot']],
    'with a due range' => [['due_from' => '2026-11-06', 'due_to' => '2026-11-15'], ['Apple']],
    'with a search and a range together' => [['search' => 'Ap', 'due_from' => '2026-11-15'], ['Apricot']],
]);

test('all results deletes exactly the tasks the list shows, and only the user\'s own', function (array $filters, array $deleted) {
    [$user, $tasks] = bulkTaskCase();

    $this->actingAs($user)
        ->delete(route('tasks.bulk-deletion.destroy'), ['all' => true, ...$filters])
        ->assertRedirect();

    foreach ($tasks as $title => $task) {
        in_array($title, $deleted, true) ? $this->assertModelMissing($task) : $this->assertModelExists($task);
    }
})->with([
    'with a search' => [['search' => 'Ap'], ['Apple', 'Apricot']],
    'with a status filter' => [['status' => ['done']], ['Banana']],
    'with a due range' => [['due_to' => '2026-11-10'], ['Apple', 'Banana']],
    'with no filter, every task of the user' => [[], ['Apple', 'Banana', 'Cherry', 'Apricot']],
]);

test('all results ignores the ticked ids', function () {
    [$user, $tasks] = bulkTaskCase();

    $this->actingAs($user)
        ->delete(route('tasks.bulk-deletion.destroy'), ['all' => true, 'ids' => [$tasks['Apple pie']->id], 'search' => 'Cherry'])
        ->assertRedirect();

    $this->assertModelMissing($tasks['Cherry']);
    $this->assertModelExists($tasks['Apple pie']);
    $this->assertModelExists($tasks['Apple']);
});

test('completed tasks in the selection are skipped, and nothing to complete is refused', function () {
    [$user, $tasks] = bulkTaskCase();

    $this->actingAs($user)
        ->post(route('tasks.bulk-completion.store'), ['ids' => [$tasks['Apple']->id, $tasks['Banana']->id]])
        ->assertRedirect();

    expect($tasks['Apple']->refresh()->status)->toBe(TaskStatus::Done);

    $this->actingAs($user)
        ->post(route('tasks.bulk-completion.store'), ['ids' => [$tasks['Banana']->id]])
        ->assertSessionHasErrors('tasks');
});

test('a selection that matches no task is refused', function () {
    [$user] = bulkTaskCase();

    $this->actingAs($user)
        ->post(route('tasks.bulk-completion.store'), ['all' => true, 'search' => 'nothing like this'])
        ->assertSessionHasErrors('tasks');
    $this->actingAs($user)
        ->delete(route('tasks.bulk-deletion.destroy'), ['all' => true, 'search' => 'nothing like this'])
        ->assertSessionHasErrors('tasks');
});

test('a request with neither ids nor all results is invalid', function (array $input) {
    [$user, $tasks] = bulkTaskCase();

    $this->actingAs($user)->post(route('tasks.bulk-completion.store'), $input)->assertSessionHasErrors();
    $this->actingAs($user)->delete(route('tasks.bulk-deletion.destroy'), $input)->assertSessionHasErrors();

    expect(Task::query()->count())->toBe(count($tasks));
})->with([
    'nothing' => [[]],
    'all results switched off' => [['all' => false]],
    'an empty list' => [['ids' => []]],
    'ids that are not a list' => [['ids' => 'abc']],
    'ids that are not numbers' => [['ids' => ['x']]],
]);

test('filters that are not allowed are ignored like on the list', function () {
    [$user, $tasks] = bulkTaskCase();

    $this->actingAs($user)
        ->post(route('tasks.bulk-completion.store'), ['all' => true, 'status' => ['archived'], 'due_from' => 'tomorrow'])
        ->assertRedirect();

    expect($tasks['Cherry']->refresh()->status)->toBe(TaskStatus::Done);
});
