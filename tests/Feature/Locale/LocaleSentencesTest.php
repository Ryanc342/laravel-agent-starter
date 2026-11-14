<?php

use App\Enums\Locale;

test('the current language is the one the application runs in, English when unknown', function () {
    app()->setLocale('zh_CN');
    expect(Locale::current())->toBe(Locale::ZhCn);

    app()->setLocale('en');
    expect(Locale::current())->toBe(Locale::En);

    app()->setLocale('fr');
    expect(Locale::current())->toBe(Locale::En);

    app()->setLocale('en');
});

test('English sends no sentences and Chinese sends its table', function () {
    expect(Locale::En->sentences())->toBe([])
        ->and(Locale::ZhCn->sentences()['Save changes'])->toBe('保存更改');
});
