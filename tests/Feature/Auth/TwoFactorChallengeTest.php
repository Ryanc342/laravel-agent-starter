<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
});

test('two factor challenge redirects to login when not authenticated', function () {
    $response = $this->get(route('two-factor.login'));

    $response->assertRedirect(route('login'));
});

test('two factor challenge can be rendered', function () {
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->get(route('two-factor.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/two-factor-challenge'),
        );
});

test('a recovery code completes login and is replaced after use', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'));

    $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->recoveryCodes())->not->toContain('recovery-code-1');
});

test('an invalid recovery code does not complete login or replace codes', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('two-factor.login.store'), ['recovery_code' => 'wrong-recovery-code'])
        ->assertSessionHasErrors('recovery_code');

    $this->assertGuest();
    expect($user->refresh()->recoveryCodes())->toBe(['recovery-code-1']);
});

test('an authenticator code completes login and keeps the recovery codes', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));

    $this->post(route('two-factor.login.store'), ['code' => $code])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->recoveryCodes())->toBe(['recovery-code-1']);
});

test('a wrong authenticator code does not complete login', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('two-factor.login.store'), ['code' => 'invalid'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});
