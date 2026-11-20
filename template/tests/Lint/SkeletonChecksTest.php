<?php

use Symfony\Component\Process\Process;
use Tests\Structure\CheckSettings;
use Tests\Structure\DesignDocChecks;
use Tests\Structure\PlacementChecks;
use Tests\Structure\ProjectFiles;

require_once dirname(__DIR__, 3).'/tests/Support/Workspace.php';

/*
| The template keeps its machine rules (the `[PHPStan]`, `[oxlint]` and `[Structure]`
| rules of .claude/rules/: the layer rules of principle 2, component boundaries and no import cycles, the folders of app/,
| where pages and components live, code lives only where the checks read it,
| no check is silenced, the checks stay as set, the design spec's index and its
| values, no t() at module level). Each test plants code that
| breaks one kept rule in a fixture project outside the repository and expects
| the real check, run with the real config, to reject it.
*/

/**
 * A fixture project in the system's temp folder, removed when the run ends.
 *
 * @param  array<string, string>  $files  path relative to the fixture => content
 */
function skeletonFixture(array $files = []): string
{
    // The real path (workspaceFolder): PHPStan matches allowIn paths against it.
    $root = workspaceFolder('skeleton');

    foreach ($files as $path => $content) {
        workspaceWrite($root, $path, $content);
    }

    return $root;
}

/**
 * laravel/pao shortens PHPStan's report when an AI agent runs it; the check
 * reads the whole report.
 *
 * @return array<string, string>
 */
function skeletonPlainOutput(): array
{
    return ['PAO_DISABLE' => '1'];
}

/**
 * Runs PHPStan once, with the project's phpstan.neon, over a fixture with one
 * file per kept PHPStan rule. The fixture's own config only moves the folder
 * the allowIn paths are read from to the fixture.
 *
 * @return array<string, list<string>> fixture path => messages
 */
function skeletonPhpstanErrors(): array
{
    static $errors = null;

    if ($errors !== null) {
        return $errors;
    }

    $project = dirname(__DIR__, 3);
    $root = skeletonFixture(skeletonPhpstanFiles());
    workspaceWrite($root, 'phpstan.neon', implode("\n", [
        'includes:',
        "    - {$project}/phpstan.neon",
        'parameters:',
        "    filesRootDir: {$root}",
        "    tmpDir: {$root}/cache",
        '',
    ]));

    $process = new Process([PHP_BINARY, "{$project}/vendor/bin/phpstan", 'analyse', '-c', "{$root}/phpstan.neon", '--error-format=json', '--no-progress', '--memory-limit=1G', "{$root}/app"], $project, skeletonPlainOutput(), null, 300);
    $process->run();

    $report = json_decode($process->getOutput(), true);
    expect($report)->toBeArray("PHPStan gave no report:\n{$process->getErrorOutput()}")
        ->and($report['errors'])->toBe([], 'PHPStan could not analyse the fixture');

    $errors = [];

    foreach ($report['files'] as $file => $result) {
        foreach ($result['messages'] as $message) {
            if (str_starts_with($message['identifier'] ?? '', 'disallowed.')) {
                $errors[substr($file, strlen($root) + 1)][] = $message['message'];
            }
        }
    }

    return $errors;
}

/**
 * @return array<string, string>
 */
