<?php

use App\Enums\Locale;

test('a browser that prefers any Chinese gets zh_CN, anything else English', function (?string $header, Locale $expected) {
    expect(Locale::fromBrowser($header))->toBe($expected);
})->with([
    'simplified' => ['zh-CN,zh;q=0.9,en;q=0.8', Locale::ZhCn],
    'traditional' => ['zh-TW,zh;q=0.9', Locale::ZhCn],
    'plain zh' => ['zh', Locale::ZhCn],
    'english first' => ['en-US,en;q=0.9,zh;q=0.5', Locale::En],
    'chinese ranked above english by q' => ['en;q=0.4,zh-HK;q=0.9', Locale::ZhCn],
    'another language' => ['fr-FR,fr;q=0.9', Locale::En],
    'a header with nothing usable' => ['*', Locale::En],
    'no header' => [null, Locale::En],
    'empty header' => ['', Locale::En],
]);

test('the language is the user\'s choice, then the cookie, then the browser, then English', function () {
    expect(Locale::resolve('zh_CN', 'en', 'en-US'))->toBe(Locale::ZhCn)
        ->and(Locale::resolve(null, 'zh_CN', 'en-US'))->toBe(Locale::ZhCn)
        ->and(Locale::resolve(null, null, 'zh-CN'))->toBe(Locale::ZhCn)
        ->and(Locale::resolve(null, null, null))->toBe(Locale::En)
        ->and(Locale::resolve('en', 'zh_CN', 'zh-CN'))->toBe(Locale::En);
});

test('an unknown stored value is skipped and the next source answers', function () {
    expect(Locale::resolve('fr', 'zh_CN', null))->toBe(Locale::ZhCn)
        ->and(Locale::resolve('fr', 'de', 'zh'))->toBe(Locale::ZhCn)
        ->and(Locale::resolve(['zh_CN'], null, null))->toBe(Locale::En);
});

test('each language has the name it is written in', function () {
    expect(Locale::En->nativeName())->toBe('English')
        ->and(Locale::ZhCn->nativeName())->toBe('中文');
});
