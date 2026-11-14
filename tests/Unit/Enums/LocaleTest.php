<?php

use App\Enums\Locale;

test('a browser whose first choice is any Chinese gets zh_CN, anything else English', function (array $languages, Locale $expected) {
    expect(Locale::fromBrowser($languages))->toBe($expected);
})->with([
    'simplified' => [['zh_CN', 'zh', 'en'], Locale::ZhCn],
    'traditional' => [['zh_TW', 'zh'], Locale::ZhCn],
    'plain zh' => [['zh'], Locale::ZhCn],
    'english first' => [['en_US', 'en', 'zh'], Locale::En],
    'another language' => [['fr_FR', 'fr'], Locale::En],
    'a language that only starts with zh' => [['zha'], Locale::En],
    'a wildcard is skipped' => [['*', 'zh'], Locale::ZhCn],
    'only a wildcard' => [['*'], Locale::En],
    'nothing asked for' => [[], Locale::En],
]);

test('the language is the user\'s choice, then the cookie, then the browser, then English', function () {
    expect(Locale::resolve('zh_CN', 'en', ['en_US']))->toBe(Locale::ZhCn)
        ->and(Locale::resolve(null, 'zh_CN', ['en_US']))->toBe(Locale::ZhCn)
        ->and(Locale::resolve(null, null, ['zh_CN']))->toBe(Locale::ZhCn)
        ->and(Locale::resolve(null, null, []))->toBe(Locale::En)
        ->and(Locale::resolve('en', 'zh_CN', ['zh_CN']))->toBe(Locale::En);
});

test('an unknown stored value is skipped and the next source answers', function () {
    expect(Locale::resolve('fr', 'zh_CN', []))->toBe(Locale::ZhCn)
        ->and(Locale::resolve('fr', 'de', ['zh']))->toBe(Locale::ZhCn)
        ->and(Locale::resolve(['zh_CN'], null, []))->toBe(Locale::En);
});

test('each language has the name it is written in', function () {
    expect(Locale::En->nativeName())->toBe('English')
        ->and(Locale::ZhCn->nativeName())->toBe('中文');
});

test('each language has the short label of the guest switch', function () {
    expect(Locale::En->shortName())->toBe('EN')
        ->and(Locale::ZhCn->shortName())->toBe('中文');
});
