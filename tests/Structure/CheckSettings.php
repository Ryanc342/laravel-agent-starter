<?php

namespace Tests\Structure;

use Symfony\Component\Process\Process;

/**
 * Principle 9 in docs/golden-principles.md: no check is silenced, and the
 * checks stay as set. Turning a check down is a protected change (AGENTS.md
 * "Red lines"), so these checks say so in composer check too, wherever it runs.
 * Each method returns what it found wrong; CodeTest.php and ConfigTest.php
 * expect none, and tests/Unit/Lint/SkeletonChecksTest.php breaks each once.
 */
final class CheckSettings
{
    /**
     * The layer rules of principle 2, as PHPStan resolves phpstan.neon: each
     * namespace list with exactly these folders. A message may change; the
     * folders may not.
     *
     * @var list<array{0: list<string>, 1: array<string, list<string>>}>
     */
    private const array NAMESPACES = [
        [['App\Models\*'], ['allowExceptIn' => ['app/Enums/*']]],
        [['App\Policies\*'], ['allowExceptIn' => ['app/Enums/*', 'app/Models/*']]],
        [['App\Actions\*'], ['allowExceptIn' => ['app/Enums/*', 'app/Models/*', 'app/Policies/*', 'app/Notifications/*', 'app/Http/Resources/*', 'app/Http/Requests/*']]],
        [['App\Http\*'], ['allowExceptIn' => ['app/Enums/*', 'app/Models/*', 'app/Policies/*', 'app/Notifications/*', 'app/Actions/*']]],
        [[
            'Illuminate\Http\*',
            'Symfony\Component\HttpFoundation\*',
            'Symfony\Component\HttpKernel\*',
            'Illuminate\Support\Facades\Request',
            'Illuminate\Support\Facades\Response',
            'Illuminate\Support\Facades\Session',
            'Illuminate\Support\Facades\Auth',
            'Illuminate\Support\Facades\Redirect',
            'Illuminate\Support\Facades\URL',
            'Illuminate\Support\Facades\Cookie',
            'Inertia\*',
        ], ['allowExceptIn' => ['app/Enums/*', 'app/Models/*', 'app/Policies/*', 'app/Notifications/*', 'app/Actions/*']]],
        [['Illuminate\Support\Facades\Gate', 'Illuminate\Contracts\Auth\Access\Gate', 'Illuminate\Auth\Access\AuthorizationException'], ['allowExceptIn' => ['app/Models/*']]],
        [['Illuminate\Support\Facades\DB'], ['allowExceptIn' => ['app/Http/Controllers/*']]],
        [['App\Integrations\*'], ['allowIn' => ['app/Actions/*', 'app/Integrations/*']]],
        [['Illuminate\Support\Facades\Http', 'Illuminate\Http\Client\*'], ['allowIn' => ['app/Integrations/*']]],
        [['Database\Seeders\*'], ['allowIn' => ['database/*']]],
        [['Database\Factories\*'], ['allowIn' => ['database/*', 'app/Models/*']]],
        [['Tests\*'], []],
    ];

    /**
     * The request helpers of principle 2, rule "Actions use no HTTP classes or
     * helpers".
     *
     * @var list<array{0: list<string>, 1: array<string, list<string>>}>
     */
    private const array FUNCTIONS = [
        [
            ['request()', 'response()', 'redirect()', 'back()', 'to_route()', 'inertia()', 'session()', 'auth()', 'abort()', 'abort_if()', 'abort_unless()', 'url()', 'cookie()'],
            ['allowExceptIn' => ['app/Actions/*', 'app/Enums/*', 'app/Models/*', 'app/Policies/*', 'app/Notifications/*']],
        ],
    ];

