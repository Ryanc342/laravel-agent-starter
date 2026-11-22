<?php

use App\Actions\Users\RegisterUser;
use App\Models\User;
use App\Notifications\AccountCreated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function registerUserInput(array $input = []): array
{
    return [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        ...$input,
    ];
}

test('a new user is stored with a hashed password and an unverified email', function () {
    $user = app(RegisterUser::class)->run(null, null, registerUserInput());

    $user->refresh();
    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('a new user gets a welcome notification', function () {
    $user = app(RegisterUser::class)->run(null, null, registerUserInput());

    expect($user->notifications()->sole()->type)->toBe(AccountCreated::class);
});

test('an email address that already has an account is refused, and nothing is stored', function () {
    User::factory()->create(['email' => 'test@example.com']);

    expect(fn () => app(RegisterUser::class)->run(null, null, registerUserInput()))
        ->toThrow(ValidationException::class);
    expect(User::query()->count())->toBe(1);
});

test('someone who is signed in cannot register another account', function () {
    $user = User::factory()->create();

    expect(fn () => app(RegisterUser::class)->run($user, null, registerUserInput()))
        ->toThrow(AuthorizationException::class);
});
