<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('the page uses the appearance stored in the cookie', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('home'))
        ->assertViewHas('appearance', 'dark');
});

test('an unknown appearance cookie falls back to the system setting', function () {
    $this->withUnencryptedCookie('appearance', 'purple')
        ->get(route('home'))
        ->assertViewHas('appearance', 'system');
});

test('guests are redirected to the login page', function () {
    $this->get(route('appearance.edit'))->assertRedirect(route('login'));
});

test('a signed-in user can open the appearance page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/appearance'));
});
