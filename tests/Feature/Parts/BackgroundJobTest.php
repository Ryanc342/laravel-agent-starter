<?php

namespace Tests\Feature\Parts;

use App\Actions\Operation;
use App\Actions\UserlessOperation;
use App\Jobs\OperationJob;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use LogicException;

/*
| The background job part's own test (app/Jobs/OperationJob.php and the
| operation part's later()). Tests run the queue right away (QUEUE_CONNECTION
| sync), and a job started inside a transaction waits for it to be saved.
| The operations below exist only for this test.
*/

uses(RefreshDatabase::class);

/** Marks its notification read in the background; keeps who ran it. */
final class PartMarkLater extends Operation
{
    public static ?string $ranBy = null;

    public function ability(): string
    {
        return 'partJob';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        self::$ranBy = $user->id;
        $subject?->forceFill(['read_at' => now()])->save();

        return null;
    }
}

/** Starts PartMarkLater on another notification, then, with "fail", is undone. */
final class PartStartLater extends Operation
{
    public function ability(): string
    {
        return 'partJob';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function rules(): array
    {
        return ['other' => ['required', 'string'], 'fail' => ['boolean']];
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        $other = Notification::query()->findOrFail($input['other']);
        $this->later($user, PartMarkLater::class, $other);

        if ($input['fail'] ?? false) {
            throw ValidationException::withMessages(['fail' => 'Undone.']);
        }

        return null;
    }
}

/** The system's nightly work; refuses the name "refuse". */
final class PartNightly extends UserlessOperation
{
    public static int $runs = 0;

    public function ability(): string
    {
        return 'partNobodyJob';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function rules(): array
    {
        return ['name' => ['nullable', 'string']];
    }

    public function inputRefusal(?Model $subject, array $input): ?array
    {
        return ($input['name'] ?? null) === 'refuse' ? ['name' => 'Refused.'] : null;
    }

    protected function perform(?Model $subject, array $input): mixed
    {
        self::$runs++;

        return null;
    }
}

/** Running it twice does no harm. */
final class PartRepeatable extends UserlessOperation
{
    public function ability(): string
    {
        return 'partNobodyJob';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function repeatable(): bool
    {
        return true;
    }

    protected function perform(?Model $subject, array $input): mixed
    {
        return null;
    }
}

beforeEach(function () {
    Gate::define('partJob', fn (User $user, mixed $target = null): bool => $target === Notification::class
        || ($target instanceof Notification && $target->isOwnedBy($user)));
    Gate::define('partNobodyJob', fn (?User $user, mixed $target = null): bool => false);
    PartMarkLater::$ranBy = null;
    PartNightly::$runs = 0;
});

test('a job an operation starts runs once the writes are saved, as the same user', function () {
    $mine = Notification::factory()->create();
    $user = $mine->notifiable;
    assert($user instanceof User);
    $other = Notification::factory()->create(['notifiable_id' => $user->id]);

    app(PartStartLater::class)->run($user, $mine, ['other' => $other->id]);

    expect($other->refresh()->read_at)->not->toBeNull()
        ->and(PartMarkLater::$ranBy)->toBe($user->id);
});

test('a job an operation starts never runs when the operation is undone', function () {
    $mine = Notification::factory()->create();
    $user = $mine->notifiable;
    assert($user instanceof User);
    $other = Notification::factory()->create(['notifiable_id' => $user->id]);

    expect(fn () => app(PartStartLater::class)->run($user, $mine, ['other' => $other->id, 'fail' => true]))
        ->toThrow(ValidationException::class);
    expect($other->refresh()->read_at)->toBeNull()
        ->and(PartMarkLater::$ranBy)->toBeNull();
});

test('the permission is checked again when the job runs', function () {
    $notification = Notification::factory()->create();

    expect(fn () => (new OperationJob(PartMarkLater::class, User::factory()->create(), $notification))->handle())
        ->toThrow(AuthorizationException::class);
    expect($notification->refresh()->read_at)->toBeNull();
});

test('the system runs a userless operation with every other step', function () {
    OperationJob::bySystem(PartNightly::class, null, ['name' => 'nightly'])->handle();
    expect(PartNightly::$runs)->toBe(1);

    expect(fn () => OperationJob::bySystem(PartNightly::class, null, ['name' => 'refuse'])->handle())
        ->toThrow(ValidationException::class);
    expect(PartNightly::$runs)->toBe(1);
});

test('only a userless operation runs as the system', function () {
    expect(fn () => OperationJob::bySystem(PartMarkLater::class))->toThrow(LogicException::class);
});

test('a job gets plain values, not records', function () {
    $notification = Notification::factory()->create();

    expect(fn () => new OperationJob(PartNightly::class, null, null, ['record' => $notification]))
        ->toThrow(LogicException::class);
});

test('a failed job is not tried again, unless running it twice does no harm', function () {
    expect((new OperationJob(PartNightly::class))->tries)->toBe(1)
        ->and((new OperationJob(PartRepeatable::class))->tries)->toBe(3);
});
