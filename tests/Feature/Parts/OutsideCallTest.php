<?php

namespace Tests\Feature\Parts;

use App\Actions\Operation;
use App\Actions\OutsideCall;
use App\Jobs\OperationJob;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use LogicException;

/*
| The outside-service part's own test (app/Actions/OutsideCall.php).
| The operations below exist only for this test.
*/

uses(RefreshDatabase::class);

/** Reads an exchange rate before its transaction and keeps it. */
final class PartReadRate extends Operation
{
    public static mixed $rate = null;

    public static string $service = 'rates';

    public function ability(): string
    {
        return 'partOutside';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function prepare(User $user, ?Model $subject, array $input): array
    {
        return ['rate' => OutsideCall::read(self::$service)->get('/latest')->json('rate')];
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        self::$rate = $input['rate'];

        return null;
    }
}

/** Wrongly reads inside its transaction. */
final class PartReadInside extends Operation
{
    public function ability(): string
    {
        return 'partOutside';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        return OutsideCall::read('rates')->get('/latest')->json('rate');
    }
}

/** Sends a text message once its writes are saved. */
final class PartSendText extends Operation
{
    public function ability(): string
    {
        return 'partOutside';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        return null;
    }

    public function afterCommit(User $user, ?Model $subject, array $input, mixed $result): void
    {
        OutsideCall::change('texts')->post('/send', ['to' => $user->email]);
    }
}

beforeEach(function () {
    config([
        'services.rates' => ['url' => 'https://rates.example.test'],
        'services.texts' => ['url' => 'https://texts.example.test', 'token' => 'texts-token'],
    ]);
    Gate::define('partOutside', fn (User $user, mixed $target = null): bool => true);
    PartReadRate::$rate = null;
    PartReadRate::$service = 'rates';
});

test('a read in prepare() runs before the transaction and its answer reaches the operation', function () {
    Http::fake(['rates.example.test/*' => Http::response(['rate' => 4.25])]);

    app(PartReadRate::class)->run(User::factory()->create());

    expect(PartReadRate::$rate)->toBe(4.25);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://rates.example.test/latest');
});

test('a read inside the transaction is refused before anything is sent', function () {
    Http::fake();

    expect(fn () => app(PartReadInside::class)->run(User::factory()->create()))->toThrow(LogicException::class);
    Http::assertNothingSent();
});

test('a read is sent once more after a network failure, and not after an error answer', function () {
    Http::fake(['rates.example.test/*' => Http::sequence()->pushFailedConnection()->push(['rate' => 4.3])]);

    app(PartReadRate::class)->run(User::factory()->create());

    expect(PartReadRate::$rate)->toBe(4.3);
    Http::assertSentCount(2);
});

test('an error answer is an error, not an empty result, and is not sent again', function () {
    Http::fake(['rates.example.test/*' => Http::response('down', 500)]);

    expect(fn () => app(PartReadRate::class)->run(User::factory()->create()))->toThrow(RequestException::class);
    expect(PartReadRate::$rate)->toBeNull();
    Http::assertSentCount(1);
});

test('a change outside a background job is refused', function () {
    Http::fake();

    expect(fn () => app(PartSendText::class)->run(User::factory()->create()))->toThrow(LogicException::class);
    Http::assertNothingSent();
});

test('a change in a background job is sent once, after the writes, with the service token', function () {
    Http::fake(['texts.example.test/*' => Http::response(['id' => 7])]);
    $user = User::factory()->create();

    (new OperationJob(PartSendText::class, $user))->handle();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://texts.example.test/send'
        && $request->hasHeader('Authorization', 'Bearer texts-token')
        && $request['to'] === $user->email);
});

test('a failed change is an error and is not sent again', function () {
    Http::fake(['texts.example.test/*' => Http::response('no', 500)]);

    expect(fn () => (new OperationJob(PartSendText::class, User::factory()->create()))->handle())
        ->toThrow(RequestException::class);
    Http::assertSentCount(1);
});

test('a service with no settings is a programming error', function () {
    Http::fake();
    PartReadRate::$service = 'unknown';

    expect(fn () => app(PartReadRate::class)->run(User::factory()->create()))->toThrow(LogicException::class);
});

test('a test cannot reach a real outside service', function () {
    expect(fn () => app(PartReadRate::class)->run(User::factory()->create()))->toThrow(StrayRequestException::class);
});
