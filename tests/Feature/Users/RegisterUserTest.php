<?php

use App\Actions\Users\RegisterUser;
use App\Notifications\AccountCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

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
    $user = app(RegisterUser::class)->handle(...Arr::only(registerUserInput(), ['name', 'email', 'password']));

    $user->refresh();
    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('a new user gets a welcome notification', function () {
    $user = app(RegisterUser::class)->handle(...Arr::only(registerUserInput(), ['name', 'email', 'password']));

    expect($user->notifications()->sole()->type)->toBe(AccountCreated::class);
});
