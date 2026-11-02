<?php

/*
| Checks on the code that a linter does not do: they read files across folders.
| docs/golden-principles.md lists them as [Structure].
*/

use Tests\Structure\ProjectFiles;

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
    return array_values(array_filter(
        ProjectFiles::in($folders, $skip),
        fn (string $path): bool => preg_match('/\.(php|ts|tsx|js)$/', $path) === 1,
    ));
}

/**
 * The module paths a TypeScript file imports: import … from, export … from,
 * import '…', import('…'), require('…') and import.meta.glob('…').
 *
 * @return list<string>
 */
function structureImportedPaths(string $code): array
{
    preg_match_all('/\bfrom\s*[\'"]([^\'"]+)[\'"]|\bimport\s*\(?\s*[\'"]([^\'"]+)[\'"]|\brequire\s*\(\s*[\'"]([^\'"]+)[\'"]/', $code, $matches);
    $paths = array_merge($matches[1], $matches[2], $matches[3]);

    preg_match_all('/\bimport\.meta\.glob(?:Eager)?\s*(?:<[^>]*>)?\s*\(\s*(\[[^\]]*\]|[\'"][^\'"]+[\'"])/', $code, $globs);

    foreach ($globs[1] as $glob) {
        preg_match_all('/[\'"]!?([^\'"]+)[\'"]/', $glob, $patterns);
        $paths = array_merge($paths, $patterns[1]);
    }

    return array_values(array_filter($paths, fn (string $path): bool => $path !== ''));
}

/**
 * Where an imported path points, relative to the project root, or null for a
 * package. "@/" is resources/js/, as in tsconfig.json.
 */
