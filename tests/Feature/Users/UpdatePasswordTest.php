<?php

use App\Actions\Users\UpdatePassword;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('the new password is stored hashed', function () {
    $user = User::factory()->create();

    app(UpdatePassword::class)->handle($user, 'new-password');

    $user->refresh();
    expect($user->password)->not->toBe('new-password')
        ->and(Hash::check('new-password', $user->password))->toBeTrue();
});

test('the user is notified that their password changed', function () {
    $user = User::factory()->create();

    app(UpdatePassword::class)->handle($user, 'new-password');

    expect($user->notifications()->sole()->type)->toBe(PasswordChanged::class);
});
