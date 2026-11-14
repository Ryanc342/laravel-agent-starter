<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest\'s choice is saved in the cookie and the page comes back', function () {
    $this->from(route('home'))
        ->put(route('locale.update'), ['locale' => 'zh_CN'])
        ->assertRedirect(route('home'))
        ->assertCookie('locale', 'zh_CN', false);
});

test('a signed-in user\'s choice is saved on the user and in the cookie', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)
        ->from(route('appearance.edit'))
        ->put(route('locale.update'), ['locale' => 'zh_CN'])
        ->assertRedirect(route('appearance.edit'))
        ->assertCookie('locale', 'zh_CN', false);

    expect($user->refresh()->locale?->value)->toBe('zh_CN');
});

test('the choice survives signing in on another browser', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)->put(route('locale.update'), ['locale' => 'zh_CN']);

    $this->actingAs($user->refresh())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('locale', 'zh_CN'));
});

test('only the two languages are accepted', function (mixed $locale) {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)
        ->from(route('appearance.edit'))
        ->put(route('locale.update'), ['locale' => $locale])
        ->assertRedirect(route('appearance.edit'))
        ->assertSessionHasErrors('locale')
        ->assertCookieMissing('locale');

    expect($user->refresh()->locale)->toBeNull();
})->with(['unknown' => 'fr', 'empty' => '', 'missing' => null, 'array' => [['en']]]);