function skeletonPhpstanFiles(): array
{
    $class = fn (string $namespace, string $name, string $use, string $body): string => "<?php\n\nnamespace {$namespace};\n\n{$use}\n\nfinal class {$name}\n{\n{$body}\n}\n";

    return [
        'app/Enums/SkeletonKind.php' => "<?php\n\nnamespace App\\Enums;\n\nuse App\\Models\\User;\n\nenum SkeletonKind: string\n{\n    case A = 'a';\n\n    public function owner(): ?User\n    {\n        return null;\n    }\n}\n",
        'app/Enums/SkeletonRequestKind.php' => "<?php\n\nnamespace App\\Enums;\n\nuse Illuminate\\Http\\Request;\n\nenum SkeletonRequestKind: string\n{\n    case A = 'a';\n\n    public function read(Request \$request): string\n    {\n        return '';\n    }\n}\n",
        'app/Models/SkeletonPolicyModel.php' => $class('App\\Models', 'SkeletonPolicyModel', 'use App\\Policies\\UserPolicy;', '    public function policy(UserPolicy $policy): void {}'),
        'app/Models/SkeletonGateModel.php' => $class('App\\Models', 'SkeletonGateModel', 'use Illuminate\\Support\\Facades\\Gate;', '    public function allowed(): bool { return Gate::allows(\'view\'); }'),
        'app/Policies/SkeletonPolicy.php' => $class('App\\Policies', 'SkeletonPolicy', 'use App\\Actions\\Users\\SkeletonAction;', '    public function run(SkeletonAction $action): void {}'),
        'app/Actions/Skeleton/SkeletonRequestAction.php' => $class('App\\Actions\\Skeleton', 'SkeletonRequestAction', 'use Illuminate\\Http\\Request;', '    public function handle(Request $request): void {}'),
        'app/Actions/Skeleton/SkeletonHelperAction.php' => $class('App\\Actions\\Skeleton', 'SkeletonHelperAction', '', '    public function handle(): mixed { return auth()->id(); }'),
        'app/Http/Resources/SkeletonResource.php' => $class('App\\Http\\Resources', 'SkeletonResource', 'use App\\Actions\\Users\\SkeletonAction;', '    public function run(SkeletonAction $action): void {}'),
        'app/Http/Requests/SkeletonRequest.php' => $class('App\\Http\\Requests', 'SkeletonRequest', 'use App\\Actions\\Users\\SkeletonAction;', '    public function run(SkeletonAction $action): void {}'),
        'app/Notifications/SkeletonNotification.php' => $class('App\\Notifications', 'SkeletonNotification', 'use App\\Http\\Resources\\UserResource;', '    public function data(UserResource $resource): void {}'),
        'app/Http/Controllers/SkeletonDbController.php' => $class('App\\Http\\Controllers', 'SkeletonDbController', 'use Illuminate\\Support\\Facades\\DB;', '    public function index(): int { return DB::table(\'users\')->count(); }'),
        'app/Http/Controllers/SkeletonIntegrationController.php' => $class('App\\Http\\Controllers', 'SkeletonIntegrationController', 'use App\\Integrations\\SkeletonService;', '    public function index(SkeletonService $service): void {}'),
        'app/Actions/Skeleton/SkeletonHttpAction.php' => $class('App\\Actions\\Skeleton', 'SkeletonHttpAction', 'use Illuminate\\Support\\Facades\\Http;', '    public function handle(): mixed { return Http::get(\'https://example.com\'); }'),
        'app/Console/SkeletonSeederCommand.php' => $class('App\\Console', 'SkeletonSeederCommand', 'use Database\\Seeders\\DatabaseSeeder;', '    public function handle(DatabaseSeeder $seeder): void {}'),
        'app/Actions/Skeleton/SkeletonFactoryAction.php' => $class('App\\Actions\\Skeleton', 'SkeletonFactoryAction', 'use Database\\Factories\\UserFactory;', '    public function handle(UserFactory $factory): void {}'),
        'app/Support/SkeletonTestSupport.php' => $class('App\\Support', 'SkeletonTestSupport', 'use Tests\\TestCase;', '    public function run(TestCase $test): void {}'),
    ];
}

