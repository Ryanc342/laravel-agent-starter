<?php

use Symfony\Component\Process\Process;
use Tests\Structure\CheckSettings;
use Tests\Structure\PlacementChecks;
use Tests\Structure\ProjectFiles;

require_once dirname(__DIR__).'/Support/Workspace.php';

/*
| The template keeps its machine rules (the `machine` rules of the skeleton's
| cards, .claude/rules/skeleton/: the PHPStan layer rules, component boundaries and no import cycles, the folders of app/,
| where pages and components live, code lives only where the checks read it,
| no check is silenced, the checks stay as set, the style page shows every
| shared component, only SubmitButton sends a form). Each test plants code that
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
        'app/Http/Resources/SkeletonResource.php' => $class('App\\Http\\Resources', 'SkeletonResource', "use App\\Actions\\Notifications\\MarkNotificationRead;\nuse App\\Models\\User;", '    public function go(MarkNotificationRead $operation, User $user): mixed { return $operation->run($user); }'),
        'app/Http/Requests/SkeletonRequest.php' => $class('App\\Http\\Requests', 'SkeletonRequest', 'use App\\Actions\\Users\\SkeletonAction;', '    public function run(SkeletonAction $action): void {}'),
        'app/Notifications/SkeletonNotification.php' => $class('App\\Notifications', 'SkeletonNotification', 'use App\\Http\\Resources\\UserResource;', '    public function data(UserResource $resource): void {}'),
        'app/Http/Controllers/SkeletonDbController.php' => $class('App\\Http\\Controllers', 'SkeletonDbController', 'use Illuminate\\Support\\Facades\\DB;', '    public function index(): int { return DB::table(\'users\')->count(); }'),
        'app/Http/Controllers/SkeletonOutsideController.php' => $class('App\\Http\\Controllers', 'SkeletonOutsideController', 'use App\\Actions\\OutsideCall;', '    public function index(OutsideCall $call): void {}'),
        'app/Actions/Skeleton/SkeletonHttpAction.php' => $class('App\\Actions\\Skeleton', 'SkeletonHttpAction', 'use Illuminate\\Support\\Facades\\Http;', '    public function handle(): mixed { return Http::get(\'https://example.com\'); }'),
        'app/Console/SkeletonSeederCommand.php' => $class('App\\Console', 'SkeletonSeederCommand', 'use Database\\Seeders\\DatabaseSeeder;', '    public function handle(DatabaseSeeder $seeder): void {}'),
        'app/Actions/Skeleton/SkeletonFactoryAction.php' => $class('App\\Actions\\Skeleton', 'SkeletonFactoryAction', 'use Database\\Factories\\UserFactory;', '    public function handle(UserFactory $factory): void {}'),
        'app/Support/SkeletonTestSupport.php' => $class('App\\Support', 'SkeletonTestSupport', 'use Tests\\TestCase;', '    public function run(TestCase $test): void {}'),
        'app/Actions/Skeleton/SkeletonFactoryCall.php' => $class('App\\Actions\\Skeleton', 'SkeletonFactoryCall', 'use App\\Models\\User;', '    public function handle(): void { User::factory()->create(); }'),
        'app/Http/Controllers/SkeletonConnectionController.php' => $class('App\\Http\\Controllers', 'SkeletonConnectionController', 'use Illuminate\\Database\\ConnectionInterface;', '    public function index(ConnectionInterface $db): int { return $db->table(\'users\')->delete(); }'),
        'app/Http/Controllers/SkeletonGetConnectionController.php' => $class('App\\Http\\Controllers', 'SkeletonGetConnectionController', 'use App\\Models\\User;', '    public function index(User $user): int { return $user->getConnection()->table(\'users\')->delete(); }'),
        'app/Models/SkeletonCanModel.php' => $class('App\\Models', 'SkeletonCanModel', '', '    public function allowedFor(User $user): bool { return $user->can(\'view\', $user); }'),
        'app/Enums/SkeletonGateKind.php' => "<?php\n\nnamespace App\\Enums;\n\nuse Illuminate\\Support\\Facades\\Gate;\n\nenum SkeletonGateKind: string\n{\n    case A = 'a';\n\n    public function allowed(): bool\n    {\n        return Gate::allows('view');\n    }\n}\n",
        'app/Policies/SkeletonGuardPolicy.php' => $class('App\\Policies', 'SkeletonGuardPolicy', 'use Illuminate\\Contracts\\Auth\\Guard;', '    public function __construct(private Guard $guard) {}'),
        'app/Http/Controllers/SkeletonMailController.php' => $class('App\\Http\\Controllers', 'SkeletonMailController', 'use Illuminate\\Support\\Facades\\Mail;', '    public function index(): void { Mail::raw(\'hello\', function ($message): void {}); }'),
        'app/Http/Controllers/SkeletonNotifyController.php' => $class('App\\Http\\Controllers', 'SkeletonNotifyController', "use App\\Models\\User;\nuse App\\Notifications\\PasswordChanged;", '    public function index(User $user): void { $user->notify(new PasswordChanged); }'),
        ...skeletonPhpstanCleanFiles(),
    ];
}

/**
 * The same rules met the right way: each file uses what a rule allows where
 * it allows it, so PHPStan must find nothing in them.
 *
 * @return array<string, string>
 */
