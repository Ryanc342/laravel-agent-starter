<?php

namespace App\Enums;

/**
 * The two languages of the app. Which one a request uses is decided here, once
 * (docs/rules/translations.md): the signed-in user's choice, then the `locale`
 * cookie, then the browser's Accept-Language, then English.
 */
enum Locale: string
{
    case En = 'en';
    case ZhCn = 'zh_CN';

    /**
     * Business rule: the first source that holds a known language wins. An
     * unknown or malformed value is skipped, not an error.
     */
    public static function resolve(mixed $user, mixed $cookie, ?string $acceptLanguage): self
    {
        foreach ([$user, $cookie] as $stored) {
            if (is_string($stored) && self::tryFrom($stored) !== null) {
                return self::from($stored);
            }
        }

        return self::fromBrowser($acceptLanguage);
    }

    /**
     * Business rule: a browser whose first choice is any Chinese (zh, zh-CN,
     * zh-TW, ...) gets Simplified Chinese; every other browser gets English.
     * The first choice is the language with the highest q value, the one
     * listed first when they tie.
     */
    public static function fromBrowser(?string $acceptLanguage): self
    {
        $first = null;
        $firstQuality = 0.0;

        foreach (explode(',', (string) $acceptLanguage) as $part) {
            [$tag, $parameters] = array_pad(explode(';', trim($part), 2), 2, '');

            $quality = preg_match('/q=([0-9.]+)/', $parameters, $match) === 1 ? (float) $match[1] : 1.0;

            if (trim($tag) !== '' && trim($tag) !== '*' && $quality > $firstQuality) {
                $first = strtolower(trim($tag));
                $firstQuality = $quality;
            }
        }

        return $first !== null && ($first === 'zh' || str_starts_with($first, 'zh-')) ? self::ZhCn : self::En;
    }

    /**
     * The language written in itself, so a reader who cannot read the current
     * language can still find theirs.
     */
    public function nativeName(): string
    {
        return match ($this) {
            self::En => 'English',
            self::ZhCn => '中文',
        };
    }
}