    /**
     * No check is silenced: no ignore or disable comment, no skipped, focused
     * or unfinished test, and no ignoreErrors, excludePaths or baseline.
     *
     * @return list<string>
     */
    public static function noSilencers(): array
    {
        $silencers = '/phpstan-ignore|oxlint-disable|eslint-disable|@ts-ignore|@ts-expect-error|@ts-nocheck/';
        // Only in tests: a skipped, unfinished or focused test (->only() runs that test alone).
        // Pest's modifiers chain on the test (`})->skip()` or a line of their own), unlike Collection::skip().
        $skips = '/(^\s*|\}\).*)->\s*(skip\w*|todo)\(|->\s*only\(\)|markTest(Skipped|Incomplete)\(|\b(test|it|describe)\.(skip|only|todo|fails|skipIf|runIf)\b/';
        $found = [];

        $files = ProjectFiles::code(['app', 'bootstrap', 'config', 'database', 'routes', 'resources/js', 'tests', 'lint', 'scripts'], [
            'tests/Structure',
            'tests/TestCase.php',
            'resources/js/actions',
            'resources/js/routes',
            'resources/js/wayfinder',
            'bootstrap/cache',
            'bootstrap/ssr',
        ]);

        foreach ($files as $path) {
            foreach (explode("\n", ProjectFiles::read($path)) as $number => $line) {
                if (preg_match($silencers, $line, $match) === 1 || (str_starts_with($path, 'tests/') && preg_match($skips, $line, $match) === 1)) {
                    $found[] = "{$path}:".($number + 1).": {$match[0]}";
                }
            }
        }

        // tests/TestCase.php skips the tests of a Fortify feature that is turned off, and nothing else.
        $testCase = ProjectFiles::read('tests/TestCase.php');

        if (preg_match($silencers, $testCase) === 1 || preg_match_all($skips, $testCase) !== 1 || substr_count($testCase, 'markTestSkipped(') !== 1) {
            $found[] = 'tests/TestCase.php: only skipUnlessFortifyHas() may skip a test';
        }

        if (preg_match('/^\s*(ignoreErrors|excludePaths|baseline)\s*:|phpstan-baseline/m', ProjectFiles::read('phpstan.neon')) === 1) {
            $found[] = 'phpstan.neon: ignoreErrors, excludePaths or a baseline';
        }

        return $found;
    }

    /**
     * PHPStan keeps its level, paths and extensions, registers every rule in
     * lint/phpstan, covers every folder composer autoloads, and keeps the
     * layer rules of principle 2 with their folders.
     *
     * @return list<string>
     */
    public static function phpstan(): array
    {
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

        if (self::neonList($neon, 'includes', 0) !== $includes) {
            $problems[] = 'phpstan.neon: includes are exactly '.implode(', ', $includes);
        }

        if (preg_match('#^    filesRootDir: %rootDir%/\.\./\.\./\.\.$#m', $neon) !== 1) {
            $problems[] = 'phpstan.neon: filesRootDir is %rootDir%/../../.., the project root the allowIn folders start from';
        }

        $rules = self::neonList($neon, 'rules', 0);

        foreach (ProjectFiles::in(['lint/phpstan']) as $file) {
            if (str_ends_with($file, 'Rule.php') && ! in_array('Lint\\PHPStan\\'.basename($file, '.php'), $rules, true)) {
                $problems[] = "phpstan.neon: rules lists {$file}";
            }
        }

        $paths = array_map(fn (string $path): string => rtrim($path, '/'), self::neonList($neon, 'paths', 4));

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

        return [...$problems, ...self::layerRules()];
    }

