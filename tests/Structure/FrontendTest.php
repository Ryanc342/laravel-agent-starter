<?php

/*
| Principle 4 in docs/golden-principles.md: the frontend never does the
| backend's job. These checks read the React code as text and stop the usual
| ways it would decide a business rule. Each test name says what to do instead.
|
| They match by name and shape, so a match can be display logic after all. Then
| ask the user to adjust this file; never rename things to slip past a check.
*/

$root = dirname(__DIR__, 2);

/**
 * Code lines of the hand-written React files, keyed by "path:line". Comment
 * lines and generated or shadcn folders are left out.
 *
 * @return array<string, string>
 */
function frontendCodeLines(string $root): array
{
    $skip = [
        'resources/js/components/ui/',
        'resources/js/actions/',
        'resources/js/routes/',
        'resources/js/wayfinder/',
    ];
    $lines = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator("{$root}/resources/js", FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        $path = substr($file->getPathname(), strlen($root) + 1);

        if (! in_array($file->getExtension(), ['ts', 'tsx'], true) || str_ends_with($path, '.d.ts')) {
            continue;
        }

        foreach ($skip as $prefix) {
            if (str_starts_with($path, $prefix)) {
                continue 2;
            }
        }

        foreach ((array) file($file->getPathname()) as $index => $line) {
            $code = trim((string) $line);

            if ($code === '' || preg_match('#^(//|/\*|\*|\{/\*)#', $code) === 1) {
                continue;
            }

            $lines[$path.':'.($index + 1)] = $code;
        }
    }

    ksort($lines);

    return $lines;
}

/**
 * The "path:line: code" entries that match the pattern, outside the allowed files.
 *
 * @param  list<string>  $allowedFiles
 * @return list<string>
 */
function frontendMatches(string $root, string $pattern, array $allowedFiles = []): array
{
    $matches = [];

    foreach (frontendCodeLines($root) as $where => $code) {
        $path = explode(':', $where)[0];

        if (! in_array($path, $allowedFiles, true) && preg_match($pattern, $code) === 1) {
            $matches[] = "{$where}: {$code}";
        }
    }

    return $matches;
}

test('React does not check permissions: show each button from the can.* flag the Resource sends', function () use ($root) {
    expect(frontendMatches(
        $root,
        '/auth\.user\??\.\w+\s*[!=]==?|[!=]==?\s*(?:[\w.?]*\.)?auth\.user\b/',
    ))->toBe([]);
});

test('React does not compare statuses, roles or types: use the is_* flags and labels the Resource sends', function () use ($root) {
    // Browser objects (an event, a file, an HTTP response) and flash or toast messages have a
    // type or status that is not a business value.
    $holder = '(?<!\bevent)(?<!\bevt)(?<!\be)(?<!\bfile)(?<!\btarget)(?<!\binput)(?<!\belement)(?<!\bel)(?<!\bresponse)(?<!\bres)(?<!\berror)(?<!\berr)(?<!\bflash)(?<!\btoast)(?<!\bprops)';

    expect(frontendMatches(
        $root,
        '/'.$holder.'\.(status|state|role|type|kind|stage|level)\s*[!=]==?\s*(?!\d)|[!=]==?\s*[\w.?]+'.$holder.'\.(status|state|role|type|kind|stage|level)\b/',
    ))->toBe([]);
});

test('React does not compare or subtract dates: the Resource sends is_* flags and formatted dates; display-only formatting goes in resources/js/lib/format.ts', function () use ($root) {
    expect(frontendMatches(
        $root,
        '/new Date\([^)]*\)(?:\.getTime\(\))?\s*(?:[<>]=?|-(?!>))|(?<![=\-])(?:[<>]=?|-)\s*(?:new Date\(|Date\.now\(\))|Date\.now\(\)\s*(?:[<>]=?|-)|\.getTime\(\)\s*(?:[<>]=?|-)|(?<![=\-])(?:[<>]=?|-)\s*[\w.?]+\.getTime\(\)/',
        ['resources/js/lib/format.ts'],
    ))->toBe([]);
});

test('React does not calculate money or sum lists: the Resource sends prices, totals and discounts; display-only formatting goes in resources/js/lib/format.ts', function () use ($root) {
    expect(frontendMatches(
        $root,
        '/(?<!\bmeta)\.(?:price|total|amount|subtotal|tax|discount|cost|balance|fee)\w*\s*[-+*\/%]|(?<![=<>])[-+*\/%]\s*[\w.?]+(?<!\bmeta)\.(?:price|total|amount|subtotal|tax|discount|cost|balance|fee)\w*\b|\.reduce\(\s*\(\s*(?<sum>\w+)[^,)]*,[^)]*\)\s*=>\s*\k<sum>\s*\+/',
        ['resources/js/lib/format.ts'],
    ))->toBe([]);
});

