<?php

use App\Actions\Users\ChangePassword;
use App\Actions\Users\ResetPassword;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

$newPassword = ['password' => 'new-password', 'password_confirmation' => 'new-password'];

test('a reset by link stores the new password hashed and tells the user', function () use ($newPassword) {
    $user = User::factory()->create();

    app(ResetPassword::class)->run(null, $user, $newPassword);

    $user->refresh();
    expect($user->password)->not->toBe('new-password')
        ->and(Hash::check('new-password', $user->password))->toBeTrue()
        ->and($user->notifications()->sole()->type)->toBe(PasswordChanged::class);
});

test('a signed-in user cannot set the password of another user', function () use ($newPassword) {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect(fn () => app(ResetPassword::class)->run($user, $other, $newPassword))
        ->toThrow(AuthorizationException::class);
    expect(Hash::check('password', $other->refresh()->password))->toBeTrue();
});

test('changing the password needs the current one, then sets and tells the same way as a reset', function () use ($newPassword) {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(fn () => app(ChangePassword::class)->run($user, null, ['current_password' => 'wrong', ...$newPassword]))
        ->toThrow(ValidationException::class);
    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();

    app(ChangePassword::class)->run($user, null, ['current_password' => 'password', ...$newPassword]);

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue()
        ->and($user->notifications()->sole()->type)->toBe(PasswordChanged::class);
});

test('a new password the rules refuse is not stored, and the user is not told it changed', function (array $input, string $field) {
    $user = User::factory()->create();

    try {
        app(ResetPassword::class)->run(null, $user, $input);
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
