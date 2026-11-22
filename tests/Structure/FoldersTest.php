<?php

/*
| Files go where the skeleton says (.claude/rules/skeleton.md, the locations,
| ALL-7 and ALL-8): the folders of app/, where pages and components live, and
| code lives only where the checks read it. Each folder lists what it may hold, so a file
| in a new place is a decision the user sees, not something the AI slips in.
| The checks are in PlacementChecks.php.
*/

use Tests\Structure\PlacementChecks;

test('app/ holds only the folders Actions, Concerns, Console, Enums, Http, Jobs, Models, Notifications, Policies, Providers and Support', function () {
    expect(PlacementChecks::appFolders())->toBe([]);
});

test('pages live in resources/js/pages/{domain}/: no file directly in resources/js/pages/', function () {
    expect(PlacementChecks::pagesInDomainFolders())->toBe([]);
});

test('components live in resources/js/components/{domain}/ or components/shared/: only the starter kit\'s layout and general components sit at the components root', function () {
    expect(PlacementChecks::componentsRoot())->toBe([]);
});

test('the style page shows every shared component: show a new one in resources/js/components/style', function () {
    expect(PlacementChecks::styleShowsSharedComponents())->toBe([]);
});

test('resources/js holds only TypeScript: every file is .ts or .tsx', function () {
    expect(PlacementChecks::typeScriptOnly())->toBe([]);
});

test('resources/js holds only app.tsx and the folders components, hooks, layouts, lib, pages and types (and Wayfinder\'s generated actions, routes and wayfinder)', function () {
    expect(PlacementChecks::resourcesJsFolders())->toBe([]);
});

test('docs/ holds only Markdown (.md) files', function () {
    expect(PlacementChecks::docsMarkdownOnly())->toBe([]);
});

test('the repository holds no symlinks: a link points the checks at files outside the project', function () {
    expect(PlacementChecks::noSymlinks())->toBe([]);
});

test('public/ holds no PHP file except index.php: code lives in app/', function () {
    expect(PlacementChecks::publicNoPhp())->toBe([]);
});
