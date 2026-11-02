<?php

use Tests\Structure\ProjectFiles;

/*
| Principle 9 in docs/golden-principles.md: the checks stay as set. Turning a
| check down is a protected change (AGENTS.md "Red lines"), so this test says
| so in composer check too, wherever it runs.
*/

/**
 * The lines of a top-level block in phpstan.neon, or of a block inside
 * parameters (paths), as their "- value" entries.
 *
 * @return list<string>
 */
function neonList(string $neon, string $key, int $indent): array
{
    $pad = str_repeat(' ', $indent);

    if (preg_match("/^{$pad}{$key}:\\n((?:{$pad}\\s+- .*\\n)+)/m", $neon, $block) !== 1) {
        return [];
    }

    preg_match_all('/- (.+)$/m', $block[1], $values);

    return array_map(fn (string $value): string => trim($value), $values[1]);
}

test('PHPStan keeps its level, paths, extensions and every Ezdev rule, and covers every folder composer autoloads', function () {
    $neon = ProjectFiles::read('phpstan.neon');
    $problems = [];

    if (preg_match('/^    level:\s*(\d+|max)\s*$/m', $neon, $level) !== 1 || ($level[1] !== 'max' && (int) $level[1] < 7)) {
        $problems[] = 'phpstan.neon: level is 7 or higher';
    }

    $includes = [
        'vendor/larastan/larastan/extension.neon',
        'vendor/nesbot/carbon/extension.neon',
        'vendor/spaze/phpstan-disallowed-calls/extension.neon',
    ];

    if (neonList($neon, 'includes', 0) !== $includes) {
        $problems[] = 'phpstan.neon: includes are exactly '.implode(', ', $includes);
    }

    $rules = neonList($neon, 'rules', 0);

    foreach (ProjectFiles::in(['lint/phpstan']) as $file) {
        if (str_ends_with($file, 'Rule.php') && ! in_array('Lint\\PHPStan\\'.basename($file, '.php'), $rules, true)) {
            $problems[] = "phpstan.neon: rules lists {$file}";
        }
    }

    $paths = array_map(fn (string $path): string => rtrim($path, '/'), neonList($neon, 'paths', 4));

    foreach (['app', 'bootstrap/app.php', 'config', 'database', 'routes'] as $path) {
        if (! in_array($path, $paths, true)) {
            $problems[] = "phpstan.neon: paths lists {$path}/";
        }
    }

    $autoload = json_decode(ProjectFiles::read('composer.json'), true)['autoload'] ?? [];
    $folders = array_merge(
        array_merge(...array_map(fn (string|array $dirs): array => (array) $dirs, array_values($autoload['psr-4'] ?? []))),
        $autoload['classmap'] ?? [],
        $autoload['files'] ?? [],
    );

    foreach ($folders as $folder) {
        $folder = rtrim($folder, '/');
        $covered = array_filter($paths, fn (string $path): bool => $folder === $path || str_starts_with($folder, "{$path}/"));

        if ($covered === []) {
            $problems[] = "composer.json autoloads {$folder}, which PHPStan does not read: put the code in app/";
        }
    }

    expect($problems)->toBe([]);
});

test('oxlint keeps every Ezdev rule on, fails on warnings, and ignores only generated and vendored code', function () {
    $vite = ProjectFiles::read('vite.config.ts');
    $problems = [];

    if (preg_match('/^    lint: \{\n(.*?)\n    \},\n    fmt:/ms', $vite, $lint) !== 1) {
        expect('vite.config.ts has its lint block before fmt')->toBeFalse();

        return;
    }

    $lint = $lint[1];
    $plugin = ProjectFiles::read('lint/oxlint/ezdev.js');
    preg_match_all("/'([\\w-]+)':/", substr($plugin, (int) strpos($plugin, 'export default')), $exported);

    $required = array_merge(
        array_map(fn (string $rule): string => "ezdev/{$rule}", $exported[1]),
        ['import/no-cycle', 'no-restricted-imports'],
    );

    foreach ($required as $rule) {
        if (preg_match("#'".preg_quote($rule, '#')."': (\\[\\s*)?'error'#", $lint) !== 1) {
            $problems[] = "vite.config.ts: '{$rule}' is 'error'";
        }
    }

    foreach (["jsPlugins: ['./lint/oxlint/ezdev.js']", 'denyWarnings: true', 'typeAware: true'] as $setting) {
        if (! str_contains($lint, $setting)) {
            $problems[] = "vite.config.ts: lint keeps {$setting}";
        }
    }

    if (preg_match('/\boverrides\b|\bglobals\b|\bsettings\b/', $lint) === 1) {
        $problems[] = 'vite.config.ts: lint has no overrides, globals or settings';
    }

    $ignored = [
        'vendor/**', 'node_modules/**', 'public/**', 'bootstrap/ssr/**', 'docs/**', 'tailwind.config.js',
        'resources/js/actions/**', 'resources/js/components/ui/*', 'resources/js/routes/**', 'resources/js/wayfinder/**',
    ];
    preg_match('/ignorePatterns: \[(.*?)\]/s', $lint, $patterns);
    preg_match_all("/'([^']+)'/", $patterns[1] ?? '', $patterns);

    foreach (array_diff($patterns[1], $ignored) as $pattern) {
        $problems[] = "vite.config.ts: lint ignores only generated and vendored code, not {$pattern}";
    }

    if (! str_contains($vite, "include: ['tests/js/**/*.test.{ts,tsx}'],") || preg_match('/^\s+exclude:/m', $vite) === 1) {
        $problems[] = 'vite.config.ts: Vitest runs every tests/js/**/*.test.{ts,tsx} and excludes none';
    }

    expect($problems)->toBe([]);
});

