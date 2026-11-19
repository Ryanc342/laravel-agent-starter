<?php

use Tests\Structure\CheckSettings;

/*
| Principle 9 in docs/golden-principles.md: the checks stay as set. The template
| keeps its machine rules (tagged in .claude/rules/); none is turned down
| (AGENTS.md "Red lines"), and these tests say so in composer check too,
| wherever it runs. The checks are in CheckSettings.php.
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
