<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a signed-out visitor gets the public home page with no user, so the bar offers Log in and Get started', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome/index')
            ->where('auth.user', null));
});

test('a signed-in user stays on the home page and gets their user, so the bar offers Dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome/index')
            ->where('auth.user.id', $user->id));
});
