<?php

/*
| Every sentence a user reads is translated (docs/rules/translations.md,
| docs/golden-principles.md principle 10). These checks read the code and the
| language files; the oxlint rule ezdev/no-untranslated-text catches text
| written straight into JSX.
*/

use Tests\Structure\ProjectFiles;

/**
 * The string literal that starts at the opening quote, unescaped, or null when
 * it is not a plain literal (a template with ${...}, a variable, a call).
 *
 * @return array{0: string, 1: int}|null the text and the offset after the literal
 */
function translationsLiteral(string $code, int $at): ?array
{
    $quote = $code[$at] ?? '';

    if (! in_array($quote, ["'", '"', '`'], true)) {
        return null;
    }

    $text = '';

    for ($i = $at + 1, $length = strlen($code); $i < $length; $i++) {
        $character = $code[$i];

        if ($character === '\\') {
            $next = $code[$i + 1] ?? '';
            $text .= match ($next) {
                'n' => "\n",
                't' => "\t",
                default => $next,
            };
            $i++;

            continue;
        }

        if ($character === $quote) {
            return [$text, $i + 1];
        }

        if ($quote === '`' && $character === '$' && ($code[$i + 1] ?? '') === '{') {
            return null;
        }

        $text .= $character;
    }

    return null;
}

/**
 * Every sentence the code asks to translate, keyed by its text, with where it
 * was found: t(), tChoice(), msg() in React; __(), trans(), trans_choice(),
 * Lang::get(), @lang() and sentence() (a stored notification) in PHP and Blade.
 *
 * @param  list<string>  $files
 * @return array<string, string> sentence => first file
 */
function translationsUsed(array $files): array
{
    $used = [];
    $call = '/(?<![\w$>.])(?:t|tChoice|msg|__|trans|trans_choice)\s*\(\s*(?=[\'"`])|\$this->sentence\(\s*(?=[\'"`])|Lang::get\(\s*(?=[\'"`])|@lang\(\s*(?=[\'"`])/';

    foreach ($files as $file) {
        $code = ProjectFiles::read($file);

        if (preg_match_all($call, $code, $matches, PREG_OFFSET_CAPTURE) === 0) {
            continue;
        }

        foreach ($matches[0] as [$found, $offset]) {
            $literal = translationsLiteral($code, $offset + strlen($found));

            if ($literal !== null) {
                $used[$literal[0]] ??= $file;
            }
        }
    }

    return $used;
}

/**
 * The files of the app that hold sentences a user reads.
 *
 * @return list<string>
 */
function translationsAppFiles(): array
{
    $files = ProjectFiles::in(
        ['app', 'resources/views', 'resources/js', 'routes', 'docs/reference/tasks'],
        ['resources/js/actions', 'resources/js/routes', 'resources/js/wayfinder', 'docs/reference/tasks/tests', 'docs/reference/tasks/README.md'],
    );

    // bootstrap/app.php translates the passkey refusal.
    $files[] = 'bootstrap/app.php';

    return array_values(array_filter(
        $files,
        fn (string $path): bool => preg_match('/\.(php|tsx?)$/', $path) === 1,
    ));
}

/**
 * The files of Laravel and Fortify that send a sentence to the user (the
 * verify-email and reset-password mails, Fortify's messages): lang/zh_CN.json
 * translates them, so they count as used.
 *
 * @return list<string>
 */
function translationsVendorFiles(): array
{
    return array_values(array_filter(
        ProjectFiles::in(['vendor/laravel/framework/src/Illuminate/Auth/Notifications', 'vendor/laravel/framework/src/Illuminate/Notifications/resources/views', 'vendor/laravel/fortify/src'], []),
        fn (string $path): bool => preg_match('/\.(php)$/', $path) === 1,
    ));
}

/**
 * The one framework sentence built from two literals, which the scan cannot read.
 */
const TRANSLATIONS_FRAMEWORK_SENTENCES = [
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:",
];

