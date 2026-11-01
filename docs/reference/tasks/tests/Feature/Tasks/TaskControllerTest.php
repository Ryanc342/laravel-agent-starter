<?php

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get(route('tasks.index'))->assertRedirect(route('login'));
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
        ->assertRedirect(route('tasks.index'));

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
