<?php

use App\Console\Commands\InitializeProject;
use Dotenv\Dotenv;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * @param  list<string>  $arguments
 */
function installationCommandLine(array $arguments): string
{
    return (new SymfonyProcess($arguments))->getCommandLine();
}

beforeEach(function () {
    $this->originalBasePath = base_path();
    $this->workspace = sys_get_temp_dir().'/starter-initialize-'.Str::uuid();
    $this->project = $this->workspace.'/my-project';

    File::ensureDirectoryExists($this->project);
    File::put($this->project.'/.env.example', <<<'ENV'
APP_NAME=Laravel
APP_KEY=
# DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=root
DB_PASSWORD=
ENV);

    $kernel = app(Kernel::class);
    $kernel->bootstrap();
    $kernel->registerCommand(app(InitializeProject::class));
    app()->setBasePath($this->project)->useEnvironmentPath($this->project);

    config()->set('starter-kit.defer_initialization', false);
    config()->set('database.default', 'mysql');
    config()->set('database.connections.mysql', [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => '',
        'username' => 'root',
        'password' => '',
    ]);

    Process::fake([
        installationCommandLine([PHP_BINARY, 'artisan', 'migrate', '--force']) => Process::result(),
        installationCommandLine(['npm', 'install']) => Process::result(),
        installationCommandLine(['npm', 'run', 'build']) => Process::result(),
        installationCommandLine(['git', 'init', '--initial-branch=main']) => Process::result(),
    ]);
    Process::preventStrayProcesses();
});

afterEach(function () {
    app()->setBasePath($this->originalBasePath)->useEnvironmentPath($this->originalBasePath);
    File::deleteDirectory($this->workspace);
});

test('a fresh project gets its own mysql database, key, assets and git repository', function () {
    $this->artisan('starter:initialize')->assertSuccessful();

    $environment = Dotenv::createArrayBacked($this->project)->load();
    expect($environment['DB_DATABASE'])->toBe('my_project');
    expect($environment['APP_KEY'])->toStartWith('base64:');
    expect($environment)->not->toHaveKey('DB_CONNECTION');
    expect(File::get($this->project.'/.env'))->toContain('# DB_HOST=127.0.0.1');
    Process::assertRanTimes([PHP_BINARY, 'artisan', 'migrate', '--force'], 1);
    Process::assertRanTimes(['npm', 'install'], 1);
    Process::assertRanTimes(['npm', 'run', 'build'], 1);
    Process::assertRanTimes(['git', 'init', '--initial-branch=main'], 1);
});

test('existing database credentials and key survive initialization', function () {
    File::put($this->project.'/.env', <<<'ENV'
APP_NAME=Laravel
APP_KEY=base64:a2tra2tra2tra2tra2tra2tra2tra2tra2tra2tra2s=
# DB_HOST=127.0.0.1
DB_HOST=db.internal
DB_PORT=3310
DB_DATABASE=customer_portal
DB_USERNAME=portal_user
DB_PASSWORD='a "quote" $literal \ path #hash'
ENV);

    config()->set('database.connections.mysql', [
        'driver' => 'mysql',
        'host' => 'db.internal',
        'port' => '3310',
        'database' => 'customer_portal',
        'username' => 'portal_user',
        'password' => 'a "quote" $literal \\ path #hash',
    ]);

    $this->artisan('starter:initialize')->assertSuccessful();

    $environment = Dotenv::createArrayBacked($this->project)->load();
    expect($environment['APP_KEY'])->toBe('base64:a2tra2tra2tra2tra2tra2tra2tra2tra2tra2tra2s=');
    expect($environment['DB_DATABASE'])->toBe('customer_portal');
    expect($environment['DB_HOST'])->toBe('db.internal');
    expect($environment['DB_PORT'])->toBe('3310');
    expect($environment['DB_USERNAME'])->toBe('portal_user');
    expect($environment['DB_PASSWORD'])->toBe('a "quote" $literal \\ path #hash');
});

test('child commands receive the new database and generated key instead of boot-time values', function () {
    $this->artisan('starter:initialize')->assertSuccessful();

    $environment = Dotenv::createArrayBacked($this->project)->load();
    Process::assertRan(fn (PendingProcess $process): bool => $process->command === [PHP_BINARY, 'artisan', 'migrate', '--force']
        && ($process->environment['DB_DATABASE'] ?? null) === 'my_project'
        && ($process->environment['APP_KEY'] ?? null) === $environment['APP_KEY']);
});