test('PHPStan keeps the layers of principle 2: each forbidden use in a fixture is rejected', function (string $file, string $forbidden) {
    expect(implode("\n", skeletonPhpstanErrors()[$file] ?? []))->toContain($forbidden);
})->with([
    'enums use no models' => ['app/Enums/SkeletonKind.php', 'App\\Models\\User is forbidden'],
    'enums use no request' => ['app/Enums/SkeletonRequestKind.php', 'Illuminate\\Http\\Request is forbidden'],
    'models use no policies' => ['app/Models/SkeletonPolicyModel.php', 'App\\Policies\\UserPolicy is forbidden'],
    'models use no Gate' => ['app/Models/SkeletonGateModel.php', 'Illuminate\\Support\\Facades\\Gate is forbidden'],
    'policies use no actions' => ['app/Policies/SkeletonPolicy.php', 'App\\Actions\\Users\\SkeletonAction is forbidden'],
    'actions use no request classes' => ['app/Actions/Skeleton/SkeletonRequestAction.php', 'Illuminate\\Http\\Request is forbidden'],
    'actions use no request helpers' => ['app/Actions/Skeleton/SkeletonHelperAction.php', 'auth() is forbidden'],
    'resources call no actions' => ['app/Http/Resources/SkeletonResource.php', 'App\\Actions\\Users\\SkeletonAction is forbidden'],
    'form requests call no actions' => ['app/Http/Requests/SkeletonRequest.php', 'App\\Actions\\Users\\SkeletonAction is forbidden'],
    'notifications use no http' => ['app/Notifications/SkeletonNotification.php', 'App\\Http\\Resources\\UserResource is forbidden'],
    'controllers use no DB facade' => ['app/Http/Controllers/SkeletonDbController.php', 'Illuminate\\Support\\Facades\\DB is forbidden'],
    'integrations only from actions' => ['app/Http/Controllers/SkeletonIntegrationController.php', 'App\\Integrations\\SkeletonService is forbidden'],
    'the http client only in integrations' => ['app/Actions/Skeleton/SkeletonHttpAction.php', 'Illuminate\\Support\\Facades\\Http is forbidden'],
    'the app uses no seeders' => ['app/Console/SkeletonSeederCommand.php', 'Database\\Seeders\\DatabaseSeeder is forbidden'],
    'factories only in models' => ['app/Actions/Skeleton/SkeletonFactoryAction.php', 'Database\\Factories\\UserFactory is forbidden'],
    'the app uses no test code' => ['app/Support/SkeletonTestSupport.php', 'Tests\\TestCase is forbidden'],
]);

/**
 * Runs oxlint once, with the project's vite.config.ts, over a fixture that
 * breaks the component boundaries and makes an import cycle.
 *
 * @return array<string, string> fixture path => everything oxlint said about it
 */
function skeletonOxlintErrors(): array
{
    static $errors = null;

    if ($errors !== null) {
        return $errors;
    }

    $root = skeletonFixture([
        'resources/js/components/alpha/alpha-card.tsx' => "import { BetaCard } from '../beta/beta-card';\n\nexport const AlphaCard = BetaCard;\n",
        'resources/js/components/beta/beta-card.tsx' => "export const BetaCard = 'beta';\n",
        'resources/js/components/beta/beta-page-link.tsx' => "import Home from '@/pages/home/index';\n\nexport const BetaPageLink = Home;\n",
        'resources/js/components/shared/shared-card.tsx' => "import { BetaCard } from '@/components/beta/beta-card';\n\nexport const SharedCard = BetaCard;\n",
        'resources/js/lib/first.ts' => "import { second } from './second';\n\nexport const first = (): unknown => second;\n",
        'resources/js/lib/second.ts' => "import { first } from './first';\n\nexport const second = (): unknown => first;\n",
        'resources/js/lib/title.ts' => "import { t } from '@/lib/i18n';\n\nexport const title = t('Dashboard');\n",
        'resources/js/lib/nav-items.ts' => "import { tChoice } from '@/lib/i18n';\n\nexport const items = [{ title: tChoice('{1} :count task|[2,*] :count tasks', 2) }];\n",
        'resources/js/lib/labels.ts' => "import { t as translate } from '@/lib/i18n';\n\nexport class Labels {\n    save = translate('Save');\n}\n",
        'resources/js/components/beta/beta-title.tsx' => "import { t } from '@/lib/i18n';\n\nexport function BetaTitle() {\n    return t('Dashboard');\n}\n\nexport function marker() {\n    debugger;\n}\n",
        'resources/js/lib/marked.ts' => "import { msg } from '@/lib/i18n';\n\nexport const marked = [{ title: msg('Dashboard') }];\n\nexport function marker() {\n    debugger;\n}\n",
        'resources/js/lib/later.ts' => "import { t } from '@/lib/i18n';\n\nexport const label = () => t('Save');\nexport const items = [{ title: function () { return t('Save'); } }];\n\nexport function marker() {\n    debugger;\n}\n",
        'resources/js/lib/own-t.ts' => "import { t } from '@/lib/format';\n\nexport const total = t('Save');\n\nexport function marker() {\n    debugger;\n}\n",
    ]);
    $project = dirname(__DIR__, 3);

    $process = new Process(["{$project}/node_modules/.bin/vp", 'lint', '--format', 'unix', "{$root}/resources/js"], $project, null, null, 300);
    $process->run();

    $errors = [];

    foreach (explode("\n", $process->getOutput().$process->getErrorOutput()) as $line) {
        if (preg_match('#'.preg_quote($root, '#').'/(\S+?):\d+:\d+:#', $line, $match) === 1) {
            $errors[$match[1]] = ($errors[$match[1]] ?? '').$line."\n";
        }
    }

    // The fixture breaks rules on purpose: oxlint must have run and failed on it
    // (exit 1, errors reported), so a file without a line was linted and is clean.
    if ($process->getExitCode() !== 1 || $errors === []) {
        throw new RuntimeException("oxlint did not lint the fixture (exit {$process->getExitCode()}):\n{$process->getOutput()}{$process->getErrorOutput()}");
    }

    return $errors;
}

