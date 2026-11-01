<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/confirm-password'),
    );
});

test('password confirmation requires authentication', function () {
    $response = $this->get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});

test('password confirmation submission requires authentication', function () {
    $this->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(route('login'))
        ->assertSessionMissing('auth.password_confirmed_at');
});

test('correct password confirms the session and returns to the intended page', function () {
    $this->freezeTime();
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['url.intended' => route('security.edit')])
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(route('security.edit'))
        ->assertSessionHas('auth.password_confirmed_at', now()->timestamp);
});

test('invalid password does not confirm the session', function (string $password) {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('password.confirm.store'), ['password' => $password])
        ->assertSessionHasErrors('password')
        ->assertSessionMissing('auth.password_confirmed_at');
})->with(['wrong-password', '']);

test('the password status endpoint says whether the password was confirmed recently', function () {
    $this->getJson(route('password.confirmation'))->assertUnauthorized();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('password.confirmation'))
        ->assertOk()
        ->assertJson(['confirmed' => false]);

    $this->withSession(['auth.password_confirmed_at' => time()])
        ->getJson(route('password.confirmation'))
        ->assertOk()
        ->assertJson(['confirmed' => true]);
});
