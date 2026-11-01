<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    $this->assertNotNull($user->fresh());
});

test('the profile page asks an unverified user to verify their email', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('emailNeedsVerification', true));
});

test('the profile page does not ask a verified user to verify again', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('emailNeedsVerification', false));
});

test('the settings address opens the profile page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('settings'))
        ->assertRedirect(route('profile.edit'));
});

test('guests are redirected to the login page', function () {
    $this->get(route('settings'))->assertRedirect(route('login'));
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->patch(route('profile.update'))->assertRedirect(route('login'));
    $this->delete(route('profile.destroy'))->assertRedirect(route('login'));
});

test('invalid profile input is rejected and changes nothing', function (array $input, string $field) {
    $user = User::factory()->create(['name' => 'Before', 'email' => 'before@example.com']);
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), $input)
        ->assertSessionHasErrors($field)
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh())
        ->name->toBe('Before')
        ->email->toBe('before@example.com');
})->with([
    'no name' => [['name' => '', 'email' => 'new@example.com'], 'name'],
    'name too long' => [['name' => str_repeat('a', 256), 'email' => 'new@example.com'], 'name'],
    'no email' => [['name' => 'After', 'email' => ''], 'email'],
    'not an email' => [['name' => 'After', 'email' => 'not-an-email'], 'email'],
    'email of another account' => [['name' => 'After', 'email' => 'taken@example.com'], 'email'],
]);
