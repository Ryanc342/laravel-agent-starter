<?php

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/*
| Search, sort and filters of the task list. They live only in the URL, and a
| value that is not allowed is ignored, never an error.
*/

dataset('task sorts', [
    'title ascending' => [['sort' => 'title', 'direction' => 'asc'], ['Apple', 'Banana', 'Cherry']],
    'title descending' => [['sort' => 'title', 'direction' => 'desc'], ['Cherry', 'Banana', 'Apple']],
    'title with no direction goes ascending' => [['sort' => 'title'], ['Apple', 'Banana', 'Cherry']],
    'title with an unknown direction goes ascending' => [['sort' => 'title', 'direction' => 'sideways'], ['Apple', 'Banana', 'Cherry']],
    'due date ascending, no date last' => [['sort' => 'due_on', 'direction' => 'asc'], ['Banana', 'Apple', 'Cherry']],
    'due date descending, no date still last' => [['sort' => 'due_on', 'direction' => 'desc'], ['Apple', 'Banana', 'Cherry']],
    'status ascending, newest first inside a status' => [['sort' => 'status', 'direction' => 'asc'], ['Banana', 'Cherry', 'Apple']],
    'status descending, newest first inside a status' => [['sort' => 'status', 'direction' => 'desc'], ['Cherry', 'Apple', 'Banana']],
]);

dataset('ignored sort values', [
    'unknown column' => [['sort' => 'user_id', 'direction' => 'desc']],
    'a direction with no column' => [['direction' => 'desc']],
    'a column that is a list' => [['sort' => ['title']]],
]);

dataset('task status filters', [
    'one status' => [['status' => ['open']], ['Apple', 'Cherry']],
    'the other status' => [['status' => ['done']], ['Banana']],
    'several statuses together' => [['status' => ['open', 'done']], ['Apple', 'Cherry', 'Banana']],
    'an unknown status is ignored' => [['status' => ['archived']], ['Apple', 'Cherry', 'Banana']],
    'an unknown status beside a known one' => [['status' => ['done', 'archived']], ['Banana']],
    'a status that is not a list is ignored' => [['status' => 'done'], ['Apple', 'Cherry', 'Banana']],
]);

dataset('task due ranges', [
    'from only, the day itself counts' => [['due_from' => '2026-11-10'], ['Apple']],
    'to only, the day itself counts' => [['due_to' => '2026-11-10'], ['Apple', 'Banana']],
    'from and to, both ends count' => [['due_from' => '2026-11-05', 'due_to' => '2026-11-10'], ['Apple', 'Banana']],
    'from and to the same day' => [['due_from' => '2026-11-10', 'due_to' => '2026-11-10'], ['Apple']],
    'a range that holds nothing' => [['due_from' => '2026-12-01', 'due_to' => '2026-12-31'], []],
    'a date that is not a date is ignored' => [['due_from' => 'tomorrow', 'due_to' => '2026-13-45'], ['Apple', 'Cherry', 'Banana']],
]);

/**
 * The user's three tasks, created in this order: Apple (open, due 10 Nov),
 * Banana (done, due 5 Nov) and Cherry (open, no due date). listOrder() shows
 * them Apple, Cherry, Banana.
 *
 * @return array{0: User, 1: array<string, Task>}
 */
function taskListCase(): array
{
    $user = User::factory()->create();

    $tasks = [
        'Apple' => Task::factory()->for($user)->create(['title' => 'Apple', 'due_on' => '2026-11-10']),
        'Banana' => Task::factory()->for($user)->completed()->create(['title' => 'Banana', 'due_on' => '2026-11-05']),
        'Cherry' => Task::factory()->for($user)->create(['title' => 'Cherry', 'due_on' => null]),
    ];

    return [$user, $tasks];
}

test('the list keeps its default order when nothing is asked', function () {
    [$user] = taskListCase();

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertInertia(fn (Assert $page) => $page->where(
            'tasks.data',
            fn ($rows) => collect($rows)->pluck('title')->all() === ['Apple', 'Cherry', 'Banana'],
        ));
});

test('search finds a task by part of its title, in any case', function () {
    [$user] = taskListCase();

    $this->actingAs($user)
        ->get(route('tasks.index', ['search' => 'ANAN']))
        ->assertInertia(fn (Assert $page) => $page->where(
            'tasks.data',
            fn ($rows) => collect($rows)->pluck('title')->all() === ['Banana'],
        ));
});

test('search treats percent and underscore signs as plain text', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['title' => 'Save 100% of it']);
    Task::factory()->for($user)->create(['title' => 'Save 1000 of it']);

    $this->actingAs($user)
        ->get(route('tasks.index', ['search' => '100%']))
        ->assertInertia(fn (Assert $page) => $page->where(
            'tasks.data',
            fn ($rows) => collect($rows)->pluck('title')->all() === ['Save 100% of it'],
        ));
});

test('an empty or list search is ignored', function (mixed $search) {
    [$user] = taskListCase();

    $this->actingAs($user)
        ->get(route('tasks.index', ['search' => $search]))
        ->assertInertia(fn (Assert $page) => $page->has('tasks.data', 3));
})->with(['', '   ', [['milk']]]);

