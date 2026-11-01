<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('invalid registration creates no account or notification', function (array $input, array $fields) {
    User::factory()->create(['email' => 'existing@example.test']);
    Notification::fake();

    $this->post(route('register.store'), array_replace([
        'name' => 'New User',
        'email' => 'new@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $input))->assertSessionHasErrors($fields);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
    Notification::assertNothingSent();
})->with([
    'missing fields' => [['name' => '', 'email' => '', 'password' => ''], ['name', 'email', 'password']],
    'invalid email' => [['email' => 'invalid'], ['email']],
    'duplicate email' => [['email' => 'existing@example.test'], ['email']],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], ['password']],
    'unconfirmed password' => [['password_confirmation' => 'different'], ['password']],
]);
