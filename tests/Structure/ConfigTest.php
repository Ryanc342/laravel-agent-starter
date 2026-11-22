<?php

use Tests\Structure\CheckSettings;
use Tests\Structure\PlacementChecks;
use Tests\Structure\ProjectFiles;

/*
| The checks stay as set (.claude/rules/skeleton.md, ALL-10 and ALL-11). The
| template keeps its machine rules (the `machine` rules of the cards); none is
| turned down, and these tests say so in composer check too, wherever it runs.
| The checks are in CheckSettings.php.
*/

test('PHPStan keeps its level, paths and extensions, covers every folder composer autoloads, and keeps every layer rule of the skeleton with its folders', function () {
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

test('every structure check runs on this project: no check can be dropped from tests/Structure', function () {
    $structure = implode("\n", array_map(
        fn (string $path): string => ProjectFiles::read($path),
        array_filter(ProjectFiles::in(['tests/Structure']), fn (string $path): bool => str_ends_with($path, 'Test.php')),
    ));
    $missing = [];

    foreach ([PlacementChecks::class, CheckSettings::class] as $class) {
        $short = (new ReflectionClass($class))->getShortName();

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (! str_contains($structure, "{$short}::{$method->getName()}()")) {
                $missing[] = "tests/Structure: no test calls {$short}::{$method->getName()}()";
            }
        }
    }

    expect($missing)->toBe([]);
});