function skeletonPhpstanCleanFiles(): array
{
    $class = fn (string $namespace, string $name, string $use, string $body): string => "<?php\n\nnamespace {$namespace};\n\n{$use}\n\nfinal class {$name}\n{\n{$body}\n}\n";

    return [
        'app/Actions/Skeleton/SkeletonMailAction.php' => $class('App\\Actions\\Skeleton', 'SkeletonMailAction', 'use Illuminate\\Support\\Facades\\Mail;', '    public function handle(): void { Mail::raw(\'hello\', function ($message): void {}); }'),
        'app/Actions/Skeleton/SkeletonNotifyAction.php' => $class('App\\Actions\\Skeleton', 'SkeletonNotifyAction', "use App\\Models\\User;\nuse App\\Notifications\\PasswordChanged;", '    public function handle(User $user): void { $user->notify(new PasswordChanged); }'),
        'app/Actions/Skeleton/SkeletonOutsideAction.php' => $class('App\\Actions\\Skeleton', 'SkeletonOutsideAction', 'use App\\Actions\\OutsideCall;', '    public function __construct(private OutsideCall $call) {}'),
        'app/Actions/Skeleton/SkeletonDbAction.php' => $class('App\\Actions\\Skeleton', 'SkeletonDbAction', 'use Illuminate\\Support\\Facades\\DB;', '    public function handle(): int { return DB::table(\'users\')->count(); }'),
        'app/Http/Controllers/SkeletonGateController.php' => $class('App\\Http\\Controllers', 'SkeletonGateController', "use App\\Models\\User;\nuse Illuminate\\Support\\Facades\\Gate;", '    public function index(): void { Gate::authorize(\'register\', User::class); }'),
        'app/Policies/SkeletonModelPolicy.php' => $class('App\\Policies', 'SkeletonModelPolicy', 'use App\\Models\\User;', '    public function view(User $user): bool { return $user->exists; }'),
    ];
}

test('PHPStan blocks nothing that is allowed: the clean files of the fixture have no error', function () {
    expect(array_intersect_key(skeletonPhpstanErrors(), skeletonPhpstanCleanFiles()))->toBe([]);
});

