<?php

use App\Enums\Appearance;

test('the theme cookie becomes an Appearance, and a missing or unknown value means system', function (mixed $cookie, Appearance $expected) {
    expect(Appearance::fromCookie($cookie))->toBe($expected);
})->with([
    'light' => ['light', Appearance::Light],
    'dark' => ['dark', Appearance::Dark],
    'system' => ['system', Appearance::System],
    'unknown value' => ['purple', Appearance::System],
    'missing' => [null, Appearance::System],
    'not a string' => [['dark'], Appearance::System],
]);
