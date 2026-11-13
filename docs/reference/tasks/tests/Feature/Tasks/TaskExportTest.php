<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
| The export is the task list as the user sees it (the same search, sort and
| filters), every page, as a CSV file that Excel opens correctly.
*/

/**
 * The CSV rows of a download, without the byte order mark.
 *
 * @return list<list<string|null>>
 */
function taskExportRows(string $content): array
{
    $content = str_starts_with($content, "\xEF\xBB\xBF") ? substr($content, 3) : $content;

    return array_map(str_getcsv(...), array_values(array_filter(explode("\n", $content), fn (string $line): bool => $line !== '')));
}

/**
 * The user's Apple (open, due 10 Nov), Banana (done, due 5 Nov) and Cherry
 * (open, no due date), and a stranger's task.
 */
function taskExportCase(): User
{
    $user = User::factory()->create();

    Task::factory()->for($user)->create(['title' => 'Apple', 'due_on' => '2026-11-10']);
    Task::factory()->for($user)->completed()->create(['title' => 'Banana', 'due_on' => '2026-11-05']);
    Task::factory()->for($user)->create(['title' => 'Cherry']);
    Task::factory()->create(['title' => 'Someone else\'s']);

    return $user;
}

test('guests are redirected to the login page', function () {
    $this->get(route('tasks.export'))->assertRedirect(route('login'));
});

test('the download is a CSV file named tasks.csv', function () {
    $response = $this->actingAs(taskExportCase())->get(route('tasks.export'));

    $response->assertOk()->assertDownload('tasks.csv');
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
});

test('the file starts with a byte order mark, so Excel shows non-English text', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['title' => '买牛奶']);

    $content = $this->actingAs($user)->get(route('tasks.export'))->streamedContent();

    expect(str_starts_with($content, "\xEF\xBB\xBF"))->toBeTrue()
        ->and(taskExportRows($content)[1][0])->toBe('买牛奶');
});

test('the first line names the columns and each task is one line below it, in the list order', function () {
    $content = $this->actingAs(taskExportCase())->get(route('tasks.export'))->streamedContent();

    expect(taskExportRows($content))->toBe([
        ['Title', 'Status', 'Due date'],
        ['Apple', 'Open', '2026-11-10'],
        ['Cherry', 'Open', ''],
        ['Banana', 'Done', '2026-11-05'],
    ]);
});

test('only the user\'s own tasks are in the file', function () {
    $content = $this->actingAs(taskExportCase())->get(route('tasks.export'))->streamedContent();

    expect($content)->not->toContain('Someone else');
});

test('the file holds the rows the list shows for its search, sort and filters', function (array $query, array $titles) {
    $content = $this->actingAs(taskExportCase())->get(route('tasks.export', $query))->streamedContent();

    expect(array_column(array_slice(taskExportRows($content), 1), 0))->toBe($titles);
})->with([
    'a search' => [['search' => 'ana'], ['Banana']],
    'a status' => [['status' => ['done']], ['Banana']],
    'a due range' => [['due_from' => '2026-11-06'], ['Apple']],
    'a sort' => [['sort' => 'title', 'direction' => 'desc'], ['Cherry', 'Banana', 'Apple']],
    'values that are not allowed, ignored like on the list' => [['sort' => 'user_id', 'status' => ['archived'], 'due_from' => 'tomorrow'], ['Apple', 'Cherry', 'Banana']],
]);

test('every page is exported, whatever the page or rows per page of the list', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(45)->create();

    $content = $this->actingAs($user)->get(route('tasks.export', ['page' => 2, 'per_page' => 10]))->streamedContent();

    expect(taskExportRows($content))->toHaveCount(46);
});

test('a title that a spreadsheet would run as a formula is written as text', function (string $title) {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['title' => $title]);

    $content = $this->actingAs($user)->get(route('tasks.export'))->streamedContent();

    expect(taskExportRows($content)[1][0])->toBe("'".$title);
})->with(['=SUM(A1:A9)', '+1', '-1', '@cmd']);

test('commas, quotes and line breaks in a title stay inside their cell', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['title' => "Say \"hi\", then\nleave"]);

    $content = $this->actingAs($user)->get(route('tasks.export'))->streamedContent();
    $handle = fopen('php://memory', 'r+');
    fwrite($handle, substr($content, 3));
    rewind($handle);
    fgetcsv($handle);

    expect(fgetcsv($handle)[0])->toBe("Say \"hi\", then\nleave");
});
