<?php

namespace App\Enums;

/**
 * The colour theme the user picked on the appearance settings page. The
 * browser stores it in the `appearance` cookie (resources/js/hooks/use-appearance.tsx).
 */
enum Appearance: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';

    /**
     * Business rule: a missing or unknown cookie value means "follow the
     * system setting".
     */
    public static function fromCookie(mixed $value): self
    {
        if (! is_string($value)) {
            return self::System;
        }

        return self::tryFrom($value) ?? self::System;
    }
}
