<?php

use Tests\Structure\CheckSettings;
use Tests\Structure\DesignDocChecks;
use Tests\Structure\PlacementChecks;
use Tests\Structure\ProjectFiles;

/*
| Principle 9 in docs/golden-principles.md: the checks stay as set. The template
| keeps 18 machine rules (docs/golden-principles.md); turning one
| down is a protected change (AGENTS.md "Red lines"), so these tests say so in
| composer check too, wherever it runs. The checks are in CheckSettings.php.
*/

test('PHPStan keeps its level, paths and extensions, covers every folder composer autoloads, and keeps every layer rule of principle 2 with its folders', function () {
    expect(CheckSettings::phpstan())->toBe([]);
});

test('oxlint keeps every Ezdev rule and import/no-cycle on, fails on warnings, and ignores only generated and vendored code', function () {
    expect(CheckSettings::oxlint())->toBe([]);
});

test('TypeScript stays strict over all of resources/js and tests/js', function () {
    expect(CheckSettings::typeScript())->toBe([]);
});

test('Pest runs the three suites, all of them, and fails on a skipped or unfinished test', function () {
    expect(CheckSettings::pestSuites())->toBe([]);
});

test('composer check runs every check, and the npm scripts it calls are the checks themselves', function () {
    expect(CheckSettings::composerScripts())->toBe([]);
});

test('no other lint config can change the checks, and npm runs no install scripts and no other shell', function () {
    expect(CheckSettings::noOtherLintConfig())->toBe([]);
});

test('every structure check runs on this project and is proved on a broken fixture: a check cannot be dropped from either', function () {
    $structure = implode("\n", array_map(
        fn (string $path): string => ProjectFiles::read($path),
        array_filter(ProjectFiles::in(['tests/Structure']), fn (string $path): bool => str_ends_with($path, 'Test.php')),
    ));
    $fixtures = ProjectFiles::read('tests/Unit/Lint/SkeletonChecksTest.php');
    $missing = [];

    foreach ([PlacementChecks::class, CheckSettings::class, DesignDocChecks::class] as $class) {
        $short = (new ReflectionClass($class))->getShortName();

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $call = "{$short}::{$method->getName()}()";

            if (! str_contains($structure, $call)) {
                $missing[] = "tests/Structure: no test calls {$call}";
            }

            if (! str_contains($fixtures, $call)) {
                $missing[] = "tests/Unit/Lint/SkeletonChecksTest.php: no fixture breaks {$call}";
            }
        }
    }

    expect($missing)->toBe([]);
});

test('PHPStan and oxlint are proved on broken fixtures for every kept rule family', function () {
    $fixtures = ProjectFiles::read('tests/Unit/Lint/SkeletonChecksTest.php');
    $missing = [];

    foreach (['skeletonPhpstanErrors()', 'skeletonOxlintErrors()', 'ezdev(component-boundaries)', 'ezdev(no-module-level-t)', 'import(no-cycle)'] as $proof) {
        if (! str_contains($fixtures, $proof)) {
            $missing[] = "tests/Unit/Lint/SkeletonChecksTest.php: {$proof}";
        }
    }

    expect($missing)->toBe([]);
});
