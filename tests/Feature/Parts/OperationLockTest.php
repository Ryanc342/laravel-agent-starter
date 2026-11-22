<?php

namespace Tests\Feature\Parts;

use App\Actions\Operation;
use App\Models\Notification;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Tests\Support\TestsRowLocks;

/*
| The operation part locks every record it hands to an operation, for the
| whole operation (app/Actions/OperationSteps.php). A second connection plays
| another user saving the same rows at that moment (tests/Support/TestsRowLocks.php).
*/

uses(TestsRowLocks::class);

/** Calls the test's probe in the middle of its writes. */
final class PartLockProbe extends Operation
{
    public static ?Closure $during = null;

    public function ability(): string
    {
        return 'partLock';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function rules(): array
    {
        return ['other_id' => ['nullable', 'string']];
    }

    public function records(): array
    {
        return ['other_id' => [Notification::class, 'partLock']];
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        return (self::$during)();
    }
}

beforeEach(function () {
    Gate::define('partLock', fn (User $user, mixed $target = null): bool => true);
});

test('the record in the URL stays locked until the operation ends', function () {
    $notification = Notification::factory()->create();
    $id = $notification->id;
    PartLockProbe::$during = fn (): bool => $this->waitsForLock(fn () => Notification::query()->whereKey($id)->update(['read_at' => now()]));

    expect(app(PartLockProbe::class)->run($notification->notifiable, $notification))->toBeTrue()
        ->and($notification->refresh()->read_at)->toBeNull();
});

test('a record named in the input stays locked until the operation ends', function () {
    $subject = Notification::factory()->create();
    $other = Notification::factory()->create();
    $id = $other->id;
    PartLockProbe::$during = fn (): bool => $this->waitsForLock(fn () => Notification::query()->whereKey($id)->update(['read_at' => now()]));

    expect(app(PartLockProbe::class)->run($subject->notifiable, $subject, ['other_id' => $id]))->toBeTrue()
        ->and($other->refresh()->read_at)->toBeNull();
});

test('a record nobody holds is not waited for', function () {
    $subject = Notification::factory()->create();
    $free = Notification::factory()->create();
    $id = $free->id;
    PartLockProbe::$during = fn (): bool => $this->waitsForLock(fn () => Notification::query()->whereKey($id)->update(['read_at' => now()]));

    expect(app(PartLockProbe::class)->run($subject->notifiable, $subject))->toBeFalse();
});
