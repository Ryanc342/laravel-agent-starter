<?php

namespace App\Http\Requests\Tasks;

use App\Concerns\ReadsTaskFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The tasks a bulk action acts on: the ticked `ids`, or `all` results of the
 * list with its own search and filters (the same values and the same rules as
 * the list's query string). Which of those tasks the user may touch is the
 * Policy's question, and what an action does to each is the Action's.
 */
final class BulkTaskRequest extends FormRequest
{
    use ReadsTaskFilters;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'all' => ['nullable', 'boolean'],
            'ids' => [Rule::excludeIf(fn (): bool => $this->selectsAllResults()), 'required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
            ...$this->taskFilterRules(),
        ];
    }

    /**
     * Whether "Select all N results" was used: every result of the list, not only the ticked rows.
     */
    public function selectsAllResults(): bool
    {
        return $this->boolean('all');
    }

    /**
     * The ticked ids, or null for all results.
     *
     * @return list<int>|null
     */
    public function ids(): ?array
    {
        if ($this->selectsAllResults()) {
            return null;
        }

        $ids = $this->validated('ids');

        return is_array($ids) ? array_values(array_map(intval(...), $ids)) : [];
    }
}
