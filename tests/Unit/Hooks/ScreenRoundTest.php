<?php

use Dotenv\Dotenv;
use Illuminate\Support\ConfigurationUrlParser;
use Symfony\Component\Process\Process;

require_once dirname(__DIR__, 3).'/scripts/screen-round.php';
require_once dirname(__DIR__, 2).'/Support/Workspace.php';

/*
| scripts/screen-round prepares a screen round of the final review
| (docs/workflow.md, step 4): one copy of the app for reviewer-opus, with its
| own database reset to the seeded state and a free port, answering.
| The project's own app and database are never touched, and it changes nothing
| unless the project's database server is a local development one.
*/

test('the guard lets only a local development database be reset', function (array $env, bool $allowed) {
    expect(screenRoundRefusal($env) === null)->toBe($allowed);
})->with([
    'local MySQL on 127.0.0.1' => [['APP_ENV' => 'local', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1'], true],
    'local MySQL on localhost' => [['APP_ENV' => 'local', 'DB_HOST' => 'localhost'], true],
    'local MySQL on ::1' => [['APP_ENV' => 'local', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => '::1'], true],
    'local MySQL without a host' => [['APP_ENV' => 'local', 'DB_CONNECTION' => 'mysql'], true],
    'local SQLite' => [['APP_ENV' => 'local', 'DB_CONNECTION' => 'sqlite', 'DB_HOST' => 'db.example.com'], true],
    'production on this machine' => [['APP_ENV' => 'production', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1'], false],
    'staging SQLite' => [['APP_ENV' => 'staging', 'DB_CONNECTION' => 'sqlite'], false],
    'no APP_ENV' => [['DB_CONNECTION' => 'sqlite'], false],
    'local with a database on another host' => [['APP_ENV' => 'local', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => 'db.example.com'], false],
    'local with a host that only starts like localhost' => [['APP_ENV' => 'local', 'DB_HOST' => 'localhost.example.com'], false],
    'local with a database URL on this machine' => [['APP_ENV' => 'local', 'DB_HOST' => 'db.example.com', 'DB_URL' => 'mysql://shop:secret@127.0.0.1:3306/shop'], true],
    'local with a database URL on another host' => [['APP_ENV' => 'local', 'DB_HOST' => '127.0.0.1', 'DB_URL' => 'mysql://shop:secret@db.example.com/shop'], false],
    'local with a database URL whose query names another host' => [['APP_ENV' => 'local', 'DB_URL' => 'mysql://root:@127.0.0.1:3306?host=db.example.com'], false],
]);

/**
 * A throwaway git repository holding the script, with the given .env.
 */
function screenRoundRepo(string $env): string
{
    $repo = workspaceFolder('screen-round').'/project';
    workspaceCopy($repo, ['scripts/screen-round', 'scripts/screen-round.php']);
    workspaceWrite($repo, '.env', $env);
    workspaceWrite($repo, '.gitignore', ".env\n");
    // The project's Composer autoloader, as a project after composer install has it.
    workspaceWrite($repo, 'vendor/autoload.php', "<?php\n\nrequire ".var_export(dirname(__DIR__, 3).'/vendor/autoload.php', true).";\n");
    workspaceGitInit($repo);

    return $repo;
}

/**
 * Everything the script could have changed: the files next to the project, the
 * worktrees, and the project's .env.
 *
 * @return array{list<string>, string, string}
 */
function screenRoundState(string $repo): array
{
    return [
        array_values(array_diff(scandir(dirname($repo)) ?: [], ['.', '..'])),
        (new Process(['git', 'worktree', 'list', '--porcelain'], $repo))->mustRun()->getOutput(),
        (string) file_get_contents("{$repo}/.env"),
    ];
}

test('a database that is not local, or an APP_ENV that is not local, changes nothing', function (string $env) {
    $repo = screenRoundRepo($env);
    $before = screenRoundState($repo);

    $process = new Process(['php', 'scripts/screen-round'], $repo);
    $process->run();

    expect($process->getExitCode())->toBe(1)
        ->and($process->getOutput().$process->getErrorOutput())->toContain('changed nothing')->toContain('report')->not->toContain('Ask the user')
        ->and(screenRoundState($repo))->toBe($before);
})->with([
    'production' => ["APP_ENV=production\nAPP_URL=http://127.0.0.1:1\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_DATABASE=shop\n"],
    'a remote database' => ["APP_ENV=local\nAPP_URL=http://127.0.0.1:1\nDB_CONNECTION=mysql\nDB_HOST=db.example.com\nDB_DATABASE=shop\n"],
]);

test('a project without its Composer packages changes nothing', function () {
    $repo = screenRoundRepo("APP_ENV=local\nAPP_URL=http://127.0.0.1:1\nDB_CONNECTION=sqlite\n");
    workspaceRemove("{$repo}/vendor/autoload.php");
    $before = screenRoundState($repo);

    $process = new Process(['php', 'scripts/screen-round'], $repo);
    $process->run();

    expect($process->getExitCode())->toBe(1)
        ->and($process->getOutput().$process->getErrorOutput())->toContain('Run composer install in the project first. Nothing was changed.')
        ->and(screenRoundState($repo))->toBe($before);
});

test('a copy gets its own database, port and session cookie', function () {
    $env = "APP_NAME=Shop\nQUEUE_CONNECTION=database\nAPP_ENV=local\nAPP_URL=http://localhost:8004\nSERVER_PORT=8004\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_DATABASE=shop_dev\nSESSION_DRIVER=database\n";
    $copy = screenRoundCopyEnv($env, '/work/shop-review-haiku', 200, '_review_haiku');

    expect(screenRoundParse($copy))->toMatchArray([
        'APP_NAME' => 'Shop',
        'APP_URL' => 'http://localhost:8204',
        'SERVER_PORT' => '8204',
        'DB_HOST' => '127.0.0.1',
        'DB_DATABASE' => 'shop_dev_review_haiku',
        'SESSION_DRIVER' => 'database',
        'SESSION_COOKIE' => 'shop_review_haiku_session',
        'QUEUE_CONNECTION' => 'sync',
    ])->and(screenRoundParse(screenRoundSetPort($copy, 8231)))->toMatchArray(['APP_URL' => 'http://localhost:8231', 'SERVER_PORT' => '8231']);
});

/**
 * The database connection Laravel builds from a .env: phpdotenv reads the file,
 * config/database.php takes the values, ConfigurationUrlParser applies DB_URL.
 *
 * @return array<string, mixed>
 */
function screenRoundLaravelDatabase(string $env, ?string $database = 'from env'): array
{
    $values = Dotenv::parse($env);

    return (new ConfigurationUrlParser)->parseConfiguration([
        'driver' => $values['DB_CONNECTION'] ?? 'mysql',
        'url' => $values['DB_URL'] ?? null,
        'host' => $values['DB_HOST'] ?? '127.0.0.1',
        'port' => $values['DB_PORT'] ?? '3306',
        'database' => $database === 'from env' ? ($values['DB_DATABASE'] ?? 'laravel') : $database,
        'username' => $values['DB_USERNAME'] ?? 'root',
        'password' => $values['DB_PASSWORD'] ?? '',
    ]);
}

test('a copy uses its own database however the project\'s .env names its own', function (string $database) {
    $env = "APP_ENV=local\nAPP_URL=http://localhost:8004\nDB_CONNECTION=mysql\n{$database}\n";
    $copy = screenRoundCopyEnv($env, '/work/shop-review-opus', 100, '_review_opus');

    expect(screenRoundLaravelDatabase($env)['database'])->toBe('shop_dev')
        ->and(screenRoundLaravelDatabase($copy))->toMatchArray(['driver' => 'mysql', 'host' => '127.0.0.1', 'database' => 'shop_dev_review_opus'])
        // migrate creates a missing database on a connection without one: nothing may bring one back.
        ->and(screenRoundLaravelDatabase($copy, null)['database'])->toBeNull();
})->with([
    'a plain value' => ['DB_DATABASE=shop_dev'],
    'a comment after the value' => ['DB_DATABASE=shop_dev # local'],
    'a quoted value and a comment' => ['DB_DATABASE="shop_dev" # local'],
    'export' => ['export DB_DATABASE=shop_dev'],
    'a URL with the database in its path' => ["DB_DATABASE=other\nDB_URL=\"mysql://shop:s%23cret@127.0.0.1:3306/shop_dev?charset=utf8mb4\""],
    'a URL with the database in its query' => ["DB_DATABASE=other\nexport DB_URL=mysql://root:@127.0.0.1:3306?database=shop_dev"],
]);

test('a copy whose .env would use any other database stops the round before anything is migrated', function (array $copyEnv) {
    $project = ['DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_DATABASE' => 'shop'];

    expect(fn () => screenRoundCheckDatabase($project, $copyEnv, '/work/shop-review-opus', '_review_opus'))->toThrow(RuntimeException::class, 'nothing was migrated');
})->with([
    'the project\'s database' => [['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'shop']],
    'the other seat\'s database' => [['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'shop_review_haiku']],
    'its own name on another server' => [['DB_CONNECTION' => 'mysql', 'DB_HOST' => 'db.example.com', 'DB_DATABASE' => 'shop_review_opus']],
    'a URL that wins over its own name' => [['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'shop_review_opus', 'DB_URL' => 'mysql://root:@127.0.0.1?database=shop']],
]);

test('a copy whose .env uses its own database passes the check', function () {
    expect(screenRoundCheckDatabase(['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'shop'], ['DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_DATABASE' => 'shop_review_opus'], '/work/shop-review-opus', '_review_opus'))->toBeNull();
});

test('a copy keeps the server and the credentials of the project\'s database URL', function () {
    $copy = screenRoundCopyEnv("APP_ENV=local\nDB_CONNECTION=mysql\nDB_URL=\"mysql://shop:s%23cret@127.0.0.1:3307/shop_dev\"\n", '/work/shop-review-haiku', 200, '_review_haiku');

    expect(screenRoundLaravelDatabase($copy))->toMatchArray(['host' => '127.0.0.1', 'port' => '3307', 'username' => 'shop', 'password' => 's#cret', 'database' => 'shop_dev_review_haiku']);
});

test('a SQLite project gives a copy its own database file, and a port from APP_URL', function () {
    $copy = screenRoundParse(screenRoundCopyEnv("APP_ENV=local\nAPP_URL=http://localhost:8001\nDB_CONNECTION=sqlite\nDB_DATABASE=/work/shop/database/database.sqlite\n", '/work/shop-review-opus', 100, '_review_opus'));

    expect($copy)->toMatchArray([
        'APP_URL' => 'http://localhost:8101',
        'SERVER_PORT' => '8101',
        'DB_DATABASE' => '/work/shop-review-opus/database/database.sqlite',
        'SESSION_COOKIE' => 'shop_review_opus_session',
    ]);
});

test('a port that is taken gives the next free one', function () {
    $port = screenRoundFreeSeatPort();
    $busy = stream_socket_server("tcp://127.0.0.1:{$port}");

    expect(screenRoundFreePort($port))->toBe($port + 1);

    fclose($busy);

    expect(screenRoundFreePort($port))->toBe($port);
});

test('a copy\'s app starts in the background, keeps the server\'s pid, and a restart replaces it', function () {
    $copy = sys_get_temp_dir().'/ezdev-screen-round-serve-'.bin2hex(random_bytes(6));
    $server = "{$copy}/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php";

    foreach (["{$copy}/storage/framework", "{$copy}/storage/logs"] as $dir) {
        mkdir($dir, 0777, true) ?: throw new RuntimeException("cannot make {$dir}");
    }

    // Laravel's own server.php, which loads index.php from the folder it runs in.
    workspaceCopy($copy, [substr($server, strlen("{$copy}/"))]);
    workspaceWrite($copy, 'public/index.php', "<?php\nif (\$_SERVER['REQUEST_URI'] === '/up') { echo 'Application up'; }\n");
    $port = screenRoundFreeSeatPort();
    $pids = [];
    register_shutdown_function(function () use (&$pids, $copy) {
        foreach ($pids as $pid) {
            @posix_kill($pid, SIGTERM);
        }
        (new Process(['rm', '-rf', $copy]))->run();
    });

    foreach ([1, 2] as $start) {
        $begin = microtime(true);
        screenRoundServe($copy, $port);
        $pids[] = (int) file_get_contents("{$copy}/".SCREEN_ROUND_PID);

        expect(microtime(true) - $begin)->toBeLessThan(5)
            ->and(screenRoundAnswers("http://127.0.0.1:{$port}", 10))->toBeTrue();
    }

    expect($pids[0])->not->toBe($pids[1])
        ->and(posix_kill($pids[0], 0))->toBeFalse()
        ->and(posix_kill($pids[1], 0))->toBeTrue()
        ->and((new Process(['ps', '-o', 'command=', '-p', (string) $pids[1]]))->mustRun()->getOutput())->toContain("-S 127.0.0.1:{$port}");
});

test('an app that answers /up without Laravel\'s health page does not count as answering', function () {
    $root = sys_get_temp_dir().'/ezdev-screen-round-probe-'.bin2hex(random_bytes(6));
    workspaceWrite($root, 'router.php', "<?php\necho \"<b>Fatal error</b>: Failed opening required 'index.php'\";\n");
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int) substr(strrchr((string) stream_socket_get_name($socket, false), ':') ?: ':0', 1);
    fclose($socket);
    $server = new Process([PHP_BINARY, '-S', "127.0.0.1:{$port}", "{$root}/router.php"], $root);
    $server->start();
    register_shutdown_function(function () use ($server, $root) {
        $server->stop();
        (new Process(['rm', '-rf', $root]))->run();
    });

    for ($i = 0; $i < 50 && @fsockopen('127.0.0.1', $port) === false; $i++) {
        usleep(100_000);
    }

    expect(screenRoundAnswers("http://127.0.0.1:{$port}"))->toBeFalse();
});

/**
 * A free port whose seat ports (plus 100 and 101 for reviewer-opus's copy, and
 * 200 and 201 of an older second copy) are free too, so a busy neighbouring port
 * cannot fail a run.
 * It is drawn below the system's range for outgoing connections (49152 and
 * up), which the kernel fills in order and so keeps busy next to each other.
 */
function screenRoundFreeSeatPort(): int
{
    for ($try = 0; $try < 200; $try++) {
        $port = random_int(20000, 40000);
        $free = true;

        foreach ([0, 1, 100, 101, 200, 201] as $offset) {
            $seat = @stream_socket_server('tcp://127.0.0.1:'.($port + $offset));

            if ($seat === false) {
                $free = false;

                break;
            }

            fclose($seat);
        }

        if ($free) {
            return $port;
        }
    }

    throw new RuntimeException('No free port with free seat ports found.');
}

/**
 * A project with a tiny reference feature, an artisan, a composer and an npm
 * that only note their calls in calls.txt next to the project, and Laravel's
 * server.php with an index.php that answers /up. Returns [project, its port].
 *
 * @return array{string, int}
 */
function screenRoundReferenceProject(array $override = []): array
{
    $port = screenRoundFreeSeatPort();
    $repo = screenRoundRepo("APP_ENV=local\nAPP_URL=http://127.0.0.1:{$port}\nSERVER_PORT={$port}\nDB_CONNECTION=sqlite\n");
    $calls = dirname($repo).'/calls.txt';
    $root = dirname(__DIR__, 3);
    $files = [
        '.gitignore' => ".env\n/public/build/\n/node_modules/\n",
        // As in the project: the folders the app writes keep only their .gitignore.
        'storage/logs/.gitignore' => "*\n!.gitignore\n",
        'storage/framework/.gitignore' => "*\n!.gitignore\n",
        'database/.gitignore' => "*.sqlite\n",
        // It notes the database settings it got from the environment too.
        'artisan' => "<?php\nfile_put_contents('{$calls}', getcwd().' artisan '.implode(' ', array_slice(\$argv, 1)).' env:'.json_encode(array_filter(getenv(), fn (\$key) => str_starts_with(\$key, 'DB_') || \$key === 'APP_ENV', ARRAY_FILTER_USE_KEY)).\"\\n\", FILE_APPEND);\n",
        // It also answers /env with the database settings of its environment.
        'public/index.php' => "<?php\nif (\$_SERVER['REQUEST_URI'] === '/up') { echo 'Application up'; }\nif (\$_SERVER['REQUEST_URI'] === '/env') { echo json_encode(array_filter(getenv(), fn (\$key) => str_starts_with(\$key, 'DB_') || \$key === 'APP_ENV', ARRAY_FILTER_USE_KEY)); }\n",
        'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php' => file_get_contents("{$root}/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php") ?: throw new RuntimeException('no server.php'),
        'routes/web.php' => "<?php\n",
        'docs/reference/tasks/README.md' => "# Reference feature\n",
        'docs/reference/tasks/routes/tasks.php' => "<?php\n",
        'docs/reference/tasks/app/Models/Task.php' => "<?php\n",
        ...$override,
    ];

    foreach ($files as $path => $content) {
        workspaceWrite($repo, $path, $content);
    }

    workspaceCommit($repo);
    $bin = dirname($repo).'/bin';

    foreach (['composer', 'npm'] as $tool) {
        workspaceWrite($bin, $tool, "#!/bin/sh\necho \"\$PWD {$tool} \$*\" >> \"{$calls}\"\n");
        chmod("{$bin}/{$tool}", 0755) ?: throw new RuntimeException("cannot make {$tool} runnable");
    }

    register_shutdown_function(function () use ($repo) {
        foreach (glob(dirname($repo).'/project-*/'.SCREEN_ROUND_PID) ?: [] as $pid) {
            @posix_kill((int) file_get_contents($pid), SIGTERM);
        }
    });

    return [$repo, $port];
}

function screenRoundRunIn(string $repo, array $arguments = [], array $environment = []): Process
{
    $process = new Process(['php', 'scripts/screen-round', ...$arguments], $repo, ['PATH' => dirname($repo).'/bin:'.getenv('PATH'), ...$environment]);
    $process->setTimeout(120);
    $process->run();

    return $process;
}

test('with --reference the copy has the reference feature, and the project\'s own app, files and database are not touched', function () {
    [$repo, $port] = screenRoundReferenceProject();
    $project = realpath($repo);
    $status = (new Process(['git', 'status', '--porcelain'], $repo))->mustRun()->getOutput();
    $env = (string) file_get_contents("{$repo}/.env");

    $process = screenRoundRunIn($repo, ['--reference']);
    $output = $process->getOutput().$process->getErrorOutput();
    $calls = (string) file_get_contents(dirname($repo).'/calls.txt');

    expect($process->getExitCode())->toBe(0, $output)
        ->and($output)->toContain('reviewer-opus, browser playwright: App: http://127.0.0.1:'.($port + 100))
        ->and($output)->not->toContain('reviewer-haiku')
        ->and($output)->toContain('with the reference feature in the copy')
        ->and($output)->toContain("the project's own app and database were not touched")
        ->and(is_dir(dirname($project).'/project-review-haiku'))->toBeFalse();

    foreach (['project-review-opus'] as $copy) {
        $dir = dirname($project)."/{$copy}";

        expect(is_file("{$dir}/app/Models/Task.php"))->toBeTrue($copy)
            ->and(is_file("{$dir}/routes/tasks.php"))->toBeTrue($copy)
            ->and(is_file("{$dir}/README.md"))->toBeFalse($copy)
            ->and((string) file_get_contents("{$dir}/routes/web.php"))->toContain("require __DIR__.'/tasks.php';")
            ->and($calls)->toContain("{$dir} artisan migrate:fresh --seed")
            ->and($calls)->toContain("{$dir} npm run build")
            ->and($calls)->toContain("{$dir} composer dump-autoload");
    }

    expect(array_filter(explode("\n", $calls), fn (string $line): bool => str_starts_with($line, "{$project} ")))->toBe([])
        ->and((new Process(['git', 'status', '--porcelain'], $repo))->mustRun()->getOutput())->toBe($status)
        ->and((string) file_get_contents("{$repo}/.env"))->toBe($env)
        ->and(screenRoundParse((string) file_get_contents(dirname($project).'/project-review-opus/.env')))->toMatchArray([
            'SERVER_PORT' => (string) ($port + 100),
            'SESSION_COOKIE' => 'project_review_opus_session',
            'DB_DATABASE' => dirname($project).'/project-review-opus/database/database.sqlite',
        ]);
});

test('without --reference the copy is made too, the project is not touched, and the copy no longer holds the feature of an earlier reference round', function () {
    [$repo, $port] = screenRoundReferenceProject();
    $project = realpath($repo);

    $first = screenRoundRunIn($repo, ['--reference']);

    expect($first->getExitCode())->toBe(0, $first->getOutput().$first->getErrorOutput());

    // Another program takes the port of the copy while its app is down: the
    // copy moves to the next free one.
    screenRoundStop(dirname($project).'/project-review-opus');

    for ($i = 0; $i < 50 && ($busy = @stream_socket_server('tcp://127.0.0.1:'.($port + 100))) === false; $i++) {
        usleep(100_000);
    }

    expect($busy)->not->toBeFalse();

    $process = screenRoundRunIn($repo);
    fclose($busy);
    $output = $process->getOutput().$process->getErrorOutput();
    $calls = (string) file_get_contents(dirname($repo).'/calls.txt');

    expect($process->getExitCode())->toBe(0, $output)
        ->and($output)->toContain('reviewer-opus, browser playwright: App: http://127.0.0.1:'.($port + 101))
        ->and($output)->toContain("the project's own app and database were not touched")
        ->and(screenRoundParse((string) file_get_contents(dirname($project).'/project-review-opus/.env'))['SERVER_PORT'] ?? '')->toBe((string) ($port + 101))
        ->and(array_filter(explode("\n", $calls), fn (string $line): bool => str_starts_with($line, "{$project} ")))->toBe([]);

    foreach (['project-review-opus'] as $copy) {
        $dir = dirname($project)."/{$copy}";

        expect(is_file("{$dir}/app/Models/Task.php"))->toBeFalse($copy)
            ->and(is_file("{$dir}/".SCREEN_ROUND_REFERENCE_MARK))->toBeFalse($copy)
            ->and((string) file_get_contents("{$dir}/routes/web.php"))->toBe("<?php\n");
    }
});

test('a copy whose app does not answer stops the round and names its log', function () {
    [$repo] = screenRoundReferenceProject(['public/index.php' => "<?php\necho 'Not ready';\n"]);

    $process = screenRoundRunIn($repo);
    $output = $process->getOutput().$process->getErrorOutput();

    expect($process->getExitCode())->toBe(1)
        ->and($output)->toContain('Screen round NOT ready.')
        ->and($output)->toContain('does not answer: read '.dirname(realpath($repo)).'/project-review-opus/'.SCREEN_ROUND_LOG)
        ->and($output)->toContain('No screen run starts before scripts/screen-round has passed.')
        ->and($output)->not->toContain('App: ');
});

test('a copy\'s commands and its app do not inherit the database settings of the shell that runs the round', function () {
    [$repo, $port] = screenRoundReferenceProject();

    $process = screenRoundRunIn($repo, [], ['DB_DATABASE' => 'shop', 'DB_URL' => 'mysql://root:@127.0.0.1/shop', 'DB_HOST' => 'db.example.com', 'APP_ENV' => 'production']);
    $lines = array_filter(explode("\n", (string) file_get_contents(dirname($repo).'/calls.txt')), fn (string $line): bool => str_contains($line, ' artisan '));

    expect($process->getExitCode())->toBe(0, $process->getOutput().$process->getErrorOutput())
        ->and($lines)->not->toBeEmpty()
        ->and(array_values(array_filter($lines, fn (string $line): bool => ! str_ends_with($line, ' env:[]'))))->toBe([]);

    // The app reviewer-opus walks through, as the copy serves it.
    expect(file_get_contents('http://127.0.0.1:'.($port + 100).'/env'))->toBe('[]');
});

test('an argument other than --reference changes nothing', function () {
    [$repo] = screenRoundReferenceProject();

    $process = screenRoundRunIn($repo, ['--refrence']);

    expect($process->getExitCode())->not->toBe(0)
        ->and($process->getOutput().$process->getErrorOutput())->toContain('Usage: scripts/screen-round [--reference]')
        ->and(file_exists(dirname($repo).'/calls.txt'))->toBeFalse();
});

test('a run inside a copy changes nothing', function (string $suffix) {
    [$repo] = screenRoundReferenceProject();
    rename($repo, "{$repo}{$suffix}");

    $process = screenRoundRunIn("{$repo}{$suffix}", ['--reference']);

    expect($process->getExitCode())->not->toBe(0)
        ->and($process->getOutput().$process->getErrorOutput())->toContain('not in a copy')
        ->and(file_exists(dirname($repo).'/calls.txt'))->toBeFalse();
})->with(['-review-opus', '-review-haiku', '-review', '-reference']);

test('an uncommitted file of the reference feature stops the round, its Markdown does not count as code', function () {
    [$repo] = screenRoundReferenceProject();
    workspaceWrite($repo, 'docs/reference/tasks/app/Models/Task.php', "<?php\n// changed\n");

    $process = screenRoundRunIn($repo, ['--reference']);

    expect($process->getExitCode())->not->toBe(0)
        ->and($process->getOutput().$process->getErrorOutput())->toContain('Commit the code before a screen round')->toContain('docs/reference/tasks/app/Models/Task.php')
        ->and(file_exists(dirname($repo).'/calls.txt'))->toBeFalse()
        ->and(screenRoundIsCode('docs/reference/tasks/app/Models/Task.php'))->toBeTrue()
        ->and(screenRoundIsCode('docs/reference/tasks/README.md'))->toBeFalse()
        ->and(screenRoundIsCode('docs/reference/pages/auth/login.tsx'))->toBeTrue()
        ->and(screenRoundIsCode('docs/reference/pages/README.md'))->toBeFalse();
});

test('with MySQL the copy has a database of its own, apart from the project\'s and composer check:reference\'s', function () {
    [$repo, $port] = screenRoundReferenceProject();
    // .env is not committed, so the project switches to MySQL on this machine.
    workspaceWrite($repo, '.env', "APP_ENV=local\nAPP_URL=http://127.0.0.1:{$port}\nSERVER_PORT={$port}\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_DATABASE=shop\n");

    $process = screenRoundRunIn($repo, ['--reference']);
    $seats = array_map(fn (string $copy): string => screenRoundParse((string) file_get_contents(dirname(realpath($repo))."/{$copy}/.env"))['DB_DATABASE'] ?? '', ['project-review-opus']);

    expect($process->getExitCode())->toBe(0, $process->getOutput().$process->getErrorOutput())
        ->and($seats)->toBe(['shop_review_opus'])
        ->and(array_intersect($seats, ['shop', 'shop_reference', 'shop_reference_test']))->toBe([]);
});