test('PHPStan keeps the skeleton\'s layer rules: each forbidden use in a fixture is rejected', function (string $file, string $forbidden) {
    expect(implode("\n", skeletonPhpstanErrors()[$file] ?? []))->toContain($forbidden);
})->with([
    'enums use no models' => ['app/Enums/SkeletonKind.php', 'App\\Models\\User is forbidden'],
    'enums use no request' => ['app/Enums/SkeletonRequestKind.php', 'Illuminate\\Http\\Request is forbidden'],
    'models use no policies' => ['app/Models/SkeletonPolicyModel.php', 'App\\Policies\\UserPolicy is forbidden'],
    'models use no Gate' => ['app/Models/SkeletonGateModel.php', 'Illuminate\\Support\\Facades\\Gate is forbidden'],
    'policies use no actions' => ['app/Policies/SkeletonPolicy.php', 'App\\Actions\\Users\\SkeletonAction is forbidden'],
    'actions use no request classes' => ['app/Actions/Skeleton/SkeletonRequestAction.php', 'Illuminate\\Http\\Request is forbidden'],
    'actions use no request helpers' => ['app/Actions/Skeleton/SkeletonHelperAction.php', 'auth() is forbidden'],
    'resources run no operation' => ['app/Http/Resources/SkeletonResource.php', 'App\\Actions\\Operation::run()'],
    'form requests call no actions' => ['app/Http/Requests/SkeletonRequest.php', 'App\\Actions\\Users\\SkeletonAction is forbidden'],
    'notifications use no http' => ['app/Notifications/SkeletonNotification.php', 'App\\Http\\Resources\\UserResource is forbidden'],
    'controllers use no DB facade' => ['app/Http/Controllers/SkeletonDbController.php', 'Illuminate\\Support\\Facades\\DB is forbidden'],
    'the outside-service part only from actions' => ['app/Http/Controllers/SkeletonOutsideController.php', 'App\\Actions\\OutsideCall is forbidden'],
    'the http client only in the outside-service part' => ['app/Actions/Skeleton/SkeletonHttpAction.php', 'Illuminate\\Support\\Facades\\Http is forbidden'],
    'the app uses no seeders' => ['app/Console/SkeletonSeederCommand.php', 'Database\\Seeders\\DatabaseSeeder is forbidden'],
    'factories only in models' => ['app/Actions/Skeleton/SkeletonFactoryAction.php', 'Database\\Factories\\UserFactory is forbidden'],
    'the app uses no test code' => ['app/Support/SkeletonTestSupport.php', 'Tests\\TestCase is forbidden'],
    'the app makes no record with a factory' => ['app/Actions/Skeleton/SkeletonFactoryCall.php', 'HasFactory::factory()'],
    'controllers take no database connection' => ['app/Http/Controllers/SkeletonConnectionController.php', 'Illuminate\\Database\\ConnectionInterface is forbidden'],
    'controllers ask no model for its connection' => ['app/Http/Controllers/SkeletonGetConnectionController.php', 'Model::getConnection()'],
    'models check no permission through the user' => ['app/Models/SkeletonCanModel.php', 'Authorizable::can()'],
    'enums use no Gate' => ['app/Enums/SkeletonGateKind.php', 'Illuminate\\Support\\Facades\\Gate is forbidden'],
    'policies take no guard' => ['app/Policies/SkeletonGuardPolicy.php', 'Illuminate\\Contracts\\Auth\\Guard is forbidden'],
    'only actions send mail' => ['app/Http/Controllers/SkeletonMailController.php', 'Illuminate\\Support\\Facades\\Mail is forbidden'],
    'only actions send notifications' => ['app/Http/Controllers/SkeletonNotifyController.php', 'notify()'],
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
        'resources/js/components/alpha/alpha-submit.tsx' => "export const AlphaSubmit = () => <button type=\"submit\">Save</button>;\n",
        'resources/js/components/alpha/alpha-plain.tsx' => "export const AlphaPlain = () => <button>Save</button>;\n",
        ...skeletonOxlintCleanFiles(),
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

/**
 * The same rules met the right way: a domain uses a shared component, a page
 * uses a domain component, SubmitButton says type="submit" and a plain button
 * says its type. oxlint must find nothing in them.
 *
 * @return array<string, string>
 */
function skeletonOxlintCleanFiles(): array
{
    return [
        'resources/js/components/shared/shared-badge.tsx' => "export const SharedBadge = 'badge';\n",
        'resources/js/components/alpha/alpha-badge.tsx' => "import { SharedBadge } from '@/components/shared/shared-badge';\n\nexport const AlphaBadge = SharedBadge;\n",
        'resources/js/pages/home/index.tsx' => "import { AlphaBadge } from '@/components/alpha/alpha-badge';\n\nexport default function Home() {\n    return <p>{AlphaBadge}</p>;\n}\n",
        'resources/js/components/shared/submit-button.tsx' => "export default function SubmitButton() {\n    return <button type=\"submit\">Save</button>;\n}\n",
        'resources/js/components/alpha/alpha-toggle.tsx' => "export const AlphaToggle = () => <button type=\"button\">Show</button>;\n",
    ];
}

test('oxlint blocks nothing that is allowed: the clean files of the fixture have no error', function () {
    expect(array_intersect_key(skeletonOxlintErrors(), skeletonOxlintCleanFiles()))->toBe([]);
});

test('oxlint keeps the component boundaries, rejects import cycles and lets only SubmitButton send a form', function (string $file, string $rule) {
    expect(skeletonOxlintErrors()[$file] ?? '')->toContain($rule);
})->with([
    'a domain imports another domain' => ['resources/js/components/alpha/alpha-card.tsx', 'ezdev(component-boundaries)'],
    'a component imports a page' => ['resources/js/components/beta/beta-page-link.tsx', 'ezdev(component-boundaries)'],
    'shared imports a domain' => ['resources/js/components/shared/shared-card.tsx', 'ezdev(component-boundaries)'],
    'an import cycle' => ['resources/js/lib/first.ts', 'import(no-cycle)'],
    'a button that says type="submit"' => ['resources/js/components/alpha/alpha-submit.tsx', 'ezdev(submit-button)'],
    'a plain button without a type' => ['resources/js/components/alpha/alpha-plain.tsx', 'ezdev(submit-button)'],
]);

/**
 * A fixture where every structure check has something to read and everything
 * sits where it may: an operation in its domain folder, a page in its domain
 * folder, a shared component the style page shows, a component of the starter
 * kit at the components root, components/ui importing a helper, Markdown in
 * docs/, Laravel's front controller in public/, a PHP file required by PHP.
 */
function skeletonStructureCleanFixture(): string
{
    return skeletonFixture([
        'app/Actions/Users/SkeletonOperation.php' => "<?php\n\nnamespace App\\Actions\\Users;\n\nuse App\\Actions\\Operation;\n\nfinal class SkeletonOperation extends Operation\n{\n}\n",
        'app/Models/SkeletonModel.php' => "<?php\n\nnamespace App\\Models;\n\nfinal class SkeletonModel\n{\n}\n",
        'resources/js/app.tsx' => "export {};\n",
        'resources/js/pages/home/index.tsx' => "export default function Home() {\n    return null;\n}\n",
        'resources/js/components/heading.tsx' => "export default function Heading() {\n    return null;\n}\n",
        'resources/js/components/shared/badge-row.tsx' => "export default function BadgeRow() {\n    return null;\n}\n",
        'resources/js/components/style/shared-demo.tsx' => "import BadgeRow from '@/components/shared/badge-row';\n\nexport default BadgeRow;\n",
        'resources/js/components/ui/button.tsx' => "import { cn } from '@/lib/utils';\n\nexport const Button = cn;\n",
        'resources/js/lib/utils.ts' => "export const cn = (): string => '';\n",
        'docs/notes.md' => "# Notes\n",
        'public/index.php' => "<?php\n",
        'routes/web.php' => "<?php\n\nrequire __DIR__.'/settings.php';\n",
        'routes/settings.php' => "<?php\n",
        'resources/views/app.blade.php' => "<div>{{ \$page['component'] }}</div>\n",
    ]);
}

test('the structure checks block nothing that is allowed: every check passes on the clean fixture', function () {
    $root = skeletonStructureCleanFixture();
    $found = [];

    foreach ((new ReflectionClass(PlacementChecks::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $found = [...$found, ...ProjectFiles::at($root, fn () => $method->invoke(null))];
    }

    expect($found)->toBe([]);
});

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
    'an action that is not an operation' => [['app/Actions/Users/PlainAction.php' => "<?php\n\nnamespace App\\Actions\\Users;\n\nfinal class PlainAction\n{\n}\n"], fn () => PlacementChecks::actionsAreOperations(), 'app/Actions/Users/PlainAction.php'],
    'PHP required from a non-PHP file' => [['app/Support/Loader.php' => "<?php\n\nrequire __DIR__.'/code.txt';\n"], fn () => PlacementChecks::requiresOnlyPhp(), 'app/Support/Loader.php'],
    'PHP in a Blade view' => [['resources/views/app.blade.php' => "@php \$x = 1; @endphp\n"], fn () => PlacementChecks::bladeNoPhp(), 'resources/views/app.blade.php'],
    'a shared component the style page does not show' => [['resources/js/components/shared/badge-row.tsx' => "export default function BadgeRow() {\n    return null;\n}\n", 'resources/js/components/style/shared-demo.tsx' => "export default function SharedDemo() {\n    return null;\n}\n"], fn () => PlacementChecks::styleShowsSharedComponents(), 'resources/js/components/shared/badge-row.tsx'],
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
    'a layer rule widened' => ['phpstan.neon', "allowIn:\n                - app/Actions/OutsideCall.php\n", "allowIn:\n                - app/Actions/OutsideCall.php\n                - app/Http/*\n", fn () => CheckSettings::phpstan(), 'Illuminate\\Support\\Facades\\Http'],
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
