<?php

use Tests\Structure\CheckSettings;
use Tests\Structure\DesignDocChecks;
use Tests\Structure\PlacementChecks;
use Tests\Structure\ProjectFiles;

/*
| In the template only: every machine check is proved on a broken fixture
| (SkeletonChecksTest.php), so a check cannot be dropped from either side.
*/

test('every structure check is proved on a broken fixture: no fixture can be dropped', function () {
    $fixtures = ProjectFiles::read('template/tests/Lint/SkeletonChecksTest.php');
    $missing = [];

    foreach ([PlacementChecks::class, CheckSettings::class, DesignDocChecks::class] as $class) {
        $short = (new ReflectionClass($class))->getShortName();

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $call = "{$short}::{$method->getName()}()";

            if (! str_contains($fixtures, $call)) {
                $missing[] = "template/tests/Lint/SkeletonChecksTest.php: no fixture breaks {$call}";
            }
        }
    }

    expect($missing)->toBe([]);
});

test('PHPStan and oxlint are proved on broken fixtures for every kept rule family', function () {
    $fixtures = ProjectFiles::read('template/tests/Lint/SkeletonChecksTest.php');
    $missing = [];

    foreach (['skeletonPhpstanErrors()', 'skeletonOxlintErrors()', 'ezdev(component-boundaries)', 'ezdev(no-module-level-t)', 'import(no-cycle)'] as $proof) {
        if (! str_contains($fixtures, $proof)) {
            $missing[] = "template/tests/Lint/SkeletonChecksTest.php: {$proof}";
        }
    }

    expect($missing)->toBe([]);
});
