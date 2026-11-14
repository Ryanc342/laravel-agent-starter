<?php

use App\Actions\Tasks\CreateTask;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

/*
| Import: upload a CSV, the backend checks every row, and either every row
| becomes a task or nothing does. The errors say the row ("Row 3: ..."), at most
| five, then one line for the rest. The row number is the spreadsheet's: the
| header is row 1.
*/

function taskImportFile(string $content, string $name = 'tasks.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

/**
 * @return array<string, string>
 */
function taskImportErrors(): array
{
    return array_map(fn (array $messages): string => $messages[0], session('errors')->getBag('default')->getMessages());
}

test('guests are redirected to the login page', function () {
    $this->post(route('tasks.import.store'), ['file' => taskImportFile("Title\nA\n")])->assertRedirect(route('login'));
    $this->get(route('tasks.import.template'))->assertRedirect(route('login'));
});

test('a file with good rows creates a task for each, for the user only, and goes back to the list', function () {
    $user = User::factory()->create();
    $csv = "Title,Due date\nBuy milk,2026-11-10\nSend quote,\n";

    $this->actingAs($user)
        ->from(route('tasks.index', ['search' => 'x']))
        ->post(route('tasks.import.store'), ['file' => taskImportFile($csv)])
        ->assertRedirect(route('tasks.index', ['search' => 'x']))
        ->assertSessionHasNoErrors();

    $tasks = Task::query()->ownedBy($user)->orderBy('id')->get();

    expect($tasks->pluck('title')->all())->toBe(['Buy milk', 'Send quote'])
        ->and($tasks[0]->due_on?->toDateString())->toBe('2026-11-10')
        ->and($tasks[1]->due_on)->toBeNull()
        ->and($tasks->every(fn (Task $task) => $task->status === TaskStatus::Open))->toBeTrue()
        ->and(Task::query()->count())->toBe(2);
});

test('the file the export makes can be imported again: extra columns are ignored', function () {
    $user = User::factory()->create();
    $export = "\xEF\xBB\xBFTitle,Status,Due date\n买牛奶,Open,2026-11-10\n";

    $this->actingAs($user)->post(route('tasks.import.store'), ['file' => taskImportFile($export)])->assertSessionHasNoErrors();

    $task = Task::query()->ownedBy($user)->firstOrFail();

    expect($task->title)->toBe('买牛奶')
        ->and($task->due_on?->toDateString())->toBe('2026-11-10');
});

test('the columns may come in any order and in any case, and blank lines are skipped', function () {
    $user = User::factory()->create();
    $csv = "due date , TITLE\n2026-11-10,Buy milk\n\n,Send quote\n";

    $this->actingAs($user)->post(route('tasks.import.store'), ['file' => taskImportFile($csv)])->assertSessionHasNoErrors();

    expect(Task::query()->ownedBy($user)->orderBy('id')->pluck('title')->all())->toBe(['Buy milk', 'Send quote']);
});

test('one bad row imports nothing and the error names its row', function (string $csv, string $message) {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('tasks.import.store'), ['file' => taskImportFile($csv)])->assertSessionHasErrors();

    expect(array_values(taskImportErrors()))->toBe([$message])
        ->and(Task::query()->count())->toBe(0);
})->with([
    'a date that is not a date' => ["Title,Due date\nA,2026-11-10\nB,tomorrow\n", 'Row 3: Due date is not a date.'],
    'a day that does not exist' => ["Title,Due date\nA,2026-02-30\n", 'Row 2: Due date is not a date.'],
    'a missing title' => ["Title,Due date\nA,\n,2026-11-10\n", 'Row 3: Title is required.'],
    'a title that is too long' => ['Title'."\n".str_repeat('x', 256)."\n", 'Row 2: Title can have at most 255 characters.'],
]);

test('every row error comes back, in row order, with no line for the rest', function () {
    $user = User::factory()->create();
    $rows = array_map(fn (int $row): string => "Task {$row},not a date", range(2, 8));
    $csv = "Title,Due date\n".implode("\n", $rows)."\n";

    $this->actingAs($user)->post(route('tasks.import.store'), ['file' => taskImportFile($csv)])->assertSessionHasErrors();

    expect(array_values(taskImportErrors()))->toBe(array_map(
        fn (int $row): string => "Row {$row}: Due date is not a date.",
        range(2, 8),
    ))->and(implode(' ', taskImportErrors()))->not->toContain('more')
        ->and(Task::query()->count())->toBe(0);
});

test('errors of different columns are listed in row order', function () {
    $user = User::factory()->create();
    $csv = "Title,Due date\nA,tomorrow\n,2026-11-10\nB,nonsense\n";

    $this->actingAs($user)->post(route('tasks.import.store'), ['file' => taskImportFile($csv)])->assertSessionHasErrors();

    expect(array_values(taskImportErrors()))->toBe([
        'Row 2: Due date is not a date.',
        'Row 3: Title is required.',
        'Row 4: Due date is not a date.',
    ]);
});

test('a file the import cannot read is refused with one message and nothing is imported', function (?UploadedFile $file, string $message) {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('tasks.import.store'), $file === null ? [] : ['file' => $file])->assertSessionHasErrors('file');

    expect(taskImportErrors()['file'])->toBe($message)
        ->and(Task::query()->count())->toBe(0);
})->with([
    'no file' => [null, 'Choose a CSV file.'],
    'a file that is not CSV' => [fn () => UploadedFile::fake()->image('tasks.png'), 'The file must be a CSV file.'],
    'a header without a Title column' => [fn () => taskImportFile("Name,Due date\nA,2026-11-10\n"), 'The first line must name the columns Title and Due date.'],
    'an empty file' => [fn () => taskImportFile(''), 'The first line must name the columns Title and Due date.'],
    'a file that is not UTF-8' => [fn () => taskImportFile("Title\n".mb_convert_encoding('买牛奶', 'GBK', 'UTF-8')."\n"), 'The file must be saved as UTF-8.'],
    'a header and no tasks' => [fn () => taskImportFile("Title,Due date\n"), 'The file has no tasks.'],
    'a file over 1 MB' => [fn () => UploadedFile::fake()->createWithContent('big.csv', "Title\n".str_repeat("A task\n", 200000)), 'The file can be at most 1 MB.'],
]);

test('more tasks than the open-task limit allows import nothing', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(CreateTask::MAX_OPEN_TASKS - 1)->create();

    $this->actingAs($user)->post(route('tasks.import.store'), ['file' => taskImportFile("Title\nOne\nTwo\n")])->assertSessionHasErrors('title');

    expect(Task::query()->ownedBy($user)->count())->toBe(CreateTask::MAX_OPEN_TASKS - 1);
});

test('the template is a CSV with a byte order mark and the two columns the import reads', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('tasks.import.template'));

    $response->assertOk()->assertDownload('tasks-template.csv');
    expect($response->streamedContent())->toBe("\xEF\xBB\xBFTitle,\"Due date\"\n");
});

test('the file of the template can be imported as it is only after a task is added', function () {
    $user = User::factory()->create();
    $template = $this->actingAs($user)->get(route('tasks.import.template'))->streamedContent();

    $this->actingAs($user)->post(route('tasks.import.store'), ['file' => taskImportFile($template)])->assertSessionHasErrors('file');
    $this->actingAs($user)->post(route('tasks.import.store'), ['file' => taskImportFile($template."Buy milk,2026-11-10\n")])->assertSessionHasNoErrors();

    expect(Task::query()->ownedBy($user)->count())->toBe(1);
});
