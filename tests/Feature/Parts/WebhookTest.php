<?php

namespace Tests\Feature\Parts;

use App\Actions\UserlessOperation;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use LogicException;

/*
| Calls another system sends here (App\Http\Controllers\WebhookController,
| Route::webhook()). The operation below exists only for this test.
*/

uses(RefreshDatabase::class);

/** Records a payment another system reports; nobody may run it but the system. */
final class PartPaymentDone extends UserlessOperation
{
    /** @var array<string, mixed>|null */
    public static ?array $seen = null;

    public function ability(): string
    {
        return 'partNobodyHook';
    }

    public function subjectClass(): string
    {
        return Notification::class;
    }

    public function rules(): array
    {
        return ['order' => ['required', 'string']];
    }

    protected function perform(?Model $subject, array $input): mixed
    {
        self::$seen = $input;

        return null;
    }
}

beforeEach(function () {
    config(['services.pay' => ['webhook_secret' => 'pay-secret'], 'services.unsigned' => []]);
    Gate::define('partNobodyHook', fn (?User $user, mixed $target = null): bool => false);
    Route::webhook('part-test/hooks/pay', 'pay', PartPaymentDone::class);
    Route::webhook('part-test/hooks/unsigned', 'unsigned', PartPaymentDone::class);
    PartPaymentDone::$seen = null;
});

/**
 * @param  array<string, mixed>  $data
 */
function sendHook(string $uri, array $data, ?string $signature): TestResponse
{
    $body = (string) json_encode($data);
    $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

    if ($signature !== null) {
        $headers['HTTP_X_SIGNATURE_256'] = $signature;
    }

    return test()->call('POST', $uri, [], [], [], $headers, $body);
}

test('a call with the right signature runs the operation as the system', function () {
    $body = (string) json_encode(['order' => 'A-1']);

    sendHook('/part-test/hooks/pay', ['order' => 'A-1'], 'sha256='.hash_hmac('sha256', $body, 'pay-secret'))
        ->assertOk()
        ->assertJson(['received' => true]);

    expect(PartPaymentDone::$seen)->toBe(['order' => 'A-1']);
});

test('a wrong or missing signature is refused and nothing runs', function () {
    sendHook('/part-test/hooks/pay', ['order' => 'A-1'], 'sha256='.hash_hmac('sha256', 'something else', 'pay-secret'))
        ->assertForbidden();
    sendHook('/part-test/hooks/pay', ['order' => 'A-1'], null)->assertForbidden();

    expect(PartPaymentDone::$seen)->toBeNull();
});

test('the operation still checks the input', function () {
    $body = (string) json_encode(['nothing' => true]);

    sendHook('/part-test/hooks/pay', ['nothing' => true], hash_hmac('sha256', $body, 'pay-secret'))
        ->assertUnprocessable();

    expect(PartPaymentDone::$seen)->toBeNull();
});

test('a service without a webhook secret is a programming error', function () {
    $this->withoutExceptionHandling();

    expect(fn () => sendHook('/part-test/hooks/unsigned', ['order' => 'A-1'], 'anything'))->toThrow(LogicException::class);
    expect(PartPaymentDone::$seen)->toBeNull();
});