function structureImportTarget(string $importer, string $path): ?string
{
    if (str_starts_with($path, '/')) {
        return $path;
    }

    if (str_starts_with($path, '@/')) {
        $path = 'resources/js/'.substr($path, 2);
    } elseif (str_starts_with($path, '.')) {
        $path = dirname($importer).'/'.$path;
    } else {
        return null;
    }

    // Resolve "." and "..": a path that climbs above the project root keeps its "..".
    $parts = [];

    foreach (explode('/', $path) as $part) {
        if ($part === '..' && $parts !== [] && end($parts) !== '..') {
            array_pop($parts);
        } elseif ($part !== '.' && $part !== '') {
            $parts[] = $part;
        }
    }

    return implode('/', $parts);
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

test('test data comes from factories: tests never write rows by hand (Model::create(), firstOrCreate(), updateOrCreate(), insert(), upsert(), updateOrInsert(), an INSERT statement)', function () use ($root) {
    $found = [];

    foreach (structureCodeFiles($root, ['tests'], ['tests/Structure/']) as $path) {
        $code = file_get_contents("{$root}/{$path}") ?: '';

        // The models this file imports from App\Models, by the name it uses for them.
        $models = ['\\\\?App\\\\Models\\\\\w+'];
        preg_match_all('/^use\s+App\\\\Models\\\\(?:(\w+)(?:\s+as\s+(\w+))?|\{([^}]*)\})\s*;/m', $code, $imports, PREG_SET_ORDER);

        foreach ($imports as $import) {
            // The last word of "User", "User as Author" or a name in "use App\Models\{User, Task};".
            foreach (isset($import[3]) ? explode(',', $import[3]) : [$import[0]] as $name) {
                if (preg_match('/(\w+)\s*;?\s*$/', $name, $alias) === 1) {
                    $models[] = $alias[1];
                }
            }
        }

        $writes = [
            // Query builder inserts: DB::insert(), DB::table(…)->insert(), Model::insert(), upsert(), updateOrInsert().
            '/(->|::)\s*(insert\w*|upsert|updateOrInsert)\s*\(/',
            // A model created by hand instead of by its factory: User::create(), User::query()->firstOrCreate().
            '/(?<![\w\\\\])('.implode('|', $models).')::(query\(\)\s*->\s*)?(create|createQuietly|createOrFirst|forceCreate|forceCreateQuietly|firstOrCreate|updateOrCreate)\s*\(/',
        ];

        foreach ($writes as $pattern) {
            preg_match_all($pattern, $code, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as [$match, $offset]) {
                $found[] = "{$path}:".(substr_count($code, "\n", 0, $offset) + 1).": {$match}";
            }
        }

        // An INSERT statement in an SQL string.
        foreach (token_get_all($code) as $token) {
            if (is_array($token) && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true) && preg_match('/\binsert\s+(ignore\s+)?into\b/i', $token[1]) === 1) {
                $found[] = "{$path}:{$token[2]}: an INSERT statement";
            }
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

test('components/ui is shadcn\'s code: it never imports the app\'s other components, pages, actions, routes or wayfinder, and never calls fetch or axios', function () use ($root) {
    $found = [];

    foreach (structureCodeFiles($root, ['resources/js/components/ui']) as $path) {
        $code = file_get_contents("{$root}/{$path}") ?: '';

        foreach (structureImportedPaths($code) as $imported) {
            $target = structureImportTarget($path, $imported) ?? '';

            if ((str_starts_with($target, 'resources/js/components/') && ! str_starts_with($target, 'resources/js/components/ui/'))
                || preg_match('#^resources/js/(pages|actions|routes|wayfinder)(/|$)#', $target) === 1) {
                $found[] = "{$path}: imports {$imported}";
            }
        }

        if (preg_match_all('/(?<![\w$])fetch\s*\(|\baxios\b/', $code, $calls) > 0) {
            $found[] = "{$path}: calls ".implode(', ', array_unique($calls[0]));
        }
    }

    expect($found)->toBe([]);
});

test('TypeScript imports only from inside resources/js: no relative path that leaves it and no path into docs/', function () use ($root) {
    $found = [];

    $files = structureCodeFiles($root, ['resources/js', 'tests/js'], ['resources/js/actions', 'resources/js/routes', 'resources/js/wayfinder']);

    foreach ($files as $path) {
        // Frontend tests in tests/js may also import their neighbours.
        $inside = str_starts_with($path, 'tests/js/') ? '#^(resources|tests)/js/#' : '#^resources/js/#';

        foreach (structureImportedPaths(file_get_contents("{$root}/{$path}") ?: '') as $imported) {
            $target = structureImportTarget($path, $imported);

            if (preg_match('#(^|/)docs(/|$)#', $imported) === 1 || ($target !== null && preg_match($inside, $target) !== 1)) {
                $found[] = "{$path}: imports {$imported}";
            }
        }
    }

    expect($found)->toBe([]);
});

test('PHP requires and includes only .php files: code in another kind of file escapes PHPStan', function () use ($root) {
    $found = [];

    $files = array_filter(
        ProjectFiles::in(['app', 'bootstrap', 'config', 'database', 'routes', 'tests'], ['bootstrap/cache', 'bootstrap/ssr']),
        fn (string $path): bool => str_ends_with($path, '.php'),
    );

    foreach ($files as $path) {
        $tokens = token_get_all(file_get_contents("{$root}/{$path}") ?: '');

        foreach ($tokens as $index => $token) {
            if (! is_array($token) || ! in_array($token[0], [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE], true)) {
                continue;
            }

            // The path is the expression up to the end of the statement; its last string must end in .php.
            $expression = $token[1];
            $file = null;
            $depth = 0;

            foreach (array_slice($tokens, $index + 1) as $part) {
                $text = is_array($part) ? $part[1] : $part;
                $depth += match ($text) {
                    '(', '[' => 1,
                    ')', ']' => -1,
                    default => 0,
                };

                if ($depth < 0 || ($depth === 0 && in_array($text, [';', ','], true)) || (is_array($part) && $part[0] === T_CLOSE_TAG)) {
                    break;
                }

                if (is_array($part) && in_array($part[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
                    $file = trim($part[1], '\'"');
                }

                $expression .= $text;
            }

            if ($file === null || ! str_ends_with($file, '.php')) {
                $found[] = "{$path}:{$token[2]}: ".preg_replace('/\s+/', ' ', $expression);
            }
        }
    }

    expect($found)->toBe([]);
});

test('Blade views hold no PHP: no @php and no <?php in resources/views, the controller or Resource prepares the data', function () use ($root) {
    $found = [];

    foreach (ProjectFiles::in(['resources/views']) as $path) {
        foreach (file("{$root}/{$path}") ?: [] as $number => $line) {
            if (preg_match('/(?<![\w@])@php\b|<\?(php|=)/i', $line, $match) === 1) {
                $found[] = "{$path}:".($number + 1).": {$match[0]}";
            }
        }
    }

    expect($found)->toBe([]);
});

test('no UI library comes in through CSS: resources/css imports and plugs in only tailwindcss and tw-animate-css', function () use ($root) {
    $allowed = ['tailwindcss', 'tw-animate-css'];
    $found = [];

    foreach (ProjectFiles::in(['resources/css']) as $path) {
        preg_match_all('/@(import|plugin)\s+(?:url\(\s*)?[\'"]?([^\'"\s;)]+)/', file_get_contents("{$root}/{$path}") ?: '', $statements, PREG_SET_ORDER);

        foreach ($statements as [$statement, $kind, $imported]) {
            // The package a path belongs to: tailwindcss/theme.css is tailwindcss.
            preg_match('#^(@[^/]+/)?[^/]+#', $imported, $package);

            if (! in_array($package[0] ?? '', $allowed, true)) {
                $found[] = "{$path}: @{$kind} {$imported}";
            }
        }
    }

    expect($found)->toBe([]);
});

test('no CSS UI kit in package.json (daisyui, bootstrap, bulma, flowbite, preline and the like): the interface is built from components/ui (shadcn)', function () use ($root) {
    $kits = [
        'daisyui',
        'bootstrap',
        'bulma',
        'flowbite',
        'preline',
        'tw-elements',
        '@material-tailwind/react',
        '@material-tailwind/html',
        '@tailwindcss/ui',
        'franken-ui',
        'flyonui',
        'rippleui',
        'foundation-sites',
        'materialize-css',
        'semantic-ui-css',
        'fomantic-ui-css',
        'uikit',
        'purecss',
        '@picocss/pico',
        'tachyons',
    ];

    $package = json_decode(file_get_contents("{$root}/package.json") ?: '', true);
    $found = [];

    foreach (['dependencies', 'devDependencies', 'optionalDependencies', 'peerDependencies'] as $section) {
        foreach (array_keys(is_array($package[$section] ?? null) ? $package[$section] : []) as $name) {
            if (in_array($name, $kits, true)) {
                $found[] = "package.json {$section}: {$name}";
            }
        }
    }

    expect($found)->toBe([]);
});
