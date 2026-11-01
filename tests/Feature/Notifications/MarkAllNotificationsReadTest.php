<?php

use App\Actions\Notifications\MarkAllNotificationsRead;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    $other = Notification::factory()->create();

    app(MarkAllNotificationsRead::class)->handle($user);

    expect($other->refresh()->read_at)->toBeNull();
});