test('oxlint keeps the component boundaries and rejects import cycles', function (string $file, string $rule) {
    expect(skeletonOxlintErrors()[$file] ?? '')->toContain($rule);
})->with([
    'a domain imports another domain' => ['resources/js/components/alpha/alpha-card.tsx', 'ezdev(component-boundaries)'],
    'a component imports a page' => ['resources/js/components/beta/beta-page-link.tsx', 'ezdev(component-boundaries)'],
    'shared imports a domain' => ['resources/js/components/shared/shared-card.tsx', 'ezdev(component-boundaries)'],
    'an import cycle' => ['resources/js/lib/first.ts', 'import(no-cycle)'],
    't() at module level' => ['resources/js/lib/title.ts', 'ezdev(no-module-level-t)'],
    'tChoice() in a module-level array' => ['resources/js/lib/nav-items.ts', 'ezdev(no-module-level-t)'],
    't() in a class field' => ['resources/js/lib/labels.ts', 'ezdev(no-module-level-t)'],
]);

test('oxlint lets t() run inside a function and msg() mark a sentence at module level', function (string $file) {
    // Each clean file also holds a `debugger` on purpose: its error proves oxlint linted the file.
    expect(skeletonOxlintErrors()[$file] ?? '')->toContain('no-debugger')->not->toContain('no-module-level-t');
})->with([
    't() inside a component' => ['resources/js/components/beta/beta-title.tsx'],
    'msg() at module level' => ['resources/js/lib/marked.ts'],
    't() inside an arrow function and a function expression' => ['resources/js/lib/later.ts'],
    'a t() imported from another module' => ['resources/js/lib/own-t.ts'],
]);

test('the structure checks reject a file in the wrong place', function (array $files, Closure $check, string $found) {
    $root = skeletonFixture($files);

    expect(implode("\n", ProjectFiles::at($root, $check)))->toContain($found);
})->with([
    'a new top-level folder in app/' => [['app/Services/Billing.php' => "<?php\n"], fn () => PlacementChecks::appFolders(), 'app/Services'],
    'a page outside a domain folder' => [['resources/js/pages/home.tsx' => "export default {};\n"], fn () => PlacementChecks::pagesInDomainFolders(), 'resources/js/pages/home.tsx'],
    'a new component at the components root' => [['resources/js/components/widget.tsx' => "export {};\n"], fn () => PlacementChecks::componentsRoot(), 'resources/js/components/widget.tsx'],
    'components/ui importing domain code' => [['resources/js/components/ui/button.tsx' => "import { Task } from '@/components/tasks/task';\n"], fn () => PlacementChecks::uiImportsNoDomainCode(), 'resources/js/components/ui/button.tsx'],
    'JavaScript in resources/js' => [['resources/js/lib/legacy.js' => "export {};\n"], fn () => PlacementChecks::typeScriptOnly(), 'resources/js/lib/legacy.js'],
    'a new folder in resources/js' => [['resources/js/services/api.ts' => "export {};\n"], fn () => PlacementChecks::resourcesJsFolders(), 'resources/js/services'],
    'a non-Markdown file in docs/' => [['docs/notes.php' => "<?php\n"], fn () => PlacementChecks::docsMarkdownOnly(), 'docs/notes.php'],
    'PHP in public/' => [['public/tool.php' => "<?php\n"], fn () => PlacementChecks::publicNoPhp(), 'public/tool.php'],
    'an import that leaves resources/js' => [['resources/js/lib/outside.ts' => "import x from '../../../app/x';\n\nexport default x;\n"], fn () => PlacementChecks::importsStayInside(), 'resources/js/lib/outside.ts'],
    'PHP required from a non-PHP file' => [['app/Support/Loader.php' => "<?php\n\nrequire __DIR__.'/code.txt';\n"], fn () => PlacementChecks::requiresOnlyPhp(), 'app/Support/Loader.php'],
    'PHP in a Blade view' => [['resources/views/app.blade.php' => "@php \$x = 1; @endphp\n"], fn () => PlacementChecks::bladeNoPhp(), 'resources/views/app.blade.php'],
]);

