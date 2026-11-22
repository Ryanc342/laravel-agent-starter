<?php

namespace App\Http\Requests;

use App\Concerns\ReadsPerPage;
use BackedEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use LogicException;

/**
 * The input of every list page (part of the query part): the rows per page,
 * and the search, filters, sort and export that its Route::listing() allows.
 * A value of a filter, a sort or an export the route does not allow is an
 * input error; a query value the route does not name is ignored.
 */
final class ListingRequest extends FormRequest
{
    use ReadsPerPage;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $query = $this->allowed();
        $rules = $this->perPageRules();

        if ($query['search'] !== null) {
            $rules['search'] = ['nullable', 'string', 'max:100'];
        }

        foreach ($query['filters'] as $name => [, $type]) {
            $rules[$name] = match (true) {
                enum_exists($type) => ['nullable', Rule::enum($type)],
                $type === 'integer' => ['nullable', 'integer'],
                $type === 'string' => ['nullable', 'string', 'max:100'],
                default => throw new LogicException("The filter [{$name}] has the type [{$type}]: use an enum class, 'string' or 'integer'."),
            };
        }

        if ($query['sorts'] !== []) {
            $rules['sort'] = ['nullable', Rule::in([...$query['sorts'], ...array_map(fn (string $column): string => "-{$column}", $query['sorts'])])];
        }

        if ($query['export']) {
            $rules['export'] = ['nullable', Rule::in(['csv'])];
        }

        return $rules;
    }

    /**
     * Whether the request asks for the whole list as a CSV file (export=csv).
     */
    public function exportsCsv(): bool
    {
        return $this->allowed()['export'] && $this->validated('export') === 'csv';
    }

    /**
     * The search text, or null when there is none.
     */
    public function search(): ?string
    {
        $search = $this->validated('search');

        return is_string($search) && trim($search) !== '' ? trim($search) : null;
    }

    /**
     * The filled filters, as scope => value (an enum case for an enum filter).
     *
     * @return array<string, array{scope: string, value: mixed}>
     */
    public function filters(): array
    {
        $filled = [];

        foreach ($this->allowed()['filters'] as $name => [$scope, $type]) {
            $value = $this->validated($name);

            if ($value === null || $value === '') {
                continue;
            }

            $filled[$name] = ['scope' => $scope, 'value' => match (true) {
                enum_exists($type) && is_subclass_of($type, BackedEnum::class) && (is_string($value) || is_int($value)) => $type::from($value),
                $type === 'integer' => (int) $value,
                default => $value,
            }];
        }

        return $filled;
    }

    /**
     * The sort as [column, direction], or null for the list's own order.
     *
     * @return array{0: string, 1: 'asc'|'desc'}|null
     */
    public function sort(): ?array
    {
        $sort = $this->validated('sort');

        if (! is_string($sort) || $sort === '') {
            return null;
        }

        return str_starts_with($sort, '-') ? [substr($sort, 1), 'desc'] : [$sort, 'asc'];
    }

    /**
     * What Route::listing() allows for this list.
     *
     * @return array{search: string|null, filters: array<string, array{0: string, 1: string}>, sorts: list<string>, export: bool}
     */
    public function allowed(): array
    {
        $listing = $this->route()?->getAction('listing');

        /** @var array{search?: string|null, filters?: array<string, array{0: string, 1: string}>, sorts?: list<string>, export?: bool} $query */
        $query = is_array($listing) ? ($listing['query'] ?? []) : [];

        return ['search' => $query['search'] ?? null, 'filters' => $query['filters'] ?? [], 'sorts' => $query['sorts'] ?? [], 'export' => $query['export'] ?? false];
    }
}
