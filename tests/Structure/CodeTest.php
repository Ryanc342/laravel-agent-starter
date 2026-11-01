<?php

/*
| Checks on the code that a linter does not do: they read files across folders.
| docs/golden-principles.md lists them as [Structure].
*/

$root = dirname(__DIR__, 2);

/**
 * Every file under the given folders with one of the extensions, as paths
 * relative to the project root.
 *
 * @param  list<string>  $folders
 * @param  list<string>  $skip  path prefixes to leave out
 * @return list<string>
 */
function structureCodeFiles(string $root, array $folders, array $skip = []): array
{
    $files = [];

    foreach ($folders as $folder) {
        if (! is_dir("{$root}/{$folder}")) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$folder}", FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            $path = substr($file->getPathname(), strlen($root) + 1);

            if (preg_match('/\.(php|ts|tsx|js)$/', $path) === 1 && array_filter($skip, fn (string $prefix): bool => str_starts_with($path, $prefix)) === []) {
                $files[] = $path;
            }
        }
    }

    sort($files);

    return $files;
}

test('no check is silenced: fix the code the check points at, the error says what to do', function () use ($root) {
    $silencers = '/phpstan-ignore|oxlint-disable|eslint-disable|@ts-ignore|@ts-expect-error|@ts-nocheck/';
    // Only in tests: a skipped, unfinished or focused test (->only() runs that test alone).
    // Pest's modifiers chain on the test (`})->skip()` or a line of their own), unlike Collection::skip().
    $skips = '/(^\s*|\}\).*)->\s*(skip\w*|todo)\(|->\s*only\(\)|markTest(Skipped|Incomplete)\(|\b(test|it|describe)\.(skip|only|todo|fails|skipIf|runIf)\b/';
    $found = [];

    $files = structureCodeFiles($root, ['app', 'bootstrap', 'config', 'database', 'routes', 'resources/js', 'tests', 'lint', 'scripts'], [
        'tests/Structure/',
        'tests/TestCase.php',
        'resources/js/actions/',
        'resources/js/routes/',
        'resources/js/wayfinder/',
        'bootstrap/cache/',
        'bootstrap/ssr/',
    ]);

    foreach ($files as $path) {
        foreach (file("{$root}/{$path}") ?: [] as $number => $line) {
            if (preg_match($silencers, $line, $match) === 1 || (str_starts_with($path, 'tests/') && preg_match($skips, $line, $match) === 1)) {
                $found[] = "{$path}:".($number + 1).": {$match[0]}";
            }
        }
    }

    // tests/TestCase.php skips the tests of a Fortify feature that is turned off, and nothing else.
    $testCase = file_get_contents("{$root}/tests/TestCase.php") ?: '';

    if (preg_match($silencers, $testCase) === 1 || preg_match_all($skips, $testCase) !== 1 || substr_count($testCase, 'markTestSkipped(') !== 1) {
        $found[] = 'tests/TestCase.php: only skipUnlessFortifyHas() may skip a test';
    }

    if (preg_match('/^\s*(ignoreErrors|excludePaths|baseline)\s*:|phpstan-baseline/m', file_get_contents("{$root}/phpstan.neon") ?: '') === 1) {
        $found[] = 'phpstan.neon: ignoreErrors, excludePaths or a baseline';
    }

    expect($found)->toBe([]);
});

test('test data comes from factories: tests never insert rows by hand (insert(), upsert(), an INSERT statement)', function () use ($root) {
    $found = [];

    foreach (structureCodeFiles($root, ['tests'], ['tests/Structure/']) as $path) {
        $code = file_get_contents("{$root}/{$path}") ?: '';

        if (preg_match('/(->|::)\s*(insert\w*|upsert)\s*\(|DB::(statement|unprepared)\(\s*[\'"]\s*insert/is', $code) === 1) {
            $found[] = $path;
        }
    }

    expect($found)->toBe([]);
});

test('resources/js/lib/format.ts has its Vitest test in tests/js/lib/', function () use ($root) {
    $missing = file_exists("{$root}/resources/js/lib/format.ts")
        && ! file_exists("{$root}/tests/js/lib/format.test.ts")
        && ! file_exists("{$root}/tests/js/lib/format.test.tsx");

    expect($missing ? ['tests/js/lib/format.test.ts'] : [])->toBe([]);
});