test('the list sorts by the column and direction in the URL', function (array $query, array $titles) {
    [$user] = taskListCase();

    $this->actingAs($user)
        ->get(route('tasks.index', $query))
        ->assertInertia(fn (Assert $page) => $page->where(
            'tasks.data',
            fn ($rows) => collect($rows)->pluck('title')->all() === $titles,
        ));
})->with('task sorts');

test('a sort the list does not offer is ignored', function (array $query) {
    [$user] = taskListCase();

    $this->actingAs($user)
        ->get(route('tasks.index', $query))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where(
            'tasks.data',
            fn ($rows) => collect($rows)->pluck('title')->all() === ['Apple', 'Cherry', 'Banana'],
        ));
})->with('ignored sort values');

test('the status filter keeps the ticked statuses', function (array $query, array $titles) {
    [$user] = taskListCase();

    $this->actingAs($user)
        ->get(route('tasks.index', $query))
        ->assertInertia(fn (Assert $page) => $page->where(
            'tasks.data',
            fn ($rows) => collect($rows)->pluck('title')->all() === $titles,
        ));
})->with('task status filters');

test('the due date range includes both ends', function (array $query, array $titles) {
    [$user] = taskListCase();

    $this->actingAs($user)
        ->get(route('tasks.index', $query))
        ->assertInertia(fn (Assert $page) => $page->where(
            'tasks.data',
            fn ($rows) => collect($rows)->pluck('title')->all() === $titles,
        ));
})->with('task due ranges');

test('search, status, range and sort work together', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['title' => 'Send quote B', 'due_on' => '2026-11-12']);
    Task::factory()->for($user)->create(['title' => 'Send quote A', 'due_on' => '2026-11-14']);
    Task::factory()->for($user)->create(['title' => 'Send quote C', 'due_on' => '2026-12-01']);
    Task::factory()->for($user)->completed()->create(['title' => 'Send quote D', 'due_on' => '2026-11-13']);
    Task::factory()->for($user)->create(['title' => 'Buy milk', 'due_on' => '2026-11-13']);

    $this->actingAs($user)
        ->get(route('tasks.index', [
            'search' => 'quote',
            'status' => ['open'],
            'due_from' => '2026-11-01',
            'due_to' => '2026-11-30',
            'sort' => 'title',
            'direction' => 'asc',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tasks.meta.total', 2)
            ->where('tasks.data', fn ($rows) => collect($rows)->pluck('title')->all() === ['Send quote A', 'Send quote B']));
});

test('search and filters never show another user\'s task', function () {
    [$user] = taskListCase();
    Task::factory()->create(['title' => 'Apple pie']);

    $this->actingAs($user)
        ->get(route('tasks.index', ['search' => 'apple', 'status' => ['open']]))
        ->assertInertia(fn (Assert $page) => $page->where(
            'tasks.data',
            fn ($rows) => collect($rows)->pluck('title')->all() === ['Apple'],
        ));
});

test('a list that matches nothing is empty, not an error', function () {
    [$user] = taskListCase();

    $this->actingAs($user)
        ->get(route('tasks.index', ['search' => 'zebra']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasks.data', 0)
            ->where('tasks.meta.total', 0));
});

test('the page links keep the search, sort, filters and rows per page', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(25)->create(['title' => 'Send quote', 'due_on' => '2026-06-01']);

    $query = [
        'search' => 'quote',
        'sort' => 'title',
        'direction' => 'desc',
        'status' => ['open'],
        'due_from' => '2026-01-01',
        'due_to' => '2026-12-31',
        'per_page' => 10,
    ];

    $this->actingAs($user)
        ->get(route('tasks.index', $query))
        ->assertInertia(fn (Assert $page) => $page->where('tasks.links.next', function (string $url) {
            $address = urldecode($url);

            foreach (['search=quote', 'sort=title', 'direction=desc', 'status[0]=open', 'due_from=2026-01-01', 'due_to=2026-12-31', 'per_page=10', 'page=2'] as $part) {
                if (! str_contains($address, $part)) {
                    return false;
                }
            }

            return true;
        }));
});

test('the page sends the status options as value and label pairs', function () {
    [$user] = taskListCase();

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertInertia(fn (Assert $page) => $page->where('statusOptions', fn ($options) => collect($options)->all() === [
            ['value' => 'open', 'label' => TaskStatus::Open->label()],
            ['value' => 'done', 'label' => TaskStatus::Done->label()],
        ]));
});

dataset('narrowing and ignored list values', [
    'nothing asked' => [[], false],
    'only a sort and rows per page' => [['sort' => 'title', 'per_page' => 10], false],
    'a search' => [['search' => 'apple'], true],
    'a blank search is ignored' => [['search' => '   '], false],
    'a status' => [['status' => ['done']], true],
    'an unknown status is ignored' => [['status' => ['archived']], false],
    'a due date' => [['due_from' => '2026-11-01'], true],
    'a date that is not a date is ignored' => [['due_to' => '2026-13-45'], false],
]);

test('the page says whether the search or a filter narrows the list, ignored values not counting', function (array $query, bool $narrowed) {
    [$user] = taskListCase();

    $this->actingAs($user)
        ->get(route('tasks.index', $query))
        ->assertInertia(fn (Assert $page) => $page->where('narrowed', $narrowed));
})->with('narrowing and ignored list values');
