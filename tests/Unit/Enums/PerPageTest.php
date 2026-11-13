<?php

use App\Enums\PerPage;

test('a list offers 10, 20, 50 or 100 rows per page', function () {
    expect(PerPage::values())->toBe([10, 20, 50, 100]);
});

test('the query value becomes a PerPage, and anything else means 20', function (mixed $value, PerPage $expected) {
    expect(PerPage::fromQuery($value))->toBe($expected);
})->with([
    'ten' => ['10', PerPage::Ten],
    'twenty' => ['20', PerPage::Twenty],
    'fifty' => ['50', PerPage::Fifty],
    'a hundred' => ['100', PerPage::Hundred],
    'a number that is not offered' => ['999', PerPage::Twenty],
    'zero' => ['0', PerPage::Twenty],
    'negative' => ['-5', PerPage::Twenty],
    'text' => ['abc', PerPage::Twenty],
    'a number with text after it' => ['50abc', PerPage::Twenty],
    'empty' => ['', PerPage::Twenty],
    'missing' => [null, PerPage::Twenty],
    'an array' => [['10'], PerPage::Twenty],
]);
