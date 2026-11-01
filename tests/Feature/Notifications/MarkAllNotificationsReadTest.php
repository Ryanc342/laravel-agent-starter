<?php

use App\Actions\Notifications\MarkAllNotificationsRead;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('every unread notification of the user becomes read and the number is returned', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user, 'notifiable')->count(2)->create();
    Notification::factory()->for($user, 'notifiable')->read()->create();

    $marked = app(MarkAllNotificationsRead::class)->handle($user);

    expect($marked)->toBe(2)
        ->and(Notification::query()->ownedBy($user)->unread()->count())->toBe(0);
});

test('another user\'s notifications stay unread', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user, 'notifiable')->create();
    $other = Notification::factory()->create();

    app(MarkAllNotificationsRead::class)->handle($user);

    expect($other->refresh()->read_at)->toBeNull();
});

test('with nothing unread it refuses and changes nothing', function () {
    $user = User::factory()->create();
    $read = Notification::factory()->for($user, 'notifiable')->read()->create();
    $readAt = $read->read_at;

    expect(fn () => app(MarkAllNotificationsRead::class)->handle($user))
        ->toThrow(ValidationException::class);

    expect($read->refresh()->read_at?->toIso8601String())->toBe($readAt?->toIso8601String());
});
