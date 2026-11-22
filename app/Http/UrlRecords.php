<?php

namespace App\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LogicException;

/**
 * The records named in a URL (part of the operation part; the download
 * entry point uses it too). The last one is the record the request is about.
 * With more than one, each must belong to the one before it
 * (/projects/{project}/tasks/{task}: the task is found through the project's
 * tasks relation, 404 otherwise), and the first one is the model its name
 * says (App\Models\Project). A record that does not exist is a 404.
 */
final class UrlRecords
{
    /**
     * @param  class-string<Model>  $lastClass
     */
    public static function last(Request $request, string $lastClass): ?Model
    {
        $parameters = $request->route()?->parameters() ?? [];
        $record = null;
        $last = array_key_last($parameters);

        foreach ($parameters as $name => $value) {
            $class = $name === $last && $record === null ? $lastClass : 'App\\Models\\'.Str::studly($name);

            if ($value instanceof Model) {
                $found = $value;
            } elseif ($record === null) {
                if (! is_subclass_of($class, Model::class)) {
                    throw new LogicException("The URL record {{$name}} names no model: there is no {$class}.");
                }

                $found = (new $class)->resolveRouteBinding($value);
            } else {
                $found = $record->resolveChildRouteBinding($name, $value, null);
            }

            $record = $found ?? abort(404);
        }

        if ($record !== null && ! $record instanceof $lastClass) {
            throw new LogicException('The last record in the URL is a '.$record::class.", but the route acts on {$lastClass}.");
        }

        return $record;
    }
}
