<?php

/*
| Principles 3, 7 and 8 in docs/golden-principles.md that are about files rather
| than code. Each test name says what to do instead, because Pest prints it when
| a rule breaks.
*/

$root = dirname(__DIR__, 2);

/**
 * PHP files under a folder, as paths relative to the project root.
 *
 * @return list<string>
 */
function conventionPhpFiles(string $root, string $folder): array
{
    if (! is_dir("{$root}/{$folder}")) {
        return [];
    }

    $paths = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator("{$root}/{$folder}", FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if ($file->getExtension() === 'php') {
            $paths[] = substr($file->getPathname(), strlen($root) + 1);
        }
    }

    sort($paths);

    return $paths;
}

test('every Action has a feature test: add tests/Feature/{Domain}/{Action}Test.php', function () use ($root) {
    $missing = [];

    foreach (conventionPhpFiles($root, 'app/Actions') as $path) {
        $test = 'tests/Feature/'.substr($path, strlen('app/Actions/'), -strlen('.php')).'Test.php';

        if (! file_exists("{$root}/{$test}")) {
            $missing[] = "{$path} needs {$test}";
        }
    }

    expect($missing)->toBe([]);
});

test('every Resource with can.* buttons has a consistency test: add tests/Feature/{Domain}/{Model}ButtonsTest.php', function () use ($root) {
    $buttonTests = array_map('basename', glob("{$root}/tests/Feature/*/*ButtonsTest.php") ?: []);
    $missing = [];

    foreach (conventionPhpFiles($root, 'app/Http/Resources') as $path) {
        if (! preg_match('/[\'"]can[\'"]\s*=>/', (string) file_get_contents("{$root}/{$path}"))) {
            continue;
        }

        $expected = basename($path, 'Resource.php').'ButtonsTest.php';

        if (! in_array($expected, $buttonTests, true)) {
            $missing[] = "{$path} needs tests/Feature/{Domain}/{$expected}";
        }
    }

    expect($missing)->toBe([]);
});

test('every model has a factory: add database/factories/{Model}Factory.php with the states tests need', function () use ($root) {
    $missing = [];

    foreach (conventionPhpFiles($root, 'app/Models') as $path) {
        $factory = 'database/factories/'.basename($path, '.php').'Factory.php';

        if (! file_exists("{$root}/{$factory}")) {
            $missing[] = "{$path} needs {$factory}";
        }
    }

    expect($missing)->toBe([]);
});

test('model scopes use the #[Scope] attribute: turn scopeFoo() into a protected foo() marked #[Scope]', function () use ($root) {
    $found = [];

    foreach (conventionPhpFiles($root, 'app/Models') as $path) {
        preg_match_all('/function\s+(scope[A-Z]\w*)\s*\(/', (string) file_get_contents("{$root}/{$path}"), $matches);

        foreach ($matches[1] as $method) {
            $found[] = "{$path}: {$method}()";
        }
    }

    expect($found)->toBe([]);
});
