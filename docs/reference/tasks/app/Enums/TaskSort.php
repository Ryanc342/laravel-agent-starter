<?php

namespace App\Enums;

/**
 * The columns of the task list that can be sorted. The value is what the URL
 * carries (`?sort=due_on`) and also the column it sorts by. A value that is not
 * here is ignored, as an unknown `per_page` is.
 */
enum TaskSort: string
{
    case Title = 'title';
    case Status = 'status';
    case DueOn = 'due_on';

    /**
     * Business rule: a missing value, or one that is not offered, means no sort.
     */
    public static function fromQuery(mixed $value): ?self
    {
        return is_string($value) ? self::tryFrom($value) : null;
    }
}
