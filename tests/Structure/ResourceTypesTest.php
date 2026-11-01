<?php

/*
| Principle 4 in docs/golden-principles.md: every Resource has a TypeScript
| type with the same fields, so React never guesses what the backend sends.
|
| This stays a test until Laravel Wayfinder generates the page prop types
| (docs/exec-plans/tech-debt.md). The other frontend rules are oxlint rules in
| lint/oxlint/ezdev.js.
*/

$root = dirname(__DIR__, 2);

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
