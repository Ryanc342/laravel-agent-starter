<?php

namespace App\Concerns;

use App\Enums\TaskStatus;

/**
 * The search and filters of the task list, read from the query string or, for
 * "all results" of a bulk action, from the request body. None of them is ever
 * an error: a value that is not allowed is ignored. The task list, its export
 * and the bulk actions all read them here, so they agree on what "the tasks the
 * list shows" means.
 */
trait ReadsTaskFilters
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function taskFilterRules(): array
    {
        return [
            'search' => ['nullable'],
            'status' => ['nullable'],
            'due_from' => ['nullable'],
            'due_to' => ['nullable'],
        ];
    }

    /**
     * The text to look for in titles, or null when there is none.
     */
    public function search(): ?string
    {
        $search = $this->validated('search');

        if (! is_string($search) || trim($search) === '') {
            return null;
        }

        return mb_substr(trim($search), 0, 100);
    }

    /**
     * The ticked statuses; values that are not statuses are dropped.
     *
     * @return list<TaskStatus>
     */
    public function statuses(): array
    {
        return TaskStatus::fromQuery($this->validated('status'));
    }

    /**
     * The first day of the due date range (Y-m-d), or null.
     */
    public function dueFrom(): ?string
    {
        return $this->day('due_from');
    }

    /**
     * The last day of the due date range (Y-m-d), or null.
     */
    public function dueTo(): ?string
    {
        return $this->day('due_to');
    }

    /**
     * Whether the search or any filter actually narrows the list: the values that were ignored do not count.
     */
    public function isNarrowed(): bool
    {
        return $this->search() !== null
            || $this->statuses() !== []
            || $this->dueFrom() !== null
            || $this->dueTo() !== null;
    }

    private function day(string $key): ?string
    {
        $value = $this->validated($key);

        if (! is_string($value) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) !== 1) {
            return null;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $value : null;
    }
}
