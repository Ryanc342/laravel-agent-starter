<?php

namespace Tests\Feature\Parts;

use App\Actions\Operation;
use App\Models\User;
use App\Support\StoredFile;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use Mockery;
use RuntimeException;

/*
| The file part's own test (app/Support/StoredFile.php, the files() step of
| the operation part, App\Http\Controllers\DownloadController). The model and
| the operations below exist only for this test: the model keeps its file in
| a nullable text column of the users table.
*/

uses(RefreshDatabase::class);

/** A user row seen as something that holds one file. */
final class PartFileHolder extends Model
{
    use HasUuids;

    public const FILE = 'two_factor_recovery_codes';

    protected $table = 'users';

    protected $guarded = [];

    protected function casts(): array
    {
        return [self::FILE => StoredFile::class];
    }
}

/** Attaches a receipt, replacing the old one; refuses the note "no". */
final class PartAttach extends Operation
{
    public function ability(): string
    {
        return 'partFile';
    }

    public function subjectClass(): string
    {
        return PartFileHolder::class;
    }

    public function rules(): array
    {
        return ['receipt' => ['required', 'file', 'mimes:pdf,jpg', 'max:1024'], 'note' => ['nullable', 'string']];
    }

    public function files(): array
    {
        return ['receipt' => 'private'];
    }

    public function inputRefusal(User $user, ?Model $subject, array $input): ?array
    {
        return ($input['note'] ?? null) === 'no' ? ['note' => 'Not with this note.'] : null;
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        assert($subject instanceof PartFileHolder);
        $subject->getAttribute(PartFileHolder::FILE)?->deleteAfterCommit();
        $subject->setAttribute(PartFileHolder::FILE, $input['receipt'])->save();

        return null;
    }
}

/** Removes the receipt; with "fail", is undone afterwards. */
final class PartDetach extends Operation
{
    public function ability(): string
    {
        return 'partFile';
    }

    public function subjectClass(): string
    {
        return PartFileHolder::class;
    }

    public function rules(): array
    {
        return ['fail' => ['boolean']];
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        assert($subject instanceof PartFileHolder);
        $subject->getAttribute(PartFileHolder::FILE)?->deleteAfterCommit();
        $subject->setAttribute(PartFileHolder::FILE, null)->save();

        if ($input['fail'] ?? false) {
            throw ValidationException::withMessages(['fail' => 'Undone.']);
        }

        return null;
    }
}

/** Keeps a logo meant for everyone. */
final class PartPublicLogo extends Operation
{
    public static ?StoredFile $stored = null;

    public function ability(): string
    {
        return 'partFile';
    }

    public function subjectClass(): string
    {
        return PartFileHolder::class;
    }

    public function rules(): array
    {
        return ['logo' => ['required', 'file', 'mimes:png']];
    }

    public function files(): array
    {
        return ['logo' => 'public'];
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        self::$stored = $input['logo'];

        return null;
    }
}

/** Names a file field without a rule for it: a programming error. */
final class PartBadFile extends Operation
{
    public function ability(): string
    {
        return 'partFile';
    }

    public function subjectClass(): string
    {
        return PartFileHolder::class;
    }

    public function files(): array
    {
        return ['receipt' => 'private'];
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        return null;
    }
}

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $own = fn (User $user, mixed $target = null): bool => $target === PartFileHolder::class
        || ($target instanceof PartFileHolder && $target->getKey() === $user->getKey());
    Gate::define('partFile', $own);
    Gate::define('partFileView', $own);

    Route::middleware('web')->group(function () {
        Route::operation('part-test/holders/{holder}/attach', PartAttach::class);
        Route::operation('part-test/holders/{holder}/detach', PartDetach::class);
        Route::download('part-test/holders/{holder}/receipt', PartFileHolder::class, 'partFileView', PartFileHolder::FILE);
    });

    PartPublicLogo::$stored = null;
});

function receipt(string $name = 'Receipt May.pdf'): UploadedFile
{
    return UploadedFile::fake()->create($name, 20, 'application/pdf');
}

function holderOf(User $user): PartFileHolder
{
    return PartFileHolder::query()->findOrFail($user->id);
}

