<?php

namespace App\Http\Controllers;

use App\Http\UrlRecords;
use App\Support\StoredFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The download entry point (part of the file part). A route registered with
 * Route::download() names the model, the Policy ability that may download and
 * the field that holds the file. It finds the record in the URL, checks the
 * ability on it, and sends the file under its original name. A record
 * without a file is a 404.
 */
final class DownloadController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $download = $request->route()?->getAction('download');
        $model = is_array($download) ? ($download['model'] ?? null) : null;
        $ability = is_array($download) ? ($download['ability'] ?? null) : null;
        $field = is_array($download) ? ($download['field'] ?? null) : null;

        if (! is_string($model) || ! is_subclass_of($model, Model::class) || ! is_string($ability) || ! is_string($field)) {
            throw new LogicException('A route of DownloadController names its model, ability and field with Route::download().');
        }

        $record = UrlRecords::last($request, $model)
            ?? throw new LogicException('A download route names its record in the URL.');

        Gate::authorize($ability, $record);

        $file = $record->getAttribute($field);

        if (! $file instanceof StoredFile) {
            abort(404);
        }

        return $file->download();
    }
}
