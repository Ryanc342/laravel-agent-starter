<?php

namespace Tests\Feature\Parts;

use App\Actions\After;
use App\Actions\Operation;
use App\Actions\UserlessOperation;
use App\Http\OperationGate;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use LogicException;

/*
| The operation part's own test (app/Actions/OperationSteps.php, Operation,
| UserlessOperation, After, App\Http\Controllers\OperationController,
| App\Http\OperationGate). The operations below exist only for this test.
*/

uses(RefreshDatabase::class);

/** Marks a notification read. Refuses a read one, and the note "no". */
final class PartMarkRead extends Operation
{
    /** @var array<string, mixed> */
    public static array $seen = [];

    public function ability(): string
    {
        return 'partAct';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:20'], 'other_id' => ['nullable', 'string']];
    }

    public function records(): array
    {
        return ['other_id' => [Notification::class, 'partAct']];
    }

    public function refusal(User $user, ?Model $subject): ?array
    {
        return $subject instanceof Notification && $subject->read_at !== null ? ['notification' => 'Already read.'] : null;
    }

    public function inputRefusal(User $user, ?Model $subject, array $input): ?array
    {
        return ($input['note'] ?? null) === 'no' ? ['note' => 'Not with this note.'] : null;
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        self::$seen = $input;
        $subject?->forceFill(['read_at' => now()])->save();

        return null;
    }
}

/** Marks its notification read, then runs PartMarkRead on another one. */
final class PartMarkTwo extends Operation
{
    public function ability(): string
    {
        return 'partAct';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function rules(): array
    {
        return ['other_id' => ['required', 'string']];
    }

    public function records(): array
    {
        return ['other_id' => [Notification::class, 'partAct']];
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        $subject?->forceFill(['read_at' => now()])->save();

        $other = $input['other_id'];
        assert($other instanceof Notification);

        return app(PartMarkRead::class)->run($user, $other);
    }
}

/** Nobody may run it; it needs a name. */
final class PartStrict extends Operation
{
    public function ability(): string
    {
        return 'partNobody';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string']];
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        return null;
    }
}

/** Names a record field without a rule for it: a programming error. */
final class PartBadRecords extends Operation
{
    public function ability(): string
    {
        return 'partAct';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function records(): array
    {
        return ['other_id' => [Notification::class, 'partAct']];
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        return null;
    }
}

/** Goes to the dashboard after success. */
final class PartGoDashboard extends Operation
{
    public function ability(): string
    {
        return 'partAct';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        return null;
    }

    public function after(mixed $result): After
    {
        return After::toRoute('dashboard', [], 'Done.');
    }
}

/** Signs the user out after success. */
final class PartSignOut extends Operation
{
    public function ability(): string
    {
        return 'partAct';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        return null;
    }

    public function after(mixed $result): After
    {
        return After::signOut();
    }
}

/** Anyone may run it, a guest too; it needs a name and refuses "refuse". */
final class PartGuestNote extends UserlessOperation
{
    public static int $runs = 0;

    public function ability(): string
    {
        return 'partGuest';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string']];
    }

    public function inputRefusal(?Model $subject, array $input): ?array
    {
        return $input['name'] === 'refuse' ? ['name' => 'Refused.'] : null;
    }

    protected function perform(?Model $subject, array $input): mixed
    {
        self::$runs++;

        return null;
    }

    public function after(mixed $result): After
    {
        return After::back('Saved.');
    }
}

/** Runs without a user, but its ability needs a signed-in user. */
final class PartUsersOnly extends UserlessOperation
{
    public function ability(): string
    {
        return 'partUsersOnly';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    protected function perform(?Model $subject, array $input): mixed
    {
        return null;
    }
}

/** Nobody may run it, but the system may; it needs a name and refuses "refuse". */
final class PartSystemJob extends UserlessOperation
{
    public static int $runs = 0;

    public function ability(): string
    {
        return 'partNobody';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string']];
    }

    public function inputRefusal(?Model $subject, array $input): ?array
    {
        return $input['name'] === 'refuse' ? ['name' => 'Refused.'] : null;
    }

