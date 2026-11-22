<?php

use Tests\Structure\CheckSettings;
use Tests\Structure\PlacementChecks;
use Tests\Structure\ProjectFiles;

/*
| In the template only: every machine check is proved on a broken fixture
| (SkeletonChecksTest.php), so a check cannot be dropped from either side.
*/

test('every structure check is proved on a broken fixture: no fixture can be dropped', function () {
    $fixtures = ProjectFiles::read('template/tests/Lint/SkeletonChecksTest.php');
    $missing = [];

    foreach ([PlacementChecks::class, CheckSettings::class] as $class) {
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

test('PHPStan, oxlint and the structure checks are proved on broken fixtures for every kept rule family, and on clean ones that they block nothing allowed', function () {
    $fixtures = ProjectFiles::read('template/tests/Lint/SkeletonChecksTest.php');
    $missing = [];

    foreach (['skeletonPhpstanErrors()', 'skeletonOxlintErrors()', 'skeletonPhpstanCleanFiles()', 'skeletonOxlintCleanFiles()', 'skeletonStructureCleanFixture()', 'ezdev(component-boundaries)', 'ezdev(submit-button)', 'import(no-cycle)'] as $proof) {
        if (! str_contains($fixtures, $proof)) {
            $missing[] = "template/tests/Lint/SkeletonChecksTest.php: {$proof}";
        }
    }

    expect($missing)->toBe([]);
});
