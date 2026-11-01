<?php

use App\Actions\Users\CreateUser;
use App\Notifications\AccountCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('a new user is stored with a hashed password and an unverified email', function () {
    $user = app(CreateUser::class)->handle([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $user->refresh();
    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('a new user gets a welcome notification', function () {
    $user = app(CreateUser::class)->handle([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    expect($user->notifications()->sole()->type)->toBe(AccountCreated::class);
});