/**
 * @return array<string, string>
 */
function translationsJson(): array
{
    $decoded = json_decode(ProjectFiles::read('lang/zh_CN.json'), true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * A group key such as "validation.required" or "auth.failed".
 */
function translationsIsGroupKey(string $key): bool
{
    return preg_match('/^(auth|passwords|validation|pagination)\.[A-Za-z0-9_.*-]+$/', $key) === 1;
}

/**
 * @param  array<string, mixed>  $group
 * @return list<string>
 */
function translationsFlatten(array $group, string $prefix = ''): array
{
    $keys = [];

    foreach ($group as $key => $value) {
        if (is_array($value)) {
            array_push($keys, ...translationsFlatten($value, "{$prefix}{$key}."));
        } else {
            $keys[] = $prefix.$key;
        }
    }

    return $keys;
}

/**
 * @return list<string>
 */
function translationsPlaceholders(string $text): array
{
    preg_match_all('/:[A-Za-z_]+/', $text, $matches);
    $names = array_map('strtolower', $matches[0]);
    sort($names);

    return array_values(array_unique($names));
}

test('every sentence the code translates has its Chinese in lang/zh_CN.json: add "English sentence": "中文" in the same commit', function () {
    $json = translationsJson();
    $missing = [];

    foreach (translationsUsed(translationsAppFiles()) as $sentence => $file) {
        if (translationsIsGroupKey($sentence)) {
            continue;
        }

        if (! array_key_exists($sentence, $json)) {
            $missing[] = "{$file}: {$sentence}";
        }
    }

    expect($missing)->toBe([]);
});

test('every entry of lang/zh_CN.json is used by some code: delete it or use it', function () {
    $json = translationsJson();
    $used = translationsUsed(array_merge(translationsAppFiles(), translationsVendorFiles()));
    $unused = [];

    foreach (array_keys($json) as $sentence) {
        if (! array_key_exists($sentence, $used) && ! in_array($sentence, TRANSLATIONS_FRAMEWORK_SENTENCES, true)) {
            $unused[] = $sentence;
        }
    }

    expect($unused)->toBe([]);
});

test('a Chinese sentence keeps the :placeholders of its English key and is not empty', function () {
    $problems = [];

    foreach (translationsJson() as $sentence => $chinese) {
        if (trim((string) $chinese) === '') {
            $problems[] = "{$sentence}: empty";
        } elseif (translationsPlaceholders($sentence) !== translationsPlaceholders((string) $chinese)) {
            $problems[] = "{$sentence}: the placeholders differ";
        }
    }

    expect($problems)->toBe([]);
});

test('the Chinese groups (validation, auth, passwords, pagination) hold every key of the English ones', function () {
    $missing = [];

    foreach (['validation', 'auth', 'passwords', 'pagination'] as $group) {
        $english = include ProjectFiles::root()."/lang/en/{$group}.php";
        $chinese = include ProjectFiles::root()."/lang/zh_CN/{$group}.php";
        $chineseKeys = translationsFlatten($chinese);

        foreach (translationsFlatten($english) as $key) {
            if (! str_starts_with($key, 'custom.') && ! in_array($key, $chineseKeys, true)) {
                $missing[] = "lang/zh_CN/{$group}.php: {$key}";
            }
        }
    }

    expect($missing)->toBe([]);
});

test('every group key the code asks for exists in the Chinese group files', function () {
    $missing = [];

    foreach (translationsUsed(translationsAppFiles()) as $key => $file) {
        if (! translationsIsGroupKey($key)) {
            continue;
        }

        [$group, $name] = explode('.', $key, 2);
        $chinese = include ProjectFiles::root()."/lang/zh_CN/{$group}.php";

        if (! in_array($name, translationsFlatten($chinese), true)) {
            $missing[] = "{$file}: {$key}";
        }
    }

    expect($missing)->toBe([]);
});
