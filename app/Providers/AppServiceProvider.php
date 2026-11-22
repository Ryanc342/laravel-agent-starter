<?php

namespace App\Providers;

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\WebhookController;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The web types are generated in development only (the package is a dev dependency).
        if (class_exists(TypeScriptTransformerApplicationServiceProvider::class)) {
            $this->app->register(TypeScriptTransformerServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerParts();
    }

    /**
     * The route parts (prototype). Route::operation() is the only way to
     * expose an operation; Route::listing() the only way to expose a list;
     * Route::record() the only way to show a page about one record;
     * Route::download() the only way to send a stored file; Route::webhook()
     * the only way another system calls in.
     */
    protected function registerParts(): void
    {
        Route::macro('operation', fn (string $uri, string $operation) => Route::post($uri, [
            'uses' => OperationController::class,
            'operation' => $operation,
        ]));

        Route::macro('download', fn (string $uri, string $model, string $ability, string $field) => Route::get($uri, [
            'uses' => DownloadController::class,
            'download' => compact('model', 'ability', 'field'),
        ]));

        Route::macro('webhook', fn (string $uri, string $service, string $operation) => Route::post($uri, [
            'uses' => WebhookController::class,
            'operation' => $operation,
            'webhook_service' => $service,
        ])->withoutMiddleware([ValidateCsrfToken::class]));

        /**
         * $query names what the URL may choose: 'search' => the model scope that searches,
         * 'filters' => [name => [model scope, enum class or 'string' or 'integer']],
         * 'sorts' => the columns the list may be sorted by,
         * 'export' => true when export=csv may download the whole list (its Resource implements ExportsCsv).
         *
         * @param  array<int, string>  $scopes
         * @param  array{search?: string, filters?: array<string, array{0: string, 1: string}>, sorts?: list<string>, export?: bool}  $query
         */
        Route::macro('listing', fn (string $uri, string $model, string $resource, string $page, string $prop, array $scopes = [], array $query = []) => Route::get($uri, [
            'uses' => ListingController::class,
            'listing' => compact('model', 'resource', 'page', 'prop', 'scopes', 'query'),
        ]));

        /**
         * $with names the relations the page reads; they are loaded with the record.
         *
         * @param  list<string>  $with
         */
        Route::macro('record', fn (string $uri, string $model, string $resource, string $page, string $prop, array $with = []) => Route::get($uri, [
            'uses' => RecordController::class,
            'record' => compact('model', 'resource', 'page', 'prop', 'with'),
        ]));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Outside production a model refuses lazy loading, an attribute it was
        // not given and a value that is not fillable, so these show up as errors.
        Model::shouldBeStrict(! app()->isProduction());

        // composer dev (php artisan dev) runs the schedule next to the server and
        // the queue, so scheduled tasks run while developing too.
        if (app()->runningInConsole()) {
            DevCommands::artisan('schedule:work', 'scheduler');
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
