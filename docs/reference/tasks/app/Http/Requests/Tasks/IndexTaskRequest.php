<?php

namespace App\Http\Requests\Tasks;

use App\Concerns\ReadsPerPage;
use App\Concerns\ReadsTaskFilters;
use App\Enums\TaskSort;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The query string of the task list: search, sort, filters and rows per page.
 * None of them is ever an error: a value that is not allowed is ignored, as an
 * unknown `per_page` is.
 */
final class IndexTaskRequest extends FormRequest
{
    use ReadsPerPage;
    use ReadsTaskFilters;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            ...$this->perPageRules(),
            ...$this->taskFilterRules(),
            'sort' => ['nullable'],
            'direction' => ['nullable'],
        ];
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
}
