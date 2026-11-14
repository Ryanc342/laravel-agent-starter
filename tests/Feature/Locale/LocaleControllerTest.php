<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

/** A users' policy that refuses the language switch, to show the controller asks it. */
class DenyingLocalePolicy
{
    public function updateLocale(?User $user): bool
    {
        return false;
    }
}

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

test('a guest and a user may both switch the language', function () {
    $this->put(route('locale.update'), ['locale' => 'zh_CN'])->assertRedirect();

    $this->actingAs(User::factory()->create())
        ->put(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect();
});

test('a policy that denies the switch fails the request', function () {
    Gate::policy(User::class, DenyingLocalePolicy::class);

    $this->put(route('locale.update'), ['locale' => 'zh_CN'])->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->put(route('locale.update'), ['locale' => 'zh_CN'])
        ->assertForbidden();
});