test('an upload is stored privately under a made-up name, and the record keeps its original name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post("/part-test/holders/{$user->id}/attach", ['receipt' => receipt()])->assertRedirect();

    $file = holderOf($user)->getAttribute(PartFileHolder::FILE);
    expect($file)->toBeInstanceOf(StoredFile::class)
        ->and($file->disk)->toBe('local')
        ->and($file->name)->toBe('Receipt May.pdf')
        ->and($file->type)->toBe('application/pdf')
        ->and($file->path)->toStartWith('files/')
        ->and($file->path)->not->toContain('Receipt');
    Storage::disk('local')->assertExists($file->path);
});

test('nothing is stored when the permission or the input check fails', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->post("/part-test/holders/{$user->id}/attach", ['receipt' => receipt()])->assertForbidden();
    $this->actingAs($user)->post("/part-test/holders/{$user->id}/attach", ['receipt' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain')])
        ->assertSessionHasErrors('receipt');

    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('a refused operation deletes the file it just stored', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post("/part-test/holders/{$user->id}/attach", ['receipt' => receipt(), 'note' => 'no'])
        ->assertSessionHasErrors('note');

    expect(Storage::disk('local')->allFiles())->toBe([])
        ->and(holderOf($user)->getAttribute(PartFileHolder::FILE))->toBeNull();
});

test('a replaced file is deleted once the new one is saved', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post("/part-test/holders/{$user->id}/attach", ['receipt' => receipt('first.pdf')]);
    $first = holderOf($user)->getAttribute(PartFileHolder::FILE);
    $this->actingAs($user)->post("/part-test/holders/{$user->id}/attach", ['receipt' => receipt('second.pdf')]);
    $second = holderOf($user)->getAttribute(PartFileHolder::FILE);

    Storage::disk('local')->assertMissing($first->path);
    Storage::disk('local')->assertExists($second->path);
    expect($second->name)->toBe('second.pdf');
});

test('when the operation is undone, the old file stays', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post("/part-test/holders/{$user->id}/attach", ['receipt' => receipt()]);
    $file = holderOf($user)->getAttribute(PartFileHolder::FILE);

    $this->actingAs($user)->post("/part-test/holders/{$user->id}/detach", ['fail' => true])->assertSessionHasErrors('fail');

    Storage::disk('local')->assertExists($file->path);
    expect(holderOf($user)->getAttribute(PartFileHolder::FILE)?->path)->toBe($file->path);
});

test('a file meant for everyone goes to public storage', function () {
    $user = User::factory()->create();

    app(PartPublicLogo::class)->run($user, null, ['logo' => UploadedFile::fake()->create('logo.png', 5, 'image/png')]);

    expect(PartPublicLogo::$stored?->disk)->toBe('public');
    Storage::disk('public')->assertExists((string) PartPublicLogo::$stored?->path);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('a download checks the permission and sends the file under its original name', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    $this->actingAs($user)->post("/part-test/holders/{$user->id}/attach", ['receipt' => receipt()]);

    $this->actingAs($user)->get("/part-test/holders/{$user->id}/receipt")->assertOk()->assertDownload('Receipt May.pdf');
    $this->actingAs($stranger)->get("/part-test/holders/{$user->id}/receipt")->assertForbidden();
    $this->actingAs($stranger)->get("/part-test/holders/{$stranger->id}/receipt")->assertNotFound();
});

test('a file field without a rule is a programming error', function () {
    $user = User::factory()->create();

    expect(fn () => app(PartBadFile::class)->run($user, null, ['receipt' => receipt()]))->toThrow(LogicException::class);
});

test('a file that cannot be stored is an error, not a silent gap', function () {
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('putFileAs')->andReturn(false);
    Storage::shouldReceive('disk')->andReturn($disk);
    $user = User::factory()->create();

    expect(fn () => app(PartAttach::class)->run($user, holderOf($user), ['receipt' => receipt()]))->toThrow(RuntimeException::class);
    expect(holderOf($user)->getAttribute(PartFileHolder::FILE))->toBeNull();
});
