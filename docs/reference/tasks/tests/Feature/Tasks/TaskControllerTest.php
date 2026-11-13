<?php

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get(route('tasks.index'))->assertRedirect(route('login'));
    $this->get(route('tasks.show', Task::factory()->create()))->assertRedirect(route('login'));
});

test('a user sees their own task', function () {
    $task = Task::factory()->create(['title' => 'Buy milk']);

    $this->actingAs($task->user)
        ->get(route('tasks.show', $task))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/show')
            ->where('task.id', $task->id)
            ->where('task.title', 'Buy milk'));
});

test('a user cannot see another user\'s task', function () {
    $task = Task::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('tasks.show', $task))
        ->assertForbidden();
});

test('the list shows only the user\'s own tasks', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(2)->create();
    Task::factory()->create();

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/index')
            ->has('tasks.data', 2));
});

test('the list shows 20 tasks per page', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(21)->create();

    $this->actingAs($user)
        ->get(route('tasks.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasks.data', 1)
            ->where('tasks.meta.current_page', 2)
            ->where('tasks.meta.total', 21));
});

test('the list pages by the rows per page in the URL', function (int $perPage) {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(101)->create();

    $this->actingAs($user)
        ->get(route('tasks.index', ['per_page' => $perPage]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasks.data', $perPage)
            ->where('tasks.meta.per_page', $perPage)
            ->where('tasks.meta.total', 101));
})->with([10, 20, 50, 100]);

test('a rows-per-page value that is not offered pages by 20', function (mixed $value) {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(25)->create();

    $this->actingAs($user)
        ->get(route('tasks.index', ['per_page' => $value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasks.data', 20)
            ->where('tasks.meta.per_page', 20));
})->with(['999', '0', '-5', 'abc', '50abc', [['10']]]);

test('the page links keep the rows per page', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(25)->create();

    $this->actingAs($user)
        ->get(route('tasks.index', ['per_page' => 10]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tasks.links.next', fn (string $url) => str_contains($url, 'per_page=10') && str_contains($url, 'page=2')));
});

test('a user can create a task', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tasks.store'), ['title' => 'Buy milk', 'due_on' => null])
        ->assertRedirect(route('tasks.index'));

    $this->assertDatabaseHas('tasks', ['user_id' => $user->id, 'title' => 'Buy milk']);
});

test('a task needs a title', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tasks.store'), ['title' => ''])
        ->assertSessionHasErrors('title');

    $this->assertDatabaseCount('tasks', 0);
});

test('a user can update their own task', function () {
    $task = Task::factory()->create(['title' => 'Original']);

    $this->actingAs($task->user)
        ->put(route('tasks.update', $task), ['title' => 'Changed', 'due_on' => null])
        ->assertRedirect(route('tasks.show', $task));

    expect($task->refresh()->title)->toBe('Changed');
});

test('a user cannot update another user\'s task', function () {
    $task = Task::factory()->create(['title' => 'Original']);

    $this->actingAs(User::factory()->create())
        ->put(route('tasks.update', $task), ['title' => 'Changed', 'due_on' => null])
        ->assertForbidden();

    expect($task->refresh()->title)->toBe('Original');
});

test('a user cannot delete another user\'s task', function () {
    $task = Task::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('tasks.destroy', $task))
        ->assertForbidden();

    $this->assertModelExists($task);
});

test('a user cannot complete another user\'s task', function () {
    $task = Task::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('tasks.completion.store', $task))
        ->assertForbidden();

    expect($task->refresh()->status)->toBe(TaskStatus::Open);
});

test('a user can complete all of their overdue tasks at once', function () {
    $user = User::factory()->create();
    $overdue = Task::factory()->for($user)->overdue()->create();

    $this->actingAs($user)
        ->post(route('tasks.overdue-completion.store'))
        ->assertRedirect(route('tasks.index'));

    expect($overdue->refresh()->status)->toBe(TaskStatus::Done);
});
