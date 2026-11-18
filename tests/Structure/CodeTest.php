<?php

/*
| Checks on the code that a linter does not do: they read files across folders.
| docs/golden-principles.md lists them as [Structure]. The checks are in
| PlacementChecks.php and CheckSettings.php.
*/

use Tests\Structure\CheckSettings;
use Tests\Structure\PlacementChecks;

test('no check is silenced: fix the code the check points at, the error says what to do', function () {
    expect(CheckSettings::noSilencers())->toBe([]);
});

test('components/ui is shadcn\'s code: it never imports the app\'s other components, pages, actions, routes or wayfinder', function () {
    expect(PlacementChecks::uiImportsNoDomainCode())->toBe([]);
});

test('TypeScript imports only from inside resources/js: no relative path that leaves it and no path into docs/', function () {
    expect(PlacementChecks::importsStayInside())->toBe([]);
});

test('PHP requires and includes only .php files: code in another kind of file escapes PHPStan', function () {
    expect(PlacementChecks::requiresOnlyPhp())->toBe([]);
});

test('Blade views hold no PHP: no @php and no <?php in resources/views, the controller or Resource prepares the data', function () {
    expect(PlacementChecks::bladeNoPhp())->toBe([]);
});
