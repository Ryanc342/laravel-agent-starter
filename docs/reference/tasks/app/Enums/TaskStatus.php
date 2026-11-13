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
     * Every status as a value and label pair: the options of the list's status filter.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }

    /**
     * Business rule: the statuses a query value names. A value that is not a list, and an item that
     * is not a status, are dropped, as an unknown sort is ignored.
     *
     * @return list<self>
     */
    public static function fromQuery(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $statuses = [];

        foreach ($values as $value) {
            $status = is_string($value) ? self::tryFrom($value) : null;

            if ($status !== null) {
                $statuses[] = $status;
            }
        }

        return $statuses;
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
