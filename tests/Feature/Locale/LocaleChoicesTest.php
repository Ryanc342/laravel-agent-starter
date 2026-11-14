<?php

use App\Enums\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/*
| Both language switches draw their choices from the `locales` shared prop. This
| checks that the choices are exactly the languages the endpoint accepts.
*/

test('the switches offer exactly the languages the endpoint accepts', function () {
    $offered = collect(Locale::options())->pluck('value')->all();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locales', Locale::options()));

    foreach ($offered as $value) {
        $this->from(route('home'))
            ->put(route('locale.update'), ['locale' => $value])
            ->assertSessionHasNoErrors();
    }

    expect($offered)->toBe(array_map(fn (Locale $locale): string => $locale->value, Locale::cases()));

    $this->from(route('home'))
        ->put(route('locale.update'), ['locale' => 'not-offered'])
        ->assertSessionHasErrors('locale');
});

test('each choice carries the language\'s own name and its short label', function () {
    expect(Locale::options())->toBe([
        ['value' => 'en', 'label' => 'English', 'short' => 'EN'],
        ['value' => 'zh_CN', 'label' => '中文', 'short' => '中文'],
    ]);
});
