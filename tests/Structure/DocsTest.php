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

test('every pattern file in docs/design/patterns is in the DESIGN.md index and names its sample or demo', function () use ($root) {
    $index = file_get_contents("{$root}/DESIGN.md");
    $problems = [];

    foreach (glob("{$root}/docs/design/patterns/*.md") ?: [] as $file) {
        $name = basename($file);

        if (! str_contains($index, "docs/design/patterns/{$name}")) {
            $problems[] = "{$name}: not in the DESIGN.md index";
        }

        if (preg_match('/^## (Sample and demo|Sample|Demo)$/m', file_get_contents($file)) !== 1) {
            $problems[] = "{$name}: no Sample or Demo section";
        }
    }

    expect($problems)->toBe([]);
});

test('every file a pattern file names (a sample, a demo) exists: fix the path or add the file', function () use ($root) {
    $missing = [];

    foreach (glob("{$root}/docs/design/patterns/*.md") ?: [] as $file) {
        preg_match_all('#`((?:resources|docs|app)/[\w./-]*\.(?:tsx?|php))`#', file_get_contents($file), $matches);

        foreach (array_unique($matches[1]) as $path) {
            if (! file_exists("{$root}/{$path}")) {
                $missing[] = basename($file).": {$path}";
            }
        }
    }

    expect($missing)->toBe([]);
});

test('the design spec names tokens and component sizes, never a pixel value or a Tailwind size class: name the token or the size variant', function () use ($root) {
    $files = array_merge(
        ["{$root}/DESIGN.md"],
        glob("{$root}/docs/design/*.md") ?: [],
        glob("{$root}/docs/design/patterns/*.md") ?: [],
    );
    $value = '/\\b\\d+(?:\\.\\d+)?(?:px|rem|em)\\b|\\b[a-z-]+-\\[[^\\]]+\\]|(?<![\\w-])(?:text-(?:xs|sm|base|lg|xl|\\dxl|display(?:-lg)?)|(?:min-|max-)?(?:h|w|size)-\\d|(?:p|px|py|pt|pb|pl|pr|m|mx|my|gap|space-x|space-y)-\\d)\\b/';
    $problems = [];

    foreach ($files as $file) {
        foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $number => $line) {
            if (preg_match($value, $line, $match) === 1) {
                $problems[] = basename($file).':'.($number + 1).': '.$match[0];
            }
        }
    }

    expect($problems)->toBe([]);
});
