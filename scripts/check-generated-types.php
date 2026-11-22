<?php

/*
| The web page's types are generated from the backend
| (app/Providers/TypeScriptTransformerServiceProvider.php). composer check
| regenerates them first and fails when the file in the project was out of
| date; the regenerated file is then the one to keep.
*/

chdir(dirname(__DIR__)) ?: throw new RuntimeException('cannot work in '.dirname(__DIR__));

$file = 'resources/js/types/generated.d.ts';
$before = is_file($file) ? file_get_contents($file) : null;

passthru(escapeshellarg(PHP_BINARY).' artisan typescript:transform --no-interaction', $exit);

if ($exit !== 0) {
    exit($exit);
}

if (! is_file($file)) {
    fwrite(STDERR, "{$file} was not generated.\n");
    exit(1);
}

if (file_get_contents($file) !== $before) {
    fwrite(STDERR, "{$file} was out of date: it is now regenerated from the backend. Keep the new file and run the check again.\n");
    exit(1);
}
