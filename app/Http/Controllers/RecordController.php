<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResourceData;
use App\Http\UrlRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

/**
 * The record part: the entry point of every page about one record, such as
 * its detail page or its print page. A route registered with Route::record()
 * names the model, the Resource, the page, its prop and the relations the
 * page reads; this controller always does the same steps:
 *
 *   find the record in the URL (404 if there is none) -> authorize view on it
 *   -> load the relations -> Resource -> page
 *
 * A page about one record can therefore never forget to ask whether this
 * user may see it.
 */
final class RecordController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $route = $request->route()?->getAction('record');
        $model = is_array($route) ? ($route['model'] ?? null) : null;
        $resource = is_array($route) ? ($route['resource'] ?? null) : null;
        $page = is_array($route) ? ($route['page'] ?? null) : null;
        $prop = is_array($route) ? ($route['prop'] ?? null) : null;
        $with = is_array($route) ? ($route['with'] ?? null) : null;

        if (! is_string($model) || ! is_subclass_of($model, Model::class)
            || ! is_string($resource) || ! is_subclass_of($resource, ResourceData::class)
            || ! is_string($page) || ! is_string($prop) || ! is_array($with)) {
            throw new LogicException('A route of RecordController names its model, Resource, page, prop and relations with Route::record().');
        }

        $record = UrlRecords::last($request, $model)
            ?? throw new LogicException('A record route names its record in the URL.');

        Gate::authorize('view', $record);

        $record->load($with);

        return Inertia::render($page, [
            $prop => $resource::from($record, $request->user()),
        ]);
    }
}
