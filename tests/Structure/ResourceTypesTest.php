<?php

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Tests\Structure\ProjectFiles;

/*
| Principle 4 in docs/golden-principles.md: every Resource has a TypeScript
| type with the same fields, so React never guesses what the backend sends.
|
| This stays a test until a tagged Laravel Wayfinder release generates the
| page prop types (docs/rules/frontend.md).
*/

/**
 * Field names of a Resource's toArray(): the top level and the keys inside 'can'.
 * Null when toArray() does not return one literal array with fixed keys.
 *
 * @return array{top: list<string>, can: list<string>}|null
 */
function resourceFieldNames(string $source): ?array
{
    $finder = new NodeFinder;
    $ast = (new ParserFactory)->createForHostVersion()->parse($source) ?? [];
    $method = $finder->findFirst($ast, fn (Node $node): bool => $node instanceof ClassMethod && $node->name->toLowerString() === 'toarray');

    if (! $method instanceof ClassMethod) {
        return null;
    }

    $returns = $finder->findInstanceOf($method->stmts ?? [], Return_::class);

    if (count($returns) !== 1 || ! $returns[0]->expr instanceof Array_) {
        return null;
    }

    $keys = function (Array_ $array): ?array {
        $names = [];

        foreach ($array->items as $item) {
            if (! $item->key instanceof String_ || $item->unpack) {
                return null;
            }

            $names[$item->key->value] = $item->value;
        }

        return $names;
    };

    $top = $keys($returns[0]->expr);

    if ($top === null) {
        return null;
    }

    $can = [];

    if (isset($top['can'])) {
        $can = $top['can'] instanceof Array_ ? $keys($top['can']) : null;

        if ($can === null) {
            return null;
        }
    }

    return ['top' => array_keys($top), 'can' => array_keys($can)];
}

/**
 * Field names of the TypeScript type that follows the "Matches {resource}" comment.
 *
 * @return array{top: list<string>, optional: list<string>, can: list<string>}|null
 */
function typeFieldNames(string $source, string $resourcePath): ?array
{
    $position = strpos($source, "Matches {$resourcePath}");

    if ($position === false || preg_match('/export (?:type \w+ =|interface \w+)\s*\{/', $source, $match, PREG_OFFSET_CAPTURE, $position) !== 1) {
        return null;
    }

    // Read the body brace by brace, without comments, so layout does not matter.
    $body = substr($source, $match[0][1] + strlen($match[0][0]));
    $body = (string) preg_replace(['#/\*.*?\*/#s', '#//[^\n]*#'], '', $body);

    $top = [];
    $optional = [];
    $can = [];
    $depth = 1;
    $key = null;

    foreach (preg_split('/([{};,\n])/', $body, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
        if ($part === '{') {
            $depth++;

            continue;
        }

        if ($part === '}') {
            $depth--;

            if ($depth === 0) {
                break;
            }

            continue;
        }

        if (preg_match('/^\s*(?:readonly\s+)?[\'"]?(\w+)[\'"]?(\?)?\s*:/', $part, $field) !== 1) {
            continue;
        }

        if ($depth === 1) {
            $key = $field[1];
            $top[] = $key;

            if (($field[2] ?? '') === '?') {
                $optional[] = $key;
            }
        } elseif ($depth === 2 && $key === 'can') {
            $can[] = $field[1];
        }
    }

    return ['top' => $top, 'optional' => $optional, 'can' => $can];
}

test('every Resource has a TypeScript type with the same fields: keep the type that says "Matches app/Http/Resources/{Model}Resource.php" in step with toArray()', function () {
    $types = array_map(ProjectFiles::read(...), ProjectFiles::in(['resources/js/types']));
    $problems = [];

    foreach (ProjectFiles::in(['app/Http/Resources']) as $resourcePath) {
        $fields = resourceFieldNames(ProjectFiles::read($resourcePath));

        if ($fields === null) {
            $problems[] = "{$resourcePath}: toArray() returns one literal array with fixed keys ('id' => …), and 'can' is a literal array too";

            continue;
        }

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
