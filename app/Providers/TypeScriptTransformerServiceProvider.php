<?php

namespace App\Providers;

use Spatie\LaravelTypeScriptTransformer\Transformers\LaravelAttributedClassTransformer;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\GlobalNamespaceWriter;

/**
 * Generates the web page's types from the backend: every class in app/ marked
 * #[TypeScript] (the Resource part) becomes a type in
 * resources/js/types/generated.d.ts. composer check regenerates the file and
 * fails when the one in the project was out of date. Only in development:
 * AppServiceProvider registers this when the package is installed.
 */
class TypeScriptTransformerServiceProvider extends TypeScriptTransformerApplicationServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->transformer(LaravelAttributedClassTransformer::class)
            ->transformDirectories(app_path())
            ->writer(new GlobalNamespaceWriter('generated.d.ts'))
            ->outputDirectory(resource_path('js/types'))
            // The manifest lets the generator skip unchanged files and delete
            // ones it no longer makes. It writes one file, so there is nothing
            // to skip or delete, and resources/js holds only TypeScript.
            ->withoutManifest();
    }
}
