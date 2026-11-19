<?php

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/check-process.php';

chdir(dirname(__DIR__));

exit(runValidation(fn (): array => [
    validationStaticCommand(),
    ['npm', 'test'],
    [PHP_BINARY, 'artisan', 'test', '--compact'],
    // The template's own tests: a project made from the template has no template/.
    ...(is_file('template/phpunit.xml') ? [[PHP_BINARY, 'vendor/bin/pest', '-c', 'template/phpunit.xml', '--test-directory=template/tests', '--compact']] : []),
]));
