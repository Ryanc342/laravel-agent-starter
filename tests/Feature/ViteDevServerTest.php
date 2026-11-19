<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;

uses(RefreshDatabase::class);

/*
| Tests never use the Vite dev server: phpunit.xml turns server-side rendering
| off, so a page renders the same while `composer dev` runs.
*/

test('a page renders without asking the Vite dev server, also while one runs', function () {
    $hot = storage_path('framework/testing/vite-hot');
    file_put_contents($hot, 'http://127.0.0.1:5173');
    Vite::useHotFile($hot);
    Http::preventStrayRequests();

    try {
        $this->get(route('home'))->assertOk();
    } finally {
        unlink($hot);
    }
});
