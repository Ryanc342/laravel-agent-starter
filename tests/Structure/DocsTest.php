<?php

use Tests\Structure\ProjectFiles;

/*
| The docs are the map the AI follows, so they are checked like code.
*/

$root = dirname(__DIR__, 2);

test('AGENTS.md stays a map of at most 100 lines: move details into docs/', function () use ($root) {
    expect(count(file("{$root}/AGENTS.md")))->toBeLessThanOrEqual(100);
});

test('every docs path that AGENTS.md and ARCHITECTURE.md mention exists: fix the path or the doc', function () use ($root) {
    $missing = [];

    foreach (['AGENTS.md', 'ARCHITECTURE.md'] as $doc) {
        preg_match_all('#(?<![\w./-])(?:app|config|database|docs|lint|resources|routes|scripts|tests)/[\w./-]*[\w/]#', file_get_contents("{$root}/{$doc}"), $matches);

        foreach (array_unique($matches[0]) as $path) {
            if (! file_exists("{$root}/{$path}")) {
                $missing[] = "{$doc}: {$path}";
            }
        }
    }

    expect($missing)->toBe([]);
});

test('every ExecPlan has the sections docs/PLANS.md requires', function () {
    $required = [
        '## Purpose',
        '## Agreed decisions',
        '## Milestones',
        '## Validation',
        '## Progress',
        '## Surprises & Discoveries',
        '## Decision Log',
        '## Outcomes & Retrospective',
    ];

    $plans = array_filter(
        ProjectFiles::in(['docs/exec-plans/active', 'docs/exec-plans/completed']),
        fn (string $path): bool => str_ends_with($path, '.md'),
    );

    $missing = [];

    foreach ($plans as $plan) {
        $lines = array_map('trim', explode("\n", ProjectFiles::read($plan)));

        foreach ($required as $heading) {
            if (! in_array($heading, $lines, true)) {
                $missing[] = "{$plan}: {$heading}";
            }
        }
    }

    expect($missing)->toBe([]);
});

test('every rule in docs/golden-principles.md carries its two tags, and business rules are checked by people: write - `[checker]` `kind` rule', function () use ($root) {
    $rule = '/^- `\[(PHPStan|oxlint|Structure|hook|review|user)\]` `(structure|business|process|ui)` \S/';
    $section = null;
    $problems = [];

    foreach (file("{$root}/docs/golden-principles.md", FILE_IGNORE_NEW_LINES) ?: [] as $number => $line) {
        if (str_starts_with($line, '## ')) {
            $section = preg_match('/^## \d+\. /', $line) === 1 ? $line : null;

            continue;
        }

        if ($section === null || ! str_starts_with($line, '- ')) {
            continue;
        }

        if (preg_match($rule, $line, $tags) !== 1) {
            $problems[] = 'line '.($number + 1).': '.$line;
        } elseif ($tags[2] === 'business' && ! in_array($tags[1], ['review', 'user'], true)) {
            $problems[] = 'line '.($number + 1).': a business rule is checked by [review] or [user], not by a machine';
        }
    }

    expect($problems)->toBe([]);
});
