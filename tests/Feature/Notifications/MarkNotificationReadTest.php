<?php

use App\Actions\Notifications\MarkNotificationRead;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('an unread notification becomes read', function () {
    $notification = Notification::factory()->create();

    app(MarkNotificationRead::class)->handle($notification);

    expect($notification->refresh()->read_at)->not->toBeNull()
        ->and($notification->isUnread())->toBeFalse();
});

test('a read notification cannot be marked as read again', function () {
    $notification = Notification::factory()->read()->create(['read_at' => now()->subDay()]);
    $readAt = $notification->refresh()->read_at?->toIso8601String();

    expect(fn () => app(MarkNotificationRead::class)->handle($notification))
        ->toThrow(ValidationException::class);

    expect($notification->refresh()->read_at?->toIso8601String())->toBe($readAt);
});
