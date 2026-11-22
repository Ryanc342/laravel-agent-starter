<?php

namespace App\Enums;

use App\Enums\Concerns\ReadsChoice;

/**
 * How many rows a list page shows. The user picks one under the list; it lives
 * only in the URL (`?per_page=50`). The choices reach React as the shared prop
 * `perPageOptions` (HandleInertiaRequests).
 */
enum PerPage: int
{
    use ReadsChoice;

    case Ten = 10;
    case Twenty = 20;
    case Fifty = 50;
    case Hundred = 100;

    /**
     * Business rule: a missing value, or one that is not offered, means 20.
     */
    public static function fromQuery(mixed $value): self
    {
        return self::fromValue($value) ?? self::Twenty;
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): int => $case->value, self::cases());
    }
}
