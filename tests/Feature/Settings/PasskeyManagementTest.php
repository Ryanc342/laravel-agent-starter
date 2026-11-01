<?php

use App\Models\User;
use Database\Factories\PasskeyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passkeys\Passkey;
use Tests\Support\VirtualPasskey;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['passkeys.relying_party_id' => 'localhost', 'passkeys.allowed_origins' => ['https://localhost']]);
});

function passkeyRegisteredFor(User $user, VirtualPasskey $authenticator): Passkey
{
    $options = test()->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->getJson(route('passkey.registration-options'))->assertOk()->json('options');
    test()->postJson(route('passkey.store'), [
        'name' => 'Test authenticator', 'credential' => $authenticator->registration($options),
    ])->assertOk()->assertJsonPath('name', 'Test authenticator');

    return $user->passkeys()->firstOrFail();
}

test('passkey management requires authentication', function (string $method, string $route) {
    $passkey = PasskeyFactory::new()->create();

    $this->{$method}(route($route, $passkey))->assertRedirect(route('login'));

    $this->assertModelExists($passkey);
})->with([
    ['get', 'passkey.registration-options'], ['post', 'passkey.store'], ['delete', 'passkey.destroy'],
    ['get', 'passkey.confirm-options'], ['post', 'passkey.confirm'],
]);

test('passkey registration and deletion require password confirmation', function (string $method, string $route) {
    $passkey = PasskeyFactory::new()->create();
    $user = User::query()->findOrFail($passkey->user_id);

    $this->actingAs($user)->{$method}(route($route, $passkey))->assertRedirect(route('password.confirm'));

    $this->assertModelExists($passkey);
})->with([
    ['get', 'passkey.registration-options'], ['post', 'passkey.store'], ['delete', 'passkey.destroy'],
]);

test('a valid authenticator registers a passkey for only the signed in account', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $passkey = passkeyRegisteredFor($user, new VirtualPasskey);

    expect($passkey->user_id)->toBe($user->id)->and($passkey->credential)->toHaveKey('credentialPublicKey');
    $this->assertDatabaseCount('passkeys', 1);
    expect($other->passkeys()->exists())->toBeFalse();
});

test('invalid passkey registration creates no credential', function (array $input) {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->postJson(route('passkey.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('credential');

    $this->assertDatabaseCount('passkeys', 0);
})->with([
    'missing credential' => [['name' => 'Test authenticator']],
    'malformed credential' => [['name' => 'Test authenticator', 'credential' => [
        'id' => 'invalid', 'rawId' => 'invalid', 'type' => 'public-key', 'response' => ['attestationObject' => 'invalid'],
    ]]],
]);

test('an expired passkey registration challenge is rejected', function () {
    $user = User::factory()->create();
    $authenticator = new VirtualPasskey;
    $options = $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->getJson(route('passkey.registration-options'))->assertOk()->json('options');

    $this->withSession(['passkey.registration_options' => null])->postJson(route('passkey.store'), [
        'name' => 'Test authenticator', 'credential' => $authenticator->registration($options),
    ])->assertUnprocessable()->assertJsonValidationErrors('credential');

    $this->assertDatabaseCount('passkeys', 0);
});

test('passkeys can only be deleted by their owner', function (bool $own) {
    $passkey = PasskeyFactory::new()->create();
    $user = $own ? User::query()->findOrFail($passkey->user_id) : User::factory()->create();

    $response = $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->deleteJson(route('passkey.destroy', $passkey));

    if ($own) {
        $response->assertOk();
        $this->assertModelMissing($passkey);
    } else {
        $response->assertForbidden();
        $this->assertModelExists($passkey);
    }
})->with([true, false]);

test('the security page only lists the signed in users passkeys', function () {
    $passkey = PasskeyFactory::new()->create();
    PasskeyFactory::new()->create();
    $user = User::query()->findOrFail($passkey->user_id);

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->get(route('security.edit'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('passkeys.data', 1)->where('passkeys.data.0.id', $passkey->id));
});

test('passkey verification confirms the session with a real signature', function () {
    $this->freezeTime();
    $user = User::factory()->create();
    $authenticator = new VirtualPasskey;
    $passkey = passkeyRegisteredFor($user, $authenticator);
    $options = $this->getJson(route('passkey.confirm-options'))->assertOk()->json('options');

    $this->withSession(['auth.password_confirmed_at' => null])->postJson(route('passkey.confirm'), [
        'credential' => $authenticator->assertion($options, $user->getPasskeyUserHandle()),
    ])->assertOk()->assertSessionHas('auth.password_confirmed_at', now()->timestamp);

    expect($passkey->refresh()->last_used_at)->not->toBeNull();
});

test('a passkey from another account cannot confirm the signed in session', function () {
    $owner = User::factory()->create();
    $authenticator = new VirtualPasskey;
    $passkey = passkeyRegisteredFor($owner, $authenticator);
    $other = User::factory()->create();
    $options = $this->actingAs($other)->withSession(['auth.password_confirmed_at' => null])
        ->getJson(route('passkey.confirm-options'))->assertOk()->json('options');

    $this->postJson(route('passkey.confirm'), [
        'credential' => $authenticator->assertion($options, $owner->getPasskeyUserHandle()),
    ])->assertUnprocessable()->assertJsonValidationErrors('credential')->assertSessionMissing('auth.password_confirmed_at');

    expect($passkey->refresh()->last_used_at)->toBeNull();
});

test('a registered passkey can sign its owner in', function () {
    $user = User::factory()->create();
    $authenticator = new VirtualPasskey;
    $passkey = passkeyRegisteredFor($user, $authenticator);
    $this->post(route('logout'));
    $options = $this->getJson(route('passkey.login-options'))->assertOk()->json('options');

    $this->postJson(route('passkey.login'), [
        'credential' => $authenticator->assertion($options, $user->getPasskeyUserHandle()),
    ])->assertOk();

    $this->assertAuthenticatedAs($user);
    expect($passkey->refresh()->last_used_at)->not->toBeNull();
});

test('missing passkey login credential does not authenticate a session', function () {
    $this->getJson(route('passkey.login-options'))->assertOk();

    $this->postJson(route('passkey.login'), [])
        ->assertUnprocessable()->assertJsonValidationErrors('credential');

    $this->assertGuest();
    $this->assertDatabaseCount('passkeys', 0);
});

test('a passkey signature from another key is refused, not a server error', function () {
    $user = User::factory()->create();
    $authenticator = new VirtualPasskey;
    $passkey = passkeyRegisteredFor($user, $authenticator);
    $options = $this->withSession(['auth.password_confirmed_at' => null])
        ->getJson(route('passkey.confirm-options'))->assertOk()->json('options');

    $credential = $authenticator->assertion($options, $user->getPasskeyUserHandle());
    $credential['response']['signature'] = (new VirtualPasskey)->assertion($options, $user->getPasskeyUserHandle())['response']['signature'];

    $this->postJson(route('passkey.confirm'), ['credential' => $credential])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('credential')
        ->assertSessionMissing('auth.password_confirmed_at');

    expect($passkey->refresh()->last_used_at)->toBeNull();
});