    protected function perform(?Model $subject, array $input): mixed
    {
        self::$runs++;

        return null;
    }
}

beforeEach(function () {
    Gate::define('partAct', fn (User $user, mixed $target = null): bool => $target === Notification::class
        || ($target instanceof Notification && $target->isOwnedBy($user)));
    Gate::define('partGuest', fn (?User $user, mixed $target = null): bool => true);
    Gate::define('partUsersOnly', fn (User $user, mixed $target = null): bool => true);
    Gate::define('partNobody', fn (?User $user, mixed $target = null): bool => false);

    Route::middleware('web')->group(function () {
        Route::operation('part-test/notifications/{notification}/read', PartMarkRead::class);
        Route::operation('part-test/users/{user}/notifications/{notification}/read', PartMarkRead::class);
        Route::operation('part-test/strict', PartStrict::class);
        Route::operation('part-test/dashboard', PartGoDashboard::class);
        Route::operation('part-test/sign-out', PartSignOut::class);
        Route::operation('part-test/guest', PartGuestNote::class);
        Route::operation('part-test/users-only', PartUsersOnly::class);
    });

    PartMarkRead::$seen = [];
    PartGuestNote::$runs = 0;
    PartSystemJob::$runs = 0;
});

test('the permission is checked before the input: a user who may not run it never sees input errors', function () {
    $this->actingAs(User::factory()->create())
        ->post('/part-test/strict', [])
        ->assertForbidden();
});

test('outside the web the same steps run: permission first, then the input', function () {
    $user = User::factory()->create();

    expect(fn () => app(PartStrict::class)->run($user, null, []))->toThrow(AuthorizationException::class);

    Gate::define('partNobody', fn (?User $user, mixed $target = null): bool => true);

    expect(fn () => app(PartStrict::class)->run($user, null, []))->toThrow(ValidationException::class);
});

test('a record named in the input is found, its permission checked, and handed over as the record', function () {
    $mine = Notification::factory()->create();
    $user = $mine->notifiable;
    assert($user instanceof User);
    $other = Notification::factory()->create(['notifiable_id' => $user->id]);
    $foreign = Notification::factory()->create();

    $this->actingAs($user)->post("/part-test/notifications/{$mine->id}/read", ['other_id' => $foreign->id])->assertForbidden();
    expect($mine->refresh()->read_at)->toBeNull();

    $this->actingAs($user)->post("/part-test/notifications/{$mine->id}/read", ['other_id' => 'no-such-id'])->assertSessionHasErrors('other_id');
    expect($mine->refresh()->read_at)->toBeNull();

    $this->actingAs($user)->post("/part-test/notifications/{$mine->id}/read", ['other_id' => $other->id])->assertRedirect();
    expect(PartMarkRead::$seen['other_id'])->toBeInstanceOf(Notification::class)
        ->and(PartMarkRead::$seen['other_id']->id)->toBe($other->id)
        ->and($mine->refresh()->read_at)->not->toBeNull();
});

test('a record field without a rule is a programming error', function () {
    $notification = Notification::factory()->create();

    expect(fn () => app(PartBadRecords::class)->run($notification->notifiable, null, ['other_id' => $notification->id]))
        ->toThrow(LogicException::class);
});

test('the refusal sees the record as stored now, not as it was when it was loaded', function () {
    $notification = Notification::factory()->create();
    Notification::query()->whereKey($notification->id)->update(['read_at' => now()]);

    expect($notification->read_at)->toBeNull();
    expect(fn () => app(PartMarkRead::class)->run($notification->notifiable, $notification))
        ->toThrow(ValidationException::class);
});

test('a refusal that depends on the input stops the operation before any write', function () {
    $notification = Notification::factory()->create();

    expect(fn () => app(PartMarkRead::class)->run($notification->notifiable, $notification, ['note' => 'no']))
        ->toThrow(ValidationException::class);
    expect($notification->refresh()->read_at)->toBeNull();
});

test('when an operation it calls refuses, every write of the caller is undone too', function () {
    $mine = Notification::factory()->create();
    $user = $mine->notifiable;
    assert($user instanceof User);
    $alreadyRead = Notification::factory()->create(['notifiable_id' => $user->id, 'read_at' => now()]);

    expect(fn () => app(PartMarkTwo::class)->run($user, $mine, ['other_id' => $alreadyRead->id]))
        ->toThrow(ValidationException::class);
    expect($mine->refresh()->read_at)->toBeNull();
});

test('with two records in the URL, the second must belong to the first', function () {
    $mine = Notification::factory()->create();
    $owner = $mine->notifiable;
    $stranger = User::factory()->create();

    $this->actingAs($owner)->post("/part-test/users/{$stranger->id}/notifications/{$mine->id}/read")->assertNotFound();
    expect($mine->refresh()->read_at)->toBeNull();

    $this->actingAs($owner)->post("/part-test/users/{$owner->id}/notifications/{$mine->id}/read")->assertRedirect();
    expect($mine->refresh()->read_at)->not->toBeNull();
});

test('after success the operation chooses where to go', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/part-test/dashboard')->assertRedirect(route('dashboard'));

    $this->actingAs($user)->post('/part-test/sign-out')->assertRedirect(route('home'));
    $this->assertGuest();
});

test('an operation that needs a user sends a guest to sign in', function () {
    $notification = Notification::factory()->create();

    $this->post("/part-test/notifications/{$notification->id}/read")->assertRedirect(route('login'));
    expect($notification->refresh()->read_at)->toBeNull();
});

test('a userless operation runs for a guest when its ability accepts a guest', function () {
    $this->post('/part-test/guest', ['name' => 'Ann'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
    expect(PartGuestNote::$runs)->toBe(1);

    $this->post('/part-test/guest', ['name' => 'refuse'])->assertSessionHasErrors('name');
    expect(PartGuestNote::$runs)->toBe(1);

    $this->post('/part-test/users-only')->assertForbidden();
});

test('the system skips the permission, but not the input or the refusal', function () {
    app(PartSystemJob::class)->runBySystem(null, ['name' => 'Nightly']);
    expect(PartSystemJob::$runs)->toBe(1);

    expect(fn () => app(PartSystemJob::class)->runBySystem(null, []))->toThrow(ValidationException::class);
    expect(fn () => app(PartSystemJob::class)->runBySystem(null, ['name' => 'refuse']))->toThrow(ValidationException::class);
    expect(fn () => app(PartSystemJob::class)->run(null, null, ['name' => 'Nightly']))->toThrow(AuthorizationException::class);
    expect(PartSystemJob::$runs)->toBe(1);
});

test('the button flag asks the same permission and refusal', function () {
    $unread = Notification::factory()->create();
    $user = $unread->notifiable;
    assert($user instanceof User);
    $read = Notification::factory()->create(['notifiable_id' => $user->id, 'read_at' => now()]);

    expect(OperationGate::allows($user, PartMarkRead::class, $unread))->toBeTrue()
        ->and(OperationGate::allows($user, PartMarkRead::class, $read))->toBeFalse()
        ->and(OperationGate::allows(null, PartMarkRead::class, $unread))->toBeFalse()
        ->and(OperationGate::allows(null, PartGuestNote::class))->toBeTrue()
        ->and(OperationGate::allows(null, PartUsersOnly::class))->toBeFalse();
});