test('the design document checks reject a broken spec', function (array $files, Closure $check, string $found) {
    $root = skeletonFixture(['DESIGN.md' => "# Design\n\n- docs/design/patterns/list.md\n"] + $files);

    expect(implode("\n", ProjectFiles::at($root, $check)))->toContain($found);
})->with([
    'a pattern file missing from the index' => [['docs/design/patterns/wizard.md' => "# Wizard\n\n## Demo\n"], fn () => DesignDocChecks::patternIndex(), 'wizard.md: not in the DESIGN.md index'],
    'a pattern file without its Sample or Demo' => [['docs/design/patterns/list.md' => "# List\n"], fn () => DesignDocChecks::patternIndex(), 'list.md: no Sample or Demo section'],
    'a named file that does not exist' => [['docs/design/patterns/list.md' => "# List\n\n## Demo\n\n`resources/js/pages/style/missing.tsx`\n"], fn () => DesignDocChecks::patternFiles(), 'list.md: resources/js/pages/style/missing.tsx'],
    'a pixel value' => [['docs/design/foundations.md' => "A card has 24px of padding.\n"], fn () => DesignDocChecks::designValues(), 'foundations.md:1: 24px'],
    'a Tailwind size class' => [['docs/design/patterns/list.md' => "# List\n\n## Demo\n\nUse `p-6` for the card.\n"], fn () => DesignDocChecks::designValues(), 'list.md:5: p-6'],
]);

test('the structure checks reject a symlink', function () {
    $root = skeletonFixture(['app/Support/.keep-folder' => '']);
    symlink(sys_get_temp_dir(), "{$root}/app/Support/elsewhere");

    expect(implode("\n", ProjectFiles::at($root, fn () => PlacementChecks::noSymlinks())))->toContain('app/Support/elsewhere');
});

test('the structure checks reject a silenced check', function (string $path, string $content) {
    $root = skeletonFixture([$path => $content]);

    expect(implode("\n", ProjectFiles::at($root, fn () => CheckSettings::noSilencers())))->toContain($path);
})->with([
    'a PHPStan ignore' => ['app/Support/Quiet.php', "<?php\n\n// @phpstan-".'ignore'."-next-line\n"],
    'an oxlint disable' => ['resources/js/lib/quiet.ts', '// oxlint-'."disable\nexport {};\n"],
    'a TypeScript ignore' => ['resources/js/lib/loose.ts', '// @ts-'."ignore\nexport {};\n"],
    'a skipped Pest test' => ['tests/Feature/QuietTest.php', "<?php\n\ntest('x', function () {})->"."skip();\n"],
    'a skipped Vitest test' => ['tests/js/quiet.test.ts', 'test.'."skip('x', () => {});\n"],
    'a skip in the base test case' => ['tests/TestCase.php', "<?php\n\nnamespace Tests;\n\nabstract class TestCase\n{\n    protected function quiet(): void\n    {\n        \$this->markTest"."Skipped('off');\n    }\n}\n"],
    'a PHPStan baseline' => ['phpstan.neon', "parameters:\n    ignore"."Errors:\n        - '#x#'\n"],
]);

/**
 * A fixture holding a copy of the check settings: the configs, the oxlint
 * plugin and a link to the real vendor/ so PHPStan can read phpstan.neon.
 */
