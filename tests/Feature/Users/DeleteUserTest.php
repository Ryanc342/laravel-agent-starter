<?php

use App\Actions\Users\DeleteUser;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('deleting a user removes the account', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($user);

    app(DeleteUser::class)->handle($user);

    $this->assertModelMissing($user);
    $this->assertModelExists($other);
});

test('deleting a user removes their notifications and keeps everyone else\'s', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user, 'notifiable')->count(2)->create();
    $other = Notification::factory()->create();
    $this->actingAs($user);

    app(DeleteUser::class)->handle($user);

    expect(Notification::query()->ownedBy($user)->count())->toBe(0);
    $this->assertModelExists($other);
});

test('an account whose email is not verified yet cannot be deleted', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    expect(fn () => app(DeleteUser::class)->handle($user))
        ->toThrow(ValidationException::class);
    $this->assertModelExists($user);
});
