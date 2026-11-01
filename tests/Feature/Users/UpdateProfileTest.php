<?php

use App\Actions\Users\UpdateProfile;
use App\Models\User;
use App\Notifications\EmailChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a changed email address becomes unverified', function () {
    $user = User::factory()->create();

    app(UpdateProfile::class)->handle($user, ['name' => 'New Name', 'email' => 'new@example.com']);

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

test('an unchanged email address stays verified', function () {
    $user = User::factory()->create();

    app(UpdateProfile::class)->handle($user, ['name' => 'New Name', 'email' => $user->email]);

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->email_verified_at)->not->toBeNull();
});

test('the user is notified when their email address changes', function () {
    $user = User::factory()->create();

    app(UpdateProfile::class)->handle($user, ['name' => $user->name, 'email' => 'new@example.com']);

    $notification = $user->notifications()->sole();
    expect($notification->type)->toBe(EmailChanged::class)
        ->and($notification->message())->toContain('new@example.com');
});

test('no notification is sent when the email address is unchanged', function () {
    $user = User::factory()->create();

    app(UpdateProfile::class)->handle($user, ['name' => 'New Name', 'email' => $user->email]);

    expect($user->notifications()->count())->toBe(0);
});