function skeletonSettingsFixture(): string
{
    $root = skeletonFixture();
    workspaceCopy($root, ['phpstan.neon', 'vite.config.ts', 'lint/oxlint/ezdev.js', 'tsconfig.json', 'phpunit.xml', 'composer.json', 'package.json', '.npmrc']);

    foreach (ProjectFiles::in(['lint/phpstan']) as $path) {
        workspaceCopy($root, [$path]);
    }

    symlink(dirname(__DIR__, 3).'/vendor', "{$root}/vendor");

    return $root;
}

function skeletonEdit(string $root, string $path, string $search, string $replace): void
{
    $content = file_get_contents("{$root}/{$path}") ?: throw new RuntimeException("no {$path} to edit");
    expect($content)->toContain($search);
    workspaceWrite($root, $path, str_replace($search, $replace, $content));
}

test('the copied settings pass, so each failure below comes from its one change', function () {
    $root = skeletonSettingsFixture();

    expect(ProjectFiles::at($root, fn () => [
        ...CheckSettings::phpstan(),
        ...CheckSettings::oxlint(),
        ...CheckSettings::typeScript(),
        ...CheckSettings::pestSuites(),
        ...CheckSettings::composerScripts(),
        ...CheckSettings::noOtherLintConfig(),
    ]))->toBe([]);
});

test('the settings checks reject a check that is switched off or turned down', function (string $path, string $search, string $replace, Closure $check, string $found) {
    $root = skeletonSettingsFixture();
    skeletonEdit($root, $path, $search, $replace);

    expect(implode("\n", ProjectFiles::at($root, $check)))->toContain($found);
})->with([
    'a lower PHPStan level' => ['phpstan.neon', 'level: 7', 'level: 5', fn () => CheckSettings::phpstan(), 'level'],
    'a layer rule removed' => ['phpstan.neon', "namespace:\n                - 'Illuminate\\Support\\Facades\\DB'", "namespace:\n                - 'Illuminate\\Support\\Facades\\Schema'", fn () => CheckSettings::phpstan(), 'Illuminate\\Support\\Facades\\DB'],
    'a layer rule widened' => ['phpstan.neon', "allowIn:\n                - app/Integrations/*\n", "allowIn:\n                - app/Integrations/*\n                - app/Http/*\n", fn () => CheckSettings::phpstan(), 'Illuminate\\Support\\Facades\\Http'],
    'a request helper allowed again' => ['phpstan.neon', "                - 'auth()'\n", '', fn () => CheckSettings::phpstan(), 'auth()'],
    'the component boundaries off' => ['vite.config.ts', "'ezdev/component-boundaries': 'error'", "'ezdev/component-boundaries': 'off'", fn () => CheckSettings::oxlint(), 'ezdev/component-boundaries'],
    'import cycles allowed' => ['vite.config.ts', "'import/no-cycle': 'error'", "'import/no-cycle': 'warn'", fn () => CheckSettings::oxlint(), 'import/no-cycle'],
    'loose TypeScript' => ['tsconfig.json', '"strict": true', '"strict": false', fn () => CheckSettings::typeScript(), 'strict'],
    'skipped tests allowed' => ['phpunit.xml', 'failOnSkipped="true"', 'failOnSkipped="false"', fn () => CheckSettings::pestSuites(), 'failOnSkipped'],
    'a step left out of composer check' => ['composer.json', '"npm run types:check"', '"echo skipped"', fn () => CheckSettings::composerScripts(), 'check:static'],
]);

test('the settings checks reject another lint config', function () {
    $root = skeletonSettingsFixture();
    workspaceWrite($root, '.oxlintrc.json', "{}\n");

    expect(implode("\n", ProjectFiles::at($root, fn () => CheckSettings::noOtherLintConfig())))->toContain('.oxlintrc.json');
});

test('PHPStan must read every folder composer autoloads', function () {
    $root = skeletonSettingsFixture();
    skeletonEdit($root, 'composer.json', '"App\\\\": "app/",', '"App\\\\": "app/",'."\n".'            "Extra\\\\": "extra/",');

    expect(implode("\n", ProjectFiles::at($root, fn () => CheckSettings::phpstan())))->toContain('extra');
});
