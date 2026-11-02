<?php

/*
| Files go where the conventions say (docs/golden-principles.md, principle 7).
| Each folder lists what it may hold, so a file in a new place is a decision
| the user sees, not something the AI slips in.
*/

use Tests\Structure\ProjectFiles;

test('PHP tests in tests/Feature and tests/Unit are named …Test.php: helpers go in tests/Support', function () {
    $found = [];

    foreach (ProjectFiles::in(['tests/Feature', 'tests/Unit']) as $path) {
        if (str_ends_with($path, '.php') && ! str_ends_with($path, 'Test.php')) {
            $found[] = $path;
        }
    }

    expect($found)->toBe([]);
});

test('tests/ holds only the folders Feature, Unit, Structure, Support and js, and bootstrap.php, Pest.php and TestCase.php', function () {
    $allowed = ['Feature', 'Unit', 'Structure', 'Support', 'js', 'bootstrap.php', 'Pest.php', 'TestCase.php'];

    $found = array_map(
        fn (string $name): string => "tests/{$name}",
        array_values(array_diff(ProjectFiles::entries('tests'), $allowed)),
    );

    expect($found)->toBe([]);
});

test('frontend tests live in tests/js: no *.test.* or *.spec.* file anywhere else', function () {
    $found = [];

    foreach (ProjectFiles::everywhere(['public', 'tests/js']) as $path) {
        if (preg_match('/\.(test|spec)\./', basename($path)) === 1) {
            $found[] = $path;
        }
    }

    expect($found)->toBe([]);
});

test('tests/js holds only Vitest tests named *.test.ts or *.test.tsx, and tests/js/setup.ts', function () {
    $found = [];

    foreach (ProjectFiles::in(['tests/js']) as $path) {
        if ($path !== 'tests/js/setup.ts' && preg_match('/\.test\.tsx?$/', $path) !== 1) {
            $found[] = $path;
        }
    }

    expect($found)->toBe([]);
});

test('resources/js holds only TypeScript: every file is .ts or .tsx', function () {
    $found = [];

    foreach (ProjectFiles::in(['resources/js'], ['resources/js/actions', 'resources/js/routes', 'resources/js/wayfinder']) as $path) {
        if (preg_match('/\.tsx?$/', $path) !== 1) {
            $found[] = $path;
        }
    }

    expect($found)->toBe([]);
});

test('app/ holds only the folders Actions, Concerns, Console, Enums, Http, Integrations, Models, Notifications, Policies, Providers and Support', function () {
    $allowed = ['Actions', 'Concerns', 'Console', 'Enums', 'Http', 'Integrations', 'Models', 'Notifications', 'Policies', 'Providers', 'Support'];

    $found = [];

    foreach (ProjectFiles::entries('app') as $name) {
        if (! in_array($name, $allowed, true) || ! is_dir(ProjectFiles::root()."/app/{$name}")) {
            $found[] = "app/{$name}";
        }
    }

    expect($found)->toBe([]);
});

test('resources/js holds only app.tsx and the folders components, hooks, layouts, lib, pages and types (and Wayfinder\'s generated actions, routes and wayfinder)', function () {
    $allowed = ['app.tsx', 'components', 'hooks', 'layouts', 'lib', 'pages', 'types', 'actions', 'routes', 'wayfinder'];

    $found = array_map(
        fn (string $name): string => "resources/js/{$name}",
        array_values(array_diff(ProjectFiles::entries('resources/js'), $allowed)),
    );

    expect($found)->toBe([]);
});

test('pages live in resources/js/pages/{domain}/: no file directly in resources/js/pages/', function () {
    $found = [];

    foreach (ProjectFiles::in(['resources/js/pages']) as $path) {
        if (dirname($path) === 'resources/js/pages') {
            $found[] = $path;
        }
    }

    expect($found)->toBe([]);
});

test('components live in resources/js/components/{domain}/ or components/shared/: only the starter kit\'s layout and general components sit at the components root', function () {
    // The starter kit's own components. A new component goes in a domain
    // folder or components/shared, never next to these.
    $starterKit = [
        'alert-error.tsx',
        'app-content.tsx',
        'app-logo-icon.tsx',
        'app-logo.tsx',
        'app-shell.tsx',
        'app-sidebar-header.tsx',
        'app-sidebar.tsx',
        'breadcrumbs.tsx',
        'heading.tsx',
        'input-error.tsx',
        'nav-footer.tsx',
        'nav-main.tsx',
        'nav-user.tsx',
        'password-input.tsx',
        'text-link.tsx',
        'user-info.tsx',
        'user-menu-content.tsx',
    ];

    $found = [];

    foreach (ProjectFiles::in(['resources/js/components']) as $path) {
        if (dirname($path) === 'resources/js/components' && ! in_array(basename($path), $starterKit, true)) {
            $found[] = $path;
        }
    }

    expect($found)->toBe([]);
});

test('docs/ holds only Markdown (.md) files, apart from the reference feature in docs/reference/', function () {
    $found = [];

    foreach (ProjectFiles::in(['docs'], ['docs/reference']) as $path) {
        if (! str_ends_with($path, '.md')) {
            $found[] = $path;
        }
    }

    expect($found)->toBe([]);
});

test('the repository holds no symlinks: a link points the checks at files outside the project', function () {
    $found = [];

    foreach (ProjectFiles::everywhere() as $path) {
        if (is_link(ProjectFiles::root()."/{$path}")) {
            $found[] = "{$path} -> ".readlink(ProjectFiles::root()."/{$path}");
        }
    }

    expect($found)->toBe([]);
});

test('public/ holds no PHP file except index.php: code lives in app/', function () {
    $found = [];

    foreach (ProjectFiles::in(['public'], ['public/storage', 'public/build']) as $path) {
        if ($path !== 'public/index.php' && preg_match('/\.(php\d?|phtml|phar)$/i', $path) === 1) {
            $found[] = $path;
        }
    }

    expect($found)->toBe([]);
});