test('replacing multiline credentials preserves the complete environment and special characters', function (string $password) {
    File::put($this->project.'/.env', "APP_KEY=\nDB_PASSWORD=\"old\npassword\"\nDB_DATABASE=laravel\nAPP_NAME=Preserved\n");
    config()->set('database.connections.mysql.password', $password);

    $this->artisan('starter:initialize')->assertSuccessful();

    $environment = Dotenv::createArrayBacked($this->project)->load();
    expect($environment['DB_PASSWORD'])->toBe($password);
    expect($environment['APP_NAME'])->toBe('Preserved');
})->with([
    'multiline' => ["new\npassword"],
    'unicode separators' => ["unicode\u{2028}\u{2029}password"],
    'control character' => ["control\x08password"],
]);

test('an explicit mysql url keeps its database even when it is named laravel', function () {
    $url = 'mysql://root:example@127.0.0.1:3306/laravel';
    File::put($this->project.'/.env', "APP_KEY=\nDB_URL={$url}\nDB_DATABASE=laravel\n");
    config()->set('database.connections.mysql.url', $url);

    $this->artisan('starter:initialize')->assertSuccessful();

    $environment = Dotenv::createArrayBacked($this->project)->load();
    expect($environment['DB_URL'])->toBe($url);
    expect($environment['DB_DATABASE'])->toBe('laravel');
    Process::assertRan(fn (PendingProcess $process): bool => $process->command === [PHP_BINARY, 'artisan', 'migrate', '--force']
        && ($process->environment['DB_DATABASE'] ?? null) === 'laravel');
});

test('an existing database named laravel is preserved when setup is repeated', function () {
    File::put($this->project.'/.env', "APP_KEY=existing-key\nDB_DATABASE=laravel\n");
    config()->set('database.connections.mysql.database', 'laravel');

    $this->artisan('starter:initialize')->assertSuccessful();

    $environment = Dotenv::createArrayBacked($this->project)->load();
    expect($environment['DB_DATABASE'])->toBe('laravel');
    expect($environment['APP_KEY'])->toBe('existing-key');
    Process::assertRan(fn (PendingProcess $process): bool => $process->command === [PHP_BINARY, 'artisan', 'migrate', '--force']
        && ($process->environment['DB_DATABASE'] ?? null) === 'laravel');
});

test('assignment-like text inside a multiline password is preserved', function () {
    $password = "old\nDB_HOST=inside_password\nend";
    File::put($this->project.'/.env', "APP_KEY=\nDB_PASSWORD=\"{$password}\"\nDB_HOST=db.internal\nDB_DATABASE=customer_portal\n");
    config()->set('database.connections.mysql.password', $password);
    config()->set('database.connections.mysql.host', 'db.internal');
    config()->set('database.connections.mysql.database', 'customer_portal');

    $this->artisan('starter:initialize')->assertSuccessful();

    $environment = Dotenv::createArrayBacked($this->project)->load();
    expect($environment['DB_PASSWORD'])->toBe($password);
    expect($environment['DB_HOST'])->toBe('db.internal');
});

test('credentials survive the laravel installer database rewrite after initialization', function () {
    $password = 'prefix DB_CONNECTION=inside_password';
    config()->set('database.connections.mysql.password', $password);

    $this->artisan('starter:initialize')->assertSuccessful();

    // Laravel Installer 5.28.1 and 5.32.0 apply this rewrite after the kit hook.
    $contents = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=sqlite', File::get($this->project.'/.env'));
    File::put($this->project.'/.env', $contents);
    expect(Dotenv::createArrayBacked($this->project)->load()['DB_PASSWORD'])->toBe($password);

    $this->artisan('starter:initialize')->assertSuccessful();

    expect(substr_count(File::get($this->project.'/.env'), 'LARAVEL_STARTER_LITERAL_EQUALS="="'))->toBe(1);
});

test('composer defers initialization while the laravel installer prepares the project', function () {
    config()->set('starter-kit.defer_initialization', true);

    $this->artisan('starter:initialize')->assertSuccessful();

    expect(File::exists($this->project.'/.env'))->toBeFalse();
    Process::assertNothingRan();
});

test('initializing an existing git repository does not create another repository', function () {
    File::ensureDirectoryExists($this->project.'/.git');

    $this->artisan('starter:initialize')->assertSuccessful();

    Process::assertNotRan(['git', 'init', '--initial-branch=main']);
});

test('a failed mysql migration stops before installing assets or initializing git', function () {
    Process::fake([
        installationCommandLine([PHP_BINARY, 'artisan', 'migrate', '--force']) => Process::result(errorOutput: 'MySQL connection refused.', exitCode: 1),
    ]);

    $this->artisan('starter:initialize')->assertFailed();

    Process::assertNotRan(['npm', 'install']);
    Process::assertNotRan(['npm', 'run', 'build']);
    Process::assertNotRan(['git', 'init', '--initial-branch=main']);
});
