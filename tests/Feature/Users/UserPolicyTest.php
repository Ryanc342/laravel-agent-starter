<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user may change and delete only their own account', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect($user->can('update', $user))->toBeTrue()
        ->and($user->can('delete', $user))->toBeTrue()
        ->and($user->can('update', $other))->toBeFalse()
        ->and($user->can('delete', $other))->toBeFalse();
});