    /**
     * oxlint keeps every Ezdev rule and import/no-cycle on, fails on warnings,
     * and ignores only generated and vendored code.
     *
     * @return list<string>
     */
    public static function oxlint(): array
    {
        $vite = ProjectFiles::read('vite.config.ts');
        $problems = [];

        if (preg_match('/^    lint: \{\n(.*?)\n    \},\n    fmt:/ms', $vite, $lint) !== 1) {
            return ['vite.config.ts: the lint block comes before fmt'];
        }

        $lint = $lint[1];
        $plugin = ProjectFiles::read('lint/oxlint/ezdev.js');
        preg_match_all("/'([\\w-]+)':/", substr($plugin, (int) strpos($plugin, 'export default')), $exported);

        $required = [...array_map(fn (string $rule): string => "ezdev/{$rule}", $exported[1]), 'import/no-cycle'];

        foreach ($required as $rule) {
            if (preg_match("#'".preg_quote($rule, '#')."': (\\[\\s*)?'error'#", $lint) !== 1) {
                $problems[] = "vite.config.ts: '{$rule}' is 'error'";
            }
        }

        foreach (["plugins: ['typescript', 'unicorn', 'oxc', 'import']", "jsPlugins: ['./lint/oxlint/ezdev.js']", 'denyWarnings: true', 'typeAware: true'] as $setting) {
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

        return $problems;
    }

    /**
     * TypeScript stays strict over all of resources/js and tests/js.
     *
     * @return list<string>
     */
    public static function typeScript(): array
    {
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

        return $problems;
    }

    /**
     * Pest runs the three suites, all of them, and fails on a skipped or
     * unfinished test.
     *
     * @return list<string>
     */
    public static function pestSuites(): array
    {
        $xml = simplexml_load_string(ProjectFiles::read('phpunit.xml'));

        if ($xml === false) {
            return ['phpunit.xml: a valid PHPUnit config'];
        }

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

        return $problems;
    }

    /**
     * composer check runs every check, and the npm scripts it calls are the
     * checks themselves.
     *
     * @return list<string>
     */
    public static function composerScripts(): array
    {
        $composer = json_decode(ProjectFiles::read('composer.json'), true)['scripts'] ?? [];
        $npm = json_decode(ProjectFiles::read('package.json'), true)['scripts'] ?? [];
        $problems = [];

        foreach ([
            'check' => ['Composer\\Config::disableProcessTimeout', '@php scripts/check.php'],
            'check:static' => [
                'Composer\\Config::disableProcessTimeout',
                '@php artisan config:clear --ansi',
                '@lint:check',
                '@types:check',
                'npm run check',
                'npm run types:check',
            ],
            'check:milestone' => ['Composer\\Config::disableProcessTimeout', '@php scripts/check-milestone.php'],
            'lint:check' => ['pint --parallel --test'],
            'types:check' => ['phpstan analyse --memory-limit=1G'],
        ] as $script => $steps) {
            if (($composer[$script] ?? null) !== $steps) {
                $problems[] = "composer.json: \"{$script}\" is ".implode(' && ', $steps);
            }
        }

        foreach (['check' => 'vp check', 'test' => 'vp test', 'types:check' => 'tsc --noEmit'] as $script => $command) {
            if (($npm[$script] ?? null) !== $command) {
                $problems[] = "package.json: \"{$script}\" is {$command}";
            }
        }

        return $problems;
    }

    /**
     * No other lint config can change the checks, and npm runs no install
     * scripts and no other shell.
     *
     * @return list<string>
     */
    public static function noOtherLintConfig(): array
    {
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

        return $problems;
    }

    /**
     * The layer rules as PHPStan reads them (phpstan dump-parameters), so an
     * entry written any other way still counts as what it does.
     *
     * @return list<string>
     */
    private static function layerRules(): array
    {
        $root = ProjectFiles::root();
        $process = new Process([PHP_BINARY, dirname(__DIR__, 2).'/vendor/bin/phpstan', 'dump-parameters', '-c', "{$root}/phpstan.neon", '--json'], $root, ['PAO_DISABLE' => '1'], null, 120);
        $process->run();
        $parameters = json_decode($process->getOutput(), true);

        if (! is_array($parameters)) {
            return ['phpstan.neon: PHPStan cannot read it: '.trim($process->getErrorOutput())];
        }

        return [
            ...self::compare('disallowedNamespaces', ['namespace', 'class'], self::NAMESPACES, $parameters['disallowedNamespaces'] ?? []),
            ...self::compare('disallowedFunctionCalls', ['function'], self::FUNCTIONS, $parameters['disallowedFunctionCalls'] ?? []),
        ];
    }

    /**
     * Each expected entry is there with exactly its folders and nothing that
     * loosens it (allowInFunctions, allowParamsAnywhere and the like).
     *
     * @param  list<string>  $nameKeys
     * @param  list<array{0: list<string>, 1: array<string, list<string>>}>  $expected
     * @param  list<array<string, mixed>>  $entries
     * @return list<string>
     */
    private static function compare(string $section, array $nameKeys, array $expected, array $entries): array
    {
        $problems = [];

        foreach ($expected as [$names, $folders]) {
            $match = null;

            foreach ($entries as $entry) {
                $entryNames = [];

                foreach ($nameKeys as $key) {
                    $entryNames = [...$entryNames, ...(array) ($entry[$key] ?? [])];
                }

                if ($entryNames === $names) {
                    $match = array_diff_key($entry, array_flip([...$nameKeys, 'message', 'errorTip']));
                }
            }

            if ($match !== $folders) {
                $where = $folders === [] ? 'everywhere' : implode('; ', array_map(fn (string $key, array $paths): string => "{$key} ".implode(', ', $paths), array_keys($folders), $folders));
                $problems[] = "phpstan.neon: {$section} keeps ".implode(', ', $names)." forbidden ({$where}), with no other exception";
            }
        }

        return $problems;
    }

    /**
     * The "- value" entries of a top-level block in phpstan.neon, or of a
     * block inside parameters (paths).
     *
     * @return list<string>
     */
    private static function neonList(string $neon, string $key, int $indent): array
    {
        $pad = str_repeat(' ', $indent);

        if (preg_match("/^{$pad}{$key}:\\n((?:{$pad}\\s+- .*\\n)+)/m", $neon, $block) !== 1) {
            return [];
        }

        preg_match_all('/- (.+)$/m', $block[1], $values);

        return array_map(fn (string $value): string => trim($value), $values[1]);
    }
}
