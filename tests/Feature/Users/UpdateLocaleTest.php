<?php

use App\Actions\Users\UpdateLocale;
use App\Enums\Locale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the chosen language is saved on the user', function () {
    $user = User::factory()->create(['locale' => null]);

    app(UpdateLocale::class)->handle($user, Locale::ZhCn);

    expect($user->refresh()->locale)->toBe(Locale::ZhCn);

    app(UpdateLocale::class)->handle($user, Locale::En);

    expect($user->refresh()->locale)->toBe(Locale::En);
});

test('a guest has no user to save it on, so nothing changes', function () {
    $other = User::factory()->create(['locale' => null]);

    app(UpdateLocale::class)->handle(null, Locale::ZhCn);

    expect($other->refresh()->locale)->toBeNull();
});
