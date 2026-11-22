<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingRequest;
use App\Http\Resources\ExportsCsv;
use App\Http\Resources\ResourceData;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The query part (prototype): the entry point of every list page. A route
 * registered with Route::listing() names the model, the Resource, the page
 * and the order; this controller always does the same steps:
 *
 *   authorize the list -> start from the records the user may see -> search -> filters
 *   -> the chosen sort -> the list's own scopes and order -> paginate -> Resource
 *
 * A list can therefore never forget the pagination or the "only what this
 * user may see" condition, which lives once, in the model's visibleTo scope.
 * A model without that scope cannot be listed: the first request stops with
 * an error, so its test fails.
 *
 * The export option: a list whose route allows export, asked with export=csv,
 * takes the same steps up to the order, then sends every row as a CSV file
 * instead of one page. Which records are sent, and in what order, is read
 * first, in one query; their contents are then read in batches while the file
 * is sent, so a long list does not fill the memory, and a record added,
 * removed or moved meanwhile cannot shift a batch.
 */
final class ListingController extends Controller
{
    /** The records whose contents are read at a time while a CSV file is sent. */
    private const CSV_CHUNK = 500;

    public function __invoke(ListingRequest $request): Response|StreamedResponse
    {
        $listing = $request->route()?->getAction('listing');

        if (! is_array($listing)) {
            throw new LogicException('A route of ListingController names its list with Route::listing().');
        }

        /** @var class-string<Model> $model */
        $model = $listing['model'];
        /** @var class-string<ResourceData> $resource */
        $resource = $listing['resource'];

        if (! (new $model)->hasNamedScope('visibleTo')) {
            throw new LogicException("{$model} has no visibleTo scope, so it cannot be listed.");
        }

        // Checked on every request of a list that allows export, so a wrong column fails its page test too.
        $columns = $request->allowed()['export'] ? self::checkedCsvColumns($resource) : [];

        Gate::authorize('viewAny', $model);

        $search = $request->search();
        $filters = $request->filters();
        $sort = $request->sort();
        $allowed = $request->allowed();
        $searchScope = $allowed['search'];

        // Every scope the route names, filled in this request or not, so a wrong name fails at once.
        foreach ([$searchScope, ...array_column($allowed['filters'], 0)] as $scope) {
            if ($scope !== null && ! (new $model)->hasNamedScope($scope)) {
                throw new LogicException("{$model} has no {$scope} scope, which its Route::listing() names.");
            }
        }

        $viewer = $request->user();
        $query = $model::query()->scopes(['visibleTo' => [$viewer]]);

        if ($searchScope !== null && $search !== null) {
            $query->scopes([$searchScope => [$search]]);
        }

        foreach ($filters as ['scope' => $scope, 'value' => $value]) {
            $query->scopes([$scope => [$value]]);
        }

        if ($sort !== null) {
            $query->orderBy($sort[0], $sort[1]);
        }

        $query->scopes($listing['scopes']);

        if ($request->exportsCsv()) {
            return $this->csv($query, $resource, $columns, $viewer, $listing['prop']);
        }

        $page = $query
            ->paginate($request->perPage())
            ->withQueryString()
            ->through(fn (Model $record): ResourceData => $resource::from($record, $viewer));

        // JsonResource only wraps the page in Laravel's paginated shape: data, links, meta.
        // {prop}Query tells the page the search, filters and sort it shows.
        return Inertia::render($listing['page'], [
            $listing['prop'] => JsonResource::collection($page)->response($request)->getData(true),
            $listing['prop'].'Query' => [
                'search' => $search,
                'filters' => array_map(fn (array $filter): mixed => $filter['value'], $filters),
                'sort' => $sort === null ? null : ($sort[1] === 'desc' ? '-' : '').$sort[0],
            ],
        ]);
    }

    /**
     * The CSV columns of the Resource, as heading => field. Each field must be
     * one of its constructor's fields, typed as text, a number or null.
     *
     * @param  class-string<ResourceData>  $resource
     * @return array<string, string>
     */
    private static function checkedCsvColumns(string $resource): array
    {
        if (! is_subclass_of($resource, ExportsCsv::class)) {
            throw new LogicException("{$resource} does not implement ExportsCsv, so its list cannot be exported.");
        }

        $types = [];

        foreach ((new ReflectionClass($resource))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            $named = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
            $types[$parameter->getName()] = array_map(fn (mixed $one): string => $one instanceof ReflectionNamedType ? $one->getName() : 'other', $named);
        }

        foreach ($resource::csvColumns() as $field) {
            if (! array_key_exists($field, $types)) {
                throw new LogicException("{$resource}::csvColumns() names [{$field}], which is not one of its fields.");
            }

            if (array_diff($types[$field], ['string', 'int', 'float', 'null']) !== []) {
                throw new LogicException("{$resource}::csvColumns() names [{$field}], typed ".implode('|', $types[$field]).': a CSV column holds text, a number or null.');
            }
        }

        return $resource::csvColumns();
    }

    /**
     * Every row of the list as a CSV file: a UTF-8 mark so spreadsheet programs
     * read the text right, the column headings, then one line per record.
     *
     * @param  Builder<Model>  $query
     * @param  class-string<ResourceData>  $resource
     * @param  array<string, string>  $columns
     */
    private function csv(Builder $query, string $resource, array $columns, ?User $viewer, string $name): StreamedResponse
    {
        // The key last, so records that sort the same always come in one order.
        $query->orderBy($query->getModel()->getQualifiedKeyName());

        return new StreamedResponse(function () use ($query, $resource, $columns, $viewer): void {
            $out = fopen('php://output', 'w')
                ?: throw new LogicException('The CSV file cannot be written to the response.');

            fwrite($out, "\u{FEFF}");
            fputcsv($out, array_keys($columns), escape: '');

            // The keys of every row, in the list's order, from one query: the batches below go by
            // these keys, not by position, so no row is written twice or left out when the list
            // changes while the file is sent. A record removed, or no longer in the list, by the
            // time its batch is read is not written.
            $keys = (clone $query)->pluck($query->getModel()->getQualifiedKeyName())->all();

            foreach (array_chunk($keys, self::CSV_CHUNK) as $batch) {
                $records = (clone $query)->whereKey($batch)->get()
                    ->keyBy(fn (Model $record): string => (string) $record->getKey());

                foreach ($batch as $key) {
                    $record = $records->get((string) $key);

                    if ($record !== null) {
                        $fields = $resource::from($record, $viewer)->toArray();
                        fputcsv($out, array_map(fn (string $field): string|int|float|null => self::cell($fields, $field), array_values($columns)), escape: '');
                    }
                }
            }

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$name.'-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    /**
     * One cell: the Resource field (csvColumns() checked its type). Text that a
     * spreadsheet would run as a formula (it starts with =, +, -, @, a tab or
     * a return) gets a ' in front, so it stays text.
     *
     * @param  array<string, mixed>  $fields
     */
    private static function cell(array $fields, string $field): string|int|float|null
    {
        $value = $fields[$field];

        return match (true) {
            is_string($value) => preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value,
            $value === null, is_int($value), is_float($value) => $value,
            default => throw new LogicException("The CSV column [{$field}] holds ".get_debug_type($value).'.'),
        };
    }
}
