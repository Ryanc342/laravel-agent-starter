<?php

namespace App\Enums;

use Illuminate\Support\Facades\Lang;

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
     * unknown or malformed value is skipped, not an error. `$languages` is what
     * the browser asked for, best first (Request::getLanguages()).
     *
     * @param  list<string>  $languages
     */
    public static function resolve(mixed $user, mixed $cookie, array $languages): self
    {
        foreach ([$user, $cookie] as $stored) {
            if (is_string($stored) && self::tryFrom($stored) !== null) {
                return self::from($stored);
            }
        }

        return self::fromBrowser($languages);
    }

    /**
     * Business rule: a browser whose first choice is any Chinese (zh, zh_CN,
     * zh_TW, ...) gets Simplified Chinese; every other browser gets English.
     *
     * @param  list<string>  $languages  best first
     */
    public static function fromBrowser(array $languages): self
    {
        foreach ($languages as $language) {
            if ($language !== '*') {
                return str_starts_with(strtolower($language), 'zh') ? self::ZhCn : self::En;
            }
        }

        return self::En;
    }

    /**
     * The language the application is running in now (SetLocale set it).
     */
    public static function current(): self
    {
        return self::tryFrom(app()->getLocale()) ?? self::En;
    }

    /**
     * The sentences React translates with t(): the language's lang/*.json, which
     * English does not have, because a sentence is its own English text.
     *
     * @return array<string, string>
     */
    public function sentences(): array
    {
        return $this === self::En ? [] : Lang::getLoader()->load($this->value, '*', '*');
    }

    /**
     * The choices both language switches show, from the one list the endpoint
     * accepts: the value to send, the language's own name and its short label.
     *
     * @return list<array{value: string, label: string, short: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $locale): array => [
            'value' => $locale->value,
            'label' => $locale->nativeName(),
            'short' => $locale->shortName(),
        ], self::cases());
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

    /**
     * The short label of the guest switch.
     */
    public function shortName(): string
    {
        return match ($this) {
            self::En => 'EN',
            self::ZhCn => '中文',
        };
    }
}
