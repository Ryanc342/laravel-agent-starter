<?php

namespace Tests\Feature\Parts;

use App\Actions\Operation;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;

/*
| The CSV rows of the operation part (csvRows() in app/Actions/OperationSteps.php):
| the file's first line names the columns, every other line is a row checked
| with the row rules, and the rows are written in the operation's one
| transaction. The operations below exist only for this test.
*/

uses(RefreshDatabase::class);

/** Imports notes as notifications of the user; a note "boom" fails while writing. */
class PartImportNotes extends Operation
{
    /** @var array<string, mixed>|null */
    public static ?array $seen = null;

    public function ability(): string
    {
        return 'partImport';
    }

    public function subjectClass(): string
    {
        return User::class;
    }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'mimes:csv,txt', 'max:1024']];
    }

    public function csvRows(): array
    {
        return ['file' => [
            'type' => ['required', 'in:a,b'],
            'message' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
        ]];
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        self::$seen = $input;

        foreach ($input['file'] as $row) {
            Notification::factory()->for($user, 'notifiable')->create(['data' => ['message' => $row['message'], 'values' => []]]);

            if ($row['note'] === 'boom') {
                throw new RuntimeException('Writing failed.');
            }
        }

        return null;
    }
}

/** The file field has no rule. */
final class PartImportWithoutRule extends PartImportNotes
{
    public function rules(): array
    {
        return [];
    }
}

/** The file field is stored and read at once. */
final class PartImportStored extends PartImportNotes
{
    public function files(): array
    {
        return ['file' => 'private'];
    }
}

beforeEach(function () {
    Gate::define('partImport', fn (User $user): bool => true);
    PartImportNotes::$seen = null;
});

function csvUpload(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('rows.csv', $content);
}

/**
 * The input errors of importing this file, or [] when it was imported.
 *
 * @return array<string, list<string>>
 */
function importErrors(User $user, string $content): array
{
    try {
        app(PartImportNotes::class)->run($user, null, ['file' => csvUpload($content)]);
    } catch (ValidationException $error) {
        return $error->errors();
    }

    return [];
}

test('the operation gets the checked rows by line number, with cells trimmed and empty cells null', function () {
    $user = User::factory()->create();
    $content = "\u{FEFF}message,type,note\nInvoice paid, a ,\n\n\"Two, and\nlines\",b,x\n";

    expect(importErrors($user, $content))->toBe([])
        ->and(PartImportNotes::$seen['file'])->toEqual([
            2 => ['type' => 'a', 'message' => 'Invoice paid', 'note' => null],
            4 => ['type' => 'b', 'message' => "Two, and\nlines", 'note' => 'x'],
        ])
        ->and(Notification::query()->count())->toBe(2);
});

test('when any row is wrong, nothing is imported and every wrong row is an error with its line', function () {
    $user = User::factory()->create();
    $content = "type,message,note\na,Fine,\nb,,\nc,Wrong type,\na,Too few\n";

    $errors = importErrors($user, $content);

    expect(array_keys($errors))->toBe(['file.3.row', 'file.4.row', 'file.5.row'])
        ->and($errors['file.3.row'][0])->toStartWith('Row 3: ')
        ->and($errors['file.5.row'][0])->toBe('Row 5: it has 2 cells, but the first line names 3 columns.')
        ->and(PartImportNotes::$seen)->toBeNull()
        ->and(Notification::query()->count())->toBe(0);
});

test('a failure while writing undoes every row written before it', function () {
    $user = User::factory()->create();

    expect(fn () => importErrors($user, "type,message,note\na,First,\na,Second,boom\n"))->toThrow(RuntimeException::class)
        ->and(Notification::query()->count())->toBe(0);
});

test('the first line must name exactly the columns, and the file needs rows and UTF-8 text', function (string $content, string $message) {
    expect(importErrors(User::factory()->create(), $content))->toBe(['file' => [$message]])
        ->and(PartImportNotes::$seen)->toBeNull();
})->with([
    'a column missing' => ["type,message\na,Hi\n", 'The first line must name these columns: type, message, note.'],
    'a column too many' => ["type,message,note,owner\na,Hi,,me\n", 'The first line must name these columns: type, message, note.'],
    'an empty file' => ['', 'The first line must name these columns: type, message, note.'],
    'no rows' => ["type,message,note\n\n", 'The file has no rows.'],
    'not UTF-8' => ["type,message,note\na,Caf\xE9,\n", 'The file must be saved as CSV UTF-8.'],
]);

test('a CSV field needs a rule, and is read, not stored', function (string $operation) {
    expect(fn () => app($operation)->run(User::factory()->create(), null, ['file' => csvUpload("type,message,note\na,Hi,\n")]))
        ->toThrow(LogicException::class);
})->with([PartImportWithoutRule::class, PartImportStored::class]);
