<?php

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Users\ResetPassword;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('a reset by link stores the new password hashed and tells the user', function () {
    $user = User::factory()->create();

    app(ResetPassword::class)->handle($user, 'new-password');

    $user->refresh();
    expect($user->password)->not->toBe('new-password')
        ->and(Hash::check('new-password', $user->password))->toBeTrue()
        ->and($user->notifications()->sole()->type)->toBe(PasswordChanged::class);
});

test('a new password the rules refuse is not stored, and the user is not told it changed', function (array $input, string $field) {
    $user = User::factory()->create();

    try {
        app(ResetUserPassword::class)->reset($user, $input);
        $this->fail('The reset took a password the rules refuse.');
    } catch (ValidationException $refused) {
        expect($refused->errors())->toHaveKey($field);
    }

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue()
        ->and($user->notifications()->count())->toBe(0);
})->with([
    'no password' => [['password' => '', 'password_confirmation' => ''], 'password'],
    'too short' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'not confirmed' => [['password' => 'new-password', 'password_confirmation' => 'other-password'], 'password'],
]);
