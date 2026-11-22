<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

test('only a guest may register', function () {
    expect(Gate::forUser(null)->allows('register', User::class))->toBeTrue()
        ->and(Gate::forUser(User::factory()->create())->allows('register', User::class))->toBeFalse();
});

test('a signed-in user may change and delete their own account', function () {
    $user = User::factory()->create();

    expect($user->can('updateProfile', User::class))->toBeTrue()
        ->and($user->can('updatePassword', User::class))->toBeTrue()
        ->and($user->can('deleteAccount', User::class))->toBeTrue();
});

test('a new password is set by link for anyone, and by a signed-in user only for themselves', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect(Gate::forUser(null)->allows('resetPassword', $user))->toBeTrue()
        ->and($user->can('resetPassword', $user))->toBeTrue()
        ->and($user->can('resetPassword', $other))->toBeFalse();
});
