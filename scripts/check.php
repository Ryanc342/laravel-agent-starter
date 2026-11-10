<?php

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/check-process.php';

chdir(dirname(__DIR__));

exit(runValidation(fn (): array => [
    validationStaticCommand(),
    ['npm', 'test'],
    [PHP_BINARY, 'artisan', 'test', '--compact'],
]));