test('React reaches the backend only through Wayfinder routes and Inertia: no hard-coded URLs, fetch or axios', function () use ($root) {
    expect(frontendMatches(
        $root,
        '/(href|action)=\{?\s*["\'`]\/|router\.\w+\(\s*["\'`]\/|\bfetch\(|\baxios\b/',
    ))->toBe([]);
});

test('no other UI library: build the interface from resources/js/components/ui (shadcn)', function () use ($root) {
    $package = (array) json_decode((string) file_get_contents("{$root}/package.json"), true);
    $names = array_keys((array) ($package['dependencies'] ?? []) + (array) ($package['devDependencies'] ?? []));
    $banned = ['antd', 'bootstrap', 'react-bootstrap', 'primereact', 'semantic-ui-react', 'daisyui', 'flowbite', 'flowbite-react', '@headlessui/react'];
    $bannedScopes = ['@mui/', '@chakra-ui/', '@mantine/', '@nextui-org/', '@heroui/', '@ant-design/'];

    $found = array_values(array_filter($names, function (string $name) use ($banned, $bannedScopes): bool {
        foreach ($bannedScopes as $scope) {
            if (str_starts_with($name, $scope)) {
                return true;
            }
        }

        return in_array($name, $banned, true);
    }));

    expect($found)->toBe([]);
});

/**
 * Field names of a Resource's toArray(): the top level and the keys inside 'can'.
 *
 * @return array{top: list<string>, can: list<string>}
 */
function resourceFieldNames(string $source): array
{
    $top = [];
    $can = [];
    $inCan = false;

    foreach (explode("\n", $source) as $line) {
        if (preg_match("/^ {12}'(\w+)'\s*=>/", $line, $key) === 1) {
            $top[] = $key[1];
            $inCan = $key[1] === 'can';

            continue;
        }

        if ($inCan && preg_match("/^ {16}'(\w+)'\s*=>/", $line, $key) === 1) {
            $can[] = $key[1];
        }
    }

    return ['top' => $top, 'can' => $can];
}

/**
 * Field names of the TypeScript type that follows the "Matches {resource}" comment.
 *
 * @return array{top: list<string>, optional: list<string>, can: list<string>}|null
 */
function typeFieldNames(string $source, string $resourcePath): ?array
{
    $position = strpos($source, $resourcePath);

    if ($position === false || preg_match('/export type \w+ = \{\n(.*?)\n\};/s', $source, $type, 0, $position) !== 1) {
        return null;
    }

    $top = [];
    $optional = [];
    $can = [];
    $inCan = false;

    foreach (explode("\n", $type[1]) as $line) {
        if (preg_match('/^ {4}(\w+)(\?)?:/', $line, $key) === 1) {
            $top[] = $key[1];
            $inCan = $key[1] === 'can';

            if (($key[2] ?? '') === '?') {
                $optional[] = $key[1];
            }

            continue;
        }

        if ($inCan && preg_match('/^ {8}(\w+)\??:/', $line, $key) === 1) {
            $can[] = $key[1];
        }
    }

    return ['top' => $top, 'optional' => $optional, 'can' => $can];
}

test('every Resource has a TypeScript type with the same fields: keep the type that says "Matches app/Http/Resources/{Model}Resource.php" in step with toArray()', function () use ($root) {
    $types = [];

    foreach (glob("{$root}/resources/js/types/*.ts") ?: [] as $file) {
        $types[] = (string) file_get_contents($file);
    }

    $problems = [];

    foreach (glob("{$root}/app/Http/Resources/*.php") ?: [] as $file) {
        $resourcePath = 'app/Http/Resources/'.basename($file);
        $fields = resourceFieldNames((string) file_get_contents($file));
        $type = null;

        foreach ($types as $source) {
            $type ??= typeFieldNames($source, $resourcePath);
        }

        if ($type === null) {
            $problems[] = "{$resourcePath}: no type in resources/js/types/ says \"Matches {$resourcePath}\"";

            continue;
        }

        foreach (array_diff($fields['top'], $type['top']) as $field) {
            $problems[] = "{$resourcePath}: the TypeScript type is missing {$field}";
        }

        foreach (array_diff($type['top'], $fields['top'], $type['optional']) as $field) {
            $problems[] = "{$resourcePath}: the TypeScript type has {$field}, which the Resource does not send";
        }

        foreach (array_merge(array_diff($fields['can'], $type['can']), array_diff($type['can'], $fields['can'])) as $field) {
            $problems[] = "{$resourcePath}: can.{$field} differs between the Resource and the TypeScript type";
        }
    }

    expect($problems)->toBe([]);
});
