<?php

namespace Tests\Unit\Enums;

use App\Enums\Concerns\ReadsChoice;

/*
| The choice-reading part (app/Enums/Concerns/ReadsChoice.php). The two enums
| below exist only for this test.
*/

enum PartSize: int
{
    use ReadsChoice;

    case Small = 10;
    case Large = 50;
}

enum PartColour: string
{
    use ReadsChoice;

    case Red = 'red';
    case Blue = 'blue';
}

test('a value that names a case gives that case, whatever the enum is backed by', function () {
    expect(PartSize::fromValue('10'))->toBe(PartSize::Small)
        ->and(PartSize::fromValue(50))->toBe(PartSize::Large)
        ->and(PartColour::fromValue('blue'))->toBe(PartColour::Blue);
});

test('anything else gives no case, never an error', function (mixed $value) {
    expect(PartSize::fromValue($value))->toBeNull()
        ->and(PartColour::fromValue($value))->toBeNull();
})->with([
    'unknown' => ['purple'],
    'a number not offered' => ['999'],
    'a number with text after it' => ['10abc'],
    'a padded number' => ['010'],
    'empty' => [''],
    'missing' => [null],
    'an array' => [['red']],
    'a float' => [10.0],
]);
