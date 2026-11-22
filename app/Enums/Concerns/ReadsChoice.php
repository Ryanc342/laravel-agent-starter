<?php

namespace App\Enums\Concerns;

/**
 * The choice-reading part, for an enum of choices that arrive as text (a URL
 * value, a cookie, a header, a stored setting): the case the value names, or
 * null for anything else, so the enum decides its own fallback in one place
 * (`PerPage::fromQuery(...)`). An unknown or malformed value is never an error.
 */
trait ReadsChoice
{
    public static function fromValue(mixed $value): ?self
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        return array_find(self::cases(), fn (self $case): bool => (string) $case->value === (string) $value);
    }
}