test('TypeScript stays strict over all of resources/js and tests/js', function () {
    // JSON with comments: drop the comments, never the text inside a string ("@/*").
    $tsconfig = (string) preg_replace_callback('#("(?:\\\\.|[^"\\\\])*")|/\*.*?\*/|//[^\n]*#s', fn (array $match): string => $match[1] ?? '', ProjectFiles::read('tsconfig.json'));
    $config = json_decode((string) preg_replace('/,(\s*[}\]])/', '$1', $tsconfig), true) ?? [];
    $options = $config['compilerOptions'] ?? [];
    $problems = [];

    if (($options['strict'] ?? false) !== true) {
        $problems[] = 'tsconfig.json: "strict": true';
    }

    foreach ($options as $option => $value) {
        if ($value === false && preg_match('/^(strict\w*|noImplicit\w*|alwaysStrict|useUnknownInCatchVariables)$/', $option) === 1) {
            $problems[] = "tsconfig.json: {$option} is not turned off";
        }
    }

    $include = ['resources/js/**/*.ts', 'resources/js/**/*.d.ts', 'resources/js/**/*.tsx', 'tests/js/**/*.ts', 'tests/js/**/*.tsx'];

    if (array_diff($include, $config['include'] ?? []) !== [] || isset($config['exclude']) || isset($config['files'])) {
        $problems[] = 'tsconfig.json: include is '.implode(', ', $include).', with no exclude or files';
    }

    expect($problems)->toBe([]);
});

test('Pest runs the three suites, all of them, and fails on a skipped or unfinished test', function () {
    $xml = simplexml_load_string(ProjectFiles::read('phpunit.xml'));
    $problems = [];

    foreach (['bootstrap' => 'tests/bootstrap.php', 'failOnSkipped' => 'true', 'failOnIncomplete' => 'true'] as $attribute => $value) {
        if ((string) ($xml[$attribute] ?? '') !== $value) {
            $problems[] = "phpunit.xml: {$attribute}=\"{$value}\"";
        }
    }

    $suites = [];

    foreach ($xml->testsuites->testsuite ?? [] as $suite) {
        $suites[(string) $suite['name']] = array_map('strval', iterator_to_array($suite->children(), false));

        if (isset($suite->exclude) || isset($suite->file)) {
            $problems[] = "phpunit.xml: the {$suite['name']} suite excludes nothing";
        }
    }

    if ($suites !== ['Unit' => ['tests/Unit'], 'Feature' => ['tests/Feature'], 'Structure' => ['tests/Structure']]) {
        $problems[] = 'phpunit.xml: the suites are Unit, Feature and Structure, each its own tests/ folder';
    }

    if (isset($xml->groups)) {
        $problems[] = 'phpunit.xml: no groups are left out';
    }

    expect($problems)->toBe([]);
});

test('composer check runs every check, and the npm scripts it calls are the checks themselves', function () {
    $composer = json_decode(ProjectFiles::read('composer.json'), true)['scripts'] ?? [];
    $npm = json_decode(ProjectFiles::read('package.json'), true)['scripts'] ?? [];
    $problems = [];

    $check = [
        'Composer\\Config::disableProcessTimeout',
        '@php scripts/hooks/finish-check.php checking',
        '@php artisan config:clear --ansi',
        '@lint:check',
        '@types:check',
        'npm run check',
        'npm run types:check',
        'npm test',
        '@php artisan test --compact',
        '@php scripts/hooks/finish-check.php checked',
    ];

    foreach (['check' => $check, 'lint:check' => ['pint --parallel --test'], 'types:check' => ['phpstan analyse --memory-limit=1G']] as $script => $steps) {
        if (($composer[$script] ?? null) !== $steps) {
            $problems[] = "composer.json: \"{$script}\" is ".implode(' && ', $steps);
        }
    }

    foreach (['check' => 'vp check', 'test' => 'vp test', 'types:check' => 'tsc --noEmit'] as $script => $command) {
        if (($npm[$script] ?? null) !== $command) {
            $problems[] = "package.json: \"{$script}\" is {$command}";
        }
    }

    expect($problems)->toBe([]);
});

test('no other lint config can change the checks, and npm runs no install scripts and no other shell', function () {
    $problems = [];

    foreach (ProjectFiles::everywhere() as $path) {
        if (preg_match('/^(\.?(oxlintrc|eslintrc)(\..+)?|eslint\.config\..+|oxlint\.json|\.(oxlint|eslint)ignore)$/', basename($path)) === 1) {
            $problems[] = "{$path}: the lint config lives in vite.config.ts";
        }
    }

    $npmrc = ProjectFiles::read('.npmrc');

    if (preg_match('/^\s*ignore-scripts\s*=\s*true\s*$/m', $npmrc) !== 1 || preg_match('/^\s*script-shell\s*=/m', $npmrc) === 1) {
        $problems[] = '.npmrc: ignore-scripts=true and no script-shell';
    }

    expect($problems)->toBe([]);
});
