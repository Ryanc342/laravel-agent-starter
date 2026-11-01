<?php

/*
| The docs are the map the AI follows, so they are checked like code.
*/

$root = dirname(__DIR__, 2);

test('AGENTS.md stays a map of at most 100 lines: move details into docs/', function () use ($root) {
    expect(count(file("{$root}/AGENTS.md")))->toBeLessThanOrEqual(100);
});

test('docs/golden-principles.md has at most 27 [review] rules: to add one, merge, remove or turn another into a check', function () use ($root) {
    preg_match_all('/^- `\[review\]` /m', file_get_contents("{$root}/docs/golden-principles.md") ?: '', $rules);

    expect(count($rules[0]))->toBeLessThanOrEqual(27);
});

test('every docs path that AGENTS.md and ARCHITECTURE.md mention exists: fix the path or the doc', function () use ($root) {
    $missing = [];

    foreach (['AGENTS.md', 'ARCHITECTURE.md'] as $doc) {
        preg_match_all('#(?:docs|tests|scripts|lint)/[\w./-]*[\w/]#', file_get_contents("{$root}/{$doc}"), $matches);

        foreach (array_unique($matches[0]) as $path) {
            if (! file_exists("{$root}/{$path}")) {
                $missing[] = "{$doc}: {$path}";
            }
        }
    }

    expect($missing)->toBe([]);
});

test('every ExecPlan has the sections docs/PLANS.md requires', function () use ($root) {
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

    $plans = array_merge(
        glob("{$root}/docs/exec-plans/active/*.md") ?: [],
        glob("{$root}/docs/exec-plans/completed/*.md") ?: [],
    );

    $missing = [];

    foreach ($plans as $plan) {
        $lines = array_map('trim', file($plan));

        foreach ($required as $heading) {
            if (! in_array($heading, $lines, true)) {
                $missing[] = basename(dirname($plan)).'/'.basename($plan).": {$heading}";
            }
        }
    }

    expect($missing)->toBe([]);
});
