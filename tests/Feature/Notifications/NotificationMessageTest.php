<?php

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a stored sentence is shown with its placeholders filled', function () {
    $notification = Notification::factory()->create([
        'data' => ['message' => 'Your email address is now :email.', 'values' => ['email' => 'ann@example.com']],
    ]);

    expect($notification->message())->toBe('Your email address is now ann@example.com.');
});

test('a stored notification without its sentence is an error, never an empty line', function () {
    $notification = Notification::factory()->create(['data' => ['values' => []]]);

    expect(fn () => $notification->message())->toThrow(UnexpectedValueException::class);
});
