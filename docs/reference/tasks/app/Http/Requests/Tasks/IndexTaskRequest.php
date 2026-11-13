<?php

namespace App\Http\Requests\Tasks;

use App\Concerns\ReadsPerPage;
use App\Enums\TaskSort;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The query string of the task list: search, sort, filters and rows per page.
 * None of them is ever an error: a value that is not allowed is ignored, as an
 * unknown `per_page` is.
 */
final class IndexTaskRequest extends FormRequest
{
    use ReadsPerPage;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            ...$this->perPageRules(),
            'search' => ['nullable'],
            'sort' => ['nullable'],
            'direction' => ['nullable'],
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
     * The column to sort by, or null for the default order.
     */
    public function sort(): ?TaskSort
    {
        return TaskSort::fromQuery($this->validated('sort'));
    }

    /**
     * @return 'asc'|'desc'
     */
    public function direction(): string
    {
        return $this->validated('direction') === 'desc' ? 'desc' : 'asc';
    }

    /**
     * The ticked statuses; values that are not statuses are dropped.
     *
     * @return list<TaskStatus>
     */
    public function statuses(): array
    {
        $values = $this->validated('status');

        if (! is_array($values)) {
            return [];
        }

        $statuses = [];

        foreach ($values as $value) {
            $status = is_string($value) ? TaskStatus::tryFrom($value) : null;

            if ($status !== null) {
                $statuses[] = $status;
            }
        }

        return $statuses;
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
