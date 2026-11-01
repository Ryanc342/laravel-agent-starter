<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

test('two factor management requires login', function (string $method, string $route) {
    $this->{$method}(route($route))->assertRedirect(route('login'));
})->with([
    ['post', 'two-factor.enable'],
    ['post', 'two-factor.confirm'],
    ['delete', 'two-factor.disable'],
    ['get', 'two-factor.qr-code'],
    ['get', 'two-factor.secret-key'],
    ['get', 'two-factor.recovery-codes'],
    ['post', 'two-factor.regenerate-recovery-codes'],
]);

test('two factor management requires a recently confirmed password', function (string $method, string $route) {
    $user = User::factory()->withTwoFactor()->create();
    $original = $user->only(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);

    $this->actingAs($user)->{$method}(route($route))->assertRedirect(route('password.confirm'));

    expect($user->refresh()->only(array_keys($original)))->toEqual($original);
})->with([
    ['post', 'two-factor.enable'],
    ['post', 'two-factor.confirm'],
    ['delete', 'two-factor.disable'],
    ['get', 'two-factor.qr-code'],
    ['get', 'two-factor.secret-key'],
    ['get', 'two-factor.recovery-codes'],
    ['post', 'two-factor.regenerate-recovery-codes'],
]);

test('two factor setup confirms a real code and only changes the signed in account', function () {
    $this->freezeTime();
    // Google2FA reads PHP time directly: replace only its clock, keeping real TOTP verification.
    $engine = Mockery::mock(Google2FA::class)->makePartial();
    $engine->shouldReceive('getTimestamp')->andReturn(60000000);
    $this->instance(Google2FA::class, $engine);
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->postJson(route('two-factor.enable'))->assertSuccessful();

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->recoveryCodes())->toHaveCount(8);
    $this->getJson(route('two-factor.qr-code'))->assertOk()->assertJsonStructure(['svg', 'url']);
    $this->getJson(route('two-factor.secret-key'))->assertOk()->assertJsonPath('secretKey', decrypt($user->two_factor_secret));
    $code = $engine->getCurrentOtp(decrypt($user->two_factor_secret));
    $this->postJson(route('two-factor.confirm'), ['code' => $code])->assertOk();

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue()
        ->and($other->refresh()->two_factor_secret)->toBeNull();
});

test('invalid two factor confirmation leaves setup unconfirmed', function (string $code) {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->postJson(route('two-factor.enable'))->assertSuccessful();

    $this->post(route('two-factor.confirm'), ['code' => $code])
        ->assertSessionHasErrorsIn('confirmTwoFactorAuthentication', 'code');

    expect($user->refresh()->two_factor_confirmed_at)->toBeNull();
})->with(['', 'invalid']);

test('recovery codes can be read and regenerated for only the signed in account', function () {
    $user = User::factory()->withTwoFactor()->create();
    $other = User::factory()->withTwoFactor()->create();
    $original = $user->recoveryCodes();
    $otherCodes = $other->recoveryCodes();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->getJson(route('two-factor.recovery-codes'))->assertOk()->assertExactJson($original);
    $this->postJson(route('two-factor.regenerate-recovery-codes'))->assertSuccessful();

    expect($user->refresh()->recoveryCodes())->toHaveCount(8)->not->toBe($original)
        ->and($other->refresh()->recoveryCodes())->toBe($otherCodes);
});

test('disabling two factor authentication clears only the signed in accounts secrets', function () {
    $user = User::factory()->withTwoFactor()->create();
    $other = User::factory()->withTwoFactor()->create();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->deleteJson(route('two-factor.disable'))->assertSuccessful();

    expect($user->refresh()->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($other->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});
