<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Open = 'open';
    case Done = 'done';

    /**
     * Human-readable label shown in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => __('Open'),
            self::Done => __('Done'),
        };
    }

    /**
     * Whether a task in this status still needs work.
     * The only place that decides which statuses are open.
     */
    public function isOpen(): bool
    {
        return match ($this) {
            self::Open => true,
            self::Done => false,
        };
    }

    /**
     * The stored values of the open statuses, for queries.
     * Derived from isOpen(), so queries never list the cases again.
     *
     * @return list<string>
     */
    public static function openValues(): array
    {
        return array_values(array_map(
            fn (self $status): string => $status->value,
            array_filter(self::cases(), fn (self $status): bool => $status->isOpen()),
        ));
    }
}
