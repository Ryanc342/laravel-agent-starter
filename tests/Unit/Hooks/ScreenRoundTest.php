<?php

use Symfony\Component\Process\Process;

require_once dirname(__DIR__, 3).'/scripts/screen-round.php';

/*
| scripts/screen-round prepares a screen round of the final review
| (docs/workflow.md, step 4): the review
| copy, both databases reset, both apps answering. It resets the project's
| database without asking only when that database is a local development one.
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
]);

/**
 * A throwaway git repository holding the script, with the given .env.
 */
function screenRoundRepo(string $env): string
{
    $parent = sys_get_temp_dir().'/ezdev-screen-round-'.bin2hex(random_bytes(6));
    $repo = "{$parent}/project";
    mkdir("{$repo}/scripts", 0777, true);
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $parent]))->run());

    foreach (['scripts/screen-round', 'scripts/screen-round.php'] as $path) {
        copy(dirname(__DIR__, 3)."/{$path}", "{$repo}/{$path}");
    }

    file_put_contents("{$repo}/.env", $env);
    file_put_contents("{$repo}/.gitignore", ".env\n");

    foreach ([['init', '-q', '-b', 'main'], ['add', '-A'], ['-c', 'user.name=t', '-c', 'user.email=t@t', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null', 'commit', '-q', '-m', 'work']] as $args) {
        (new Process(['git', ...$args], $repo))->mustRun();
    }

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

test('a database that is not local, or an APP_ENV that is not local, changes nothing and sends the builder to the user', function (string $env) {
    $repo = screenRoundRepo($env);
    $before = screenRoundState($repo);

    $process = new Process(['php', 'scripts/screen-round'], $repo);
    $process->run();

    expect($process->getExitCode())->toBe(1)
        ->and($process->getOutput().$process->getErrorOutput())->toContain('changed nothing')->toContain('Ask the user')
        ->and(screenRoundState($repo))->toBe($before);
})->with([
    'production' => ["APP_ENV=production\nAPP_URL=http://127.0.0.1:1\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_DATABASE=shop\n"],
    'a remote database' => ["APP_ENV=local\nAPP_URL=http://127.0.0.1:1\nDB_CONNECTION=mysql\nDB_HOST=db.example.com\nDB_DATABASE=shop\n"],
]);

test('a project app that does not answer stops the round before anything changes', function () {
    $repo = screenRoundRepo("APP_ENV=local\nAPP_URL=http://127.0.0.1:1\nDB_CONNECTION=sqlite\n");
    $before = screenRoundState($repo);

    $process = new Process(['php', 'scripts/screen-round'], $repo);
    $process->run();

    expect($process->getExitCode())->toBe(1)
        ->and($process->getOutput().$process->getErrorOutput())->toContain('http://127.0.0.1:1 does not answer')->toContain('composer dev')
        ->and(screenRoundState($repo))->toBe($before);
});

test('the review copy gets its own database, port and session cookie', function () {
    $env = "APP_NAME=Shop\nQUEUE_CONNECTION=database\nAPP_ENV=local\nAPP_URL=http://localhost:8004\nSERVER_PORT=8004\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_DATABASE=shop_dev\nSESSION_DRIVER=database\n";
    $copy = screenRoundCopyEnv($env, '/work/shop-review');

    expect(screenRoundParse($copy))->toMatchArray([
        'APP_NAME' => 'Shop',
        'APP_URL' => 'http://localhost:8104',
        'SERVER_PORT' => '8104',
        'DB_HOST' => '127.0.0.1',
        'DB_DATABASE' => 'shop_dev_review',
        'SESSION_DRIVER' => 'database',
        'SESSION_COOKIE' => 'shop_review_session',
        'QUEUE_CONNECTION' => 'sync',
    ]);
});

test('a SQLite project gives the review copy its own database file, and a port from APP_URL', function () {
    $copy = screenRoundParse(screenRoundCopyEnv("APP_ENV=local\nAPP_URL=http://localhost:8001\nDB_CONNECTION=sqlite\nDB_DATABASE=/work/shop/database/database.sqlite\n", '/work/shop-review'));

    expect($copy)->toMatchArray([
        'APP_URL' => 'http://localhost:8101',
        'SERVER_PORT' => '8101',
        'DB_DATABASE' => '/work/shop-review/database/database.sqlite',
        'SESSION_COOKIE' => 'shop_review_session',
    ]);
});

test('the review copy\'s app starts in the background, keeps the server\'s pid, and a restart replaces it', function () {
    $copy = sys_get_temp_dir().'/ezdev-screen-round-serve-'.bin2hex(random_bytes(6));
    $server = "{$copy}/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php";

    foreach (["{$copy}/public", "{$copy}/storage/framework", "{$copy}/storage/logs", dirname($server)] as $dir) {
        mkdir($dir, 0777, true);
    }

    // Laravel's own server.php, which loads index.php from the folder it runs in.
    copy(dirname(__DIR__, 3).'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php', $server);
    file_put_contents("{$copy}/public/index.php", "<?php\nif (\$_SERVER['REQUEST_URI'] === '/up') { echo 'Application up'; }\n");
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
    mkdir($root);
    file_put_contents("{$root}/router.php", "<?php\necho \"<b>Fatal error</b>: Failed opening required 'index.php'\";\n");
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
 * A PHP server on a free port that answers every request with $body; returns its address.
 */
function screenRoundServer(string $body): string
{
    $root = sys_get_temp_dir().'/ezdev-screen-round-server-'.bin2hex(random_bytes(6));
    mkdir($root);
    file_put_contents("{$root}/router.php", '<?php echo '.var_export($body, true).";\n");
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

    return "http://127.0.0.1:{$port}";
}

/**
 * A free port nothing listens on.
 */
function screenRoundDeadUrl(): string
{
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int) substr(strrchr((string) stream_socket_get_name($socket, false), ':') ?: ':0', 1);
    fclose($socket);

    return "http://127.0.0.1:{$port}";
}

/**
 * Runs screenRoundFrontend() in the project with a fake npm on PATH that notes
 * each call in npm-calls.txt, next to the project; returns [exit code, output].
 *
 * @return array{int, string}
 */
function screenRoundFrontendRun(string $repo): array
{
    $bin = dirname($repo).'/bin';
    @mkdir($bin);
    file_put_contents("{$bin}/npm", "#!/bin/sh\necho \"\$PWD \$*\" >> \"".dirname($repo)."/npm-calls.txt\"\n");
    chmod("{$bin}/npm", 0755);
    $process = new Process([PHP_BINARY, '-r', 'require "scripts/screen-round.php"; $message = screenRoundFrontend(getcwd()); echo $message ?? "ready"; exit($message === null ? 0 : 1);'], $repo, ['PATH' => "{$bin}:".getenv('PATH')]);
    $process->run();

    return [(int) $process->getExitCode(), $process->getOutput().$process->getErrorOutput()];
}

test('without a Vite dev server the project\'s frontend is built before the round', function () {
    $repo = screenRoundRepo("APP_ENV=local\nAPP_URL=http://127.0.0.1:1\nDB_CONNECTION=sqlite\n");

    [$exit, $output] = screenRoundFrontendRun($repo);

    expect($exit)->toBe(0)
        ->and($output)->toBe('ready')
        ->and(file_get_contents(dirname($repo).'/npm-calls.txt'))->toContain(realpath($repo).' run build');
});

test('a Vite dev server that answers serves the current frontend, so nothing is built', function () {
    $repo = screenRoundRepo("APP_ENV=local\nAPP_URL=http://127.0.0.1:1\nDB_CONNECTION=sqlite\n");
    mkdir("{$repo}/public");
    file_put_contents("{$repo}/public/hot", screenRoundServer('import "/@vite/env";'));

    [$exit, $output] = screenRoundFrontendRun($repo);

    expect($exit)->toBe(0)
        ->and($output)->toBe('ready')
        ->and(is_file(dirname($repo).'/npm-calls.txt'))->toBeFalse();
});

test('a public/hot whose dev server does not answer stops the round before anything changes', function () {
    $app = screenRoundServer('Application up');
    $repo = screenRoundRepo("APP_ENV=local\nAPP_URL={$app}\nDB_CONNECTION=sqlite\n");
    mkdir("{$repo}/public");
    $hot = screenRoundDeadUrl();
    file_put_contents("{$repo}/public/hot", $hot);
    file_put_contents("{$repo}/.gitignore", ".env\n/public/hot\n");
    (new Process(['git', '-c', 'user.name=t', '-c', 'user.email=t@t', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null', 'commit', '-qam', 'ignore hot'], $repo))->mustRun();
    $bin = dirname($repo).'/bin';
    mkdir($bin);
    file_put_contents("{$bin}/npm", "#!/bin/sh\necho \"\$PWD \$*\" >> \"".dirname($repo)."/npm-calls.txt\"\n");
    chmod("{$bin}/npm", 0755);
    $before = screenRoundState($repo);

    $process = new Process(['php', 'scripts/screen-round'], $repo, ['PATH' => "{$bin}:".getenv('PATH')]);
    $process->run();

    expect($process->getExitCode())->toBe(1)
        ->and($process->getOutput().$process->getErrorOutput())->toContain("{$hot} does not answer")->toContain('composer dev')
        ->and(screenRoundState($repo))->toBe($before)
        ->and(is_file(dirname($repo).'/npm-calls.txt'))->toBeFalse();
});

/**
 * A free port whose seat ports (plus 100 for the review copy, plus 200 for the
 * reference seat) are free too, so a busy neighbouring port cannot fail a run.
 * It is drawn below the system's range for outgoing connections (49152 and
 * up), which the kernel fills in order and so keeps busy next to each other.
 */
function screenRoundFreeSeatPort(): int
{
    for ($try = 0; $try < 200; $try++) {
        $port = random_int(20000, 40000);
        $free = true;

        foreach ([0, 100, 200] as $offset) {
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
function screenRoundReferenceProject(string $appUrl = ''): array
{
    $port = screenRoundFreeSeatPort();
    $url = $appUrl === '' ? "http://127.0.0.1:{$port}" : $appUrl;
    $repo = screenRoundRepo("APP_ENV=local\nAPP_URL={$url}\nSERVER_PORT={$port}\nDB_CONNECTION=sqlite\n");
    $calls = dirname($repo).'/calls.txt';
    $root = dirname(__DIR__, 3);
    $files = [
        '.gitignore' => ".env\n/public/build/\n/node_modules/\n",
        // As in the project: the folders the app writes keep only their .gitignore.
        'storage/logs/.gitignore' => "*\n!.gitignore\n",
        'storage/framework/.gitignore' => "*\n!.gitignore\n",
        'database/.gitignore' => "*.sqlite\n",
        'artisan' => "<?php\nfile_put_contents('{$calls}', getcwd().' artisan '.implode(' ', array_slice(\$argv, 1)).\"\\n\", FILE_APPEND);\n",
        'public/index.php' => "<?php\nif (\$_SERVER['REQUEST_URI'] === '/up') { echo 'Application up'; }\n",
        'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php' => (string) file_get_contents("{$root}/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"),
        'routes/web.php' => "<?php\n",
        'docs/reference/tasks/README.md' => "# Reference feature\n",
        'docs/reference/tasks/routes/tasks.php' => "<?php\n",
        'docs/reference/tasks/app/Models/Task.php' => "<?php\n",
    ];

    foreach ($files as $path => $content) {
        @mkdir(dirname("{$repo}/{$path}"), 0777, true);
        file_put_contents("{$repo}/{$path}", $content);
    }

    (new Process(['git', 'add', '-A'], $repo))->mustRun();
    (new Process(['git', '-c', 'user.name=t', '-c', 'user.email=t@t', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null', 'commit', '-qm', 'project'], $repo))->mustRun();

    $bin = dirname($repo).'/bin';
    mkdir($bin);

    foreach (['composer', 'npm'] as $tool) {
        file_put_contents("{$bin}/{$tool}", "#!/bin/sh\necho \"\$PWD {$tool} \$*\" >> \"{$calls}\"\n");
        chmod("{$bin}/{$tool}", 0755);
    }

    register_shutdown_function(function () use ($repo) {
        foreach (glob(dirname($repo).'/project-*/'.SCREEN_ROUND_PID) ?: [] as $pid) {
            @posix_kill((int) file_get_contents($pid), SIGTERM);
        }
    });

    return [$repo, $port];
}

function screenRoundRunIn(string $repo, array $arguments = []): Process
{
    $process = new Process(['php', 'scripts/screen-round', ...$arguments], $repo, ['PATH' => dirname($repo).'/bin:'.getenv('PATH')]);
    $process->setTimeout(120);
    $process->run();

    return $process;
}

test('with --reference both seats are copies with the reference feature, and the project\'s own app, files and database are not touched', function () {
    [$repo, $port] = screenRoundReferenceProject();
    $project = realpath($repo);
    $status = (new Process(['git', 'status', '--porcelain'], $repo))->mustRun()->getOutput();
    $env = (string) file_get_contents("{$repo}/.env");

    $process = screenRoundRunIn($repo, ['--reference']);
    $output = $process->getOutput().$process->getErrorOutput();
    $calls = (string) file_get_contents(dirname($repo).'/calls.txt');

    expect($process->getExitCode())->toBe(0, $output)
        ->and($output)->toContain('Seat 1, reviewer-opus, browser playwright: App: http://127.0.0.1:'.($port + 200))
        ->and($output)->toContain('Seat 2, reviewer-haiku, browser playwright-review: App: http://127.0.0.1:'.($port + 100))
        ->and($output)->toContain('with the reference feature in both copies')
        ->and($output)->toContain("the project's own app and database were not touched");

    foreach (['project-reference', 'project-review'] as $copy) {
        $dir = dirname($project)."/{$copy}";

        expect(is_file("{$dir}/app/Models/Task.php"))->toBeTrue($copy)
            ->and(is_file("{$dir}/routes/tasks.php"))->toBeTrue($copy)
            ->and(is_file("{$dir}/README.md"))->toBeFalse($copy)
            ->and((string) file_get_contents("{$dir}/routes/web.php"))->toContain("require __DIR__.'/tasks.php';")
            ->and($calls)->toContain("{$dir} artisan migrate:fresh --seed")
            ->and($calls)->toContain("{$dir} composer dump-autoload");
    }

    expect(array_filter(explode("\n", $calls), fn (string $line): bool => str_starts_with($line, "{$project} ")))->toBe([])
        ->and((new Process(['git', 'status', '--porcelain'], $repo))->mustRun()->getOutput())->toBe($status)
        ->and((string) file_get_contents("{$repo}/.env"))->toBe($env)
        ->and(screenRoundParse((string) file_get_contents(dirname($project).'/project-reference/.env')))->toMatchArray([
            'SERVER_PORT' => (string) ($port + 200),
            'SESSION_COOKIE' => 'project_reference_session',
            'DB_DATABASE' => dirname($project).'/project-reference/database/database.sqlite',
        ]);
});

test('without --reference the project is seat 1 and the review copy no longer holds the feature of an earlier reference round', function () {
    $app = screenRoundServer('Application up');
    [$repo, $port] = screenRoundReferenceProject($app);

    $first = screenRoundRunIn($repo, ['--reference']);

    expect($first->getExitCode())->toBe(0, $first->getOutput().$first->getErrorOutput());

    $process = screenRoundRunIn($repo);
    $output = $process->getOutput().$process->getErrorOutput();
    $review = dirname(realpath($repo)).'/project-review';

    expect($process->getExitCode())->toBe(0, $output)
        ->and($output)->toContain("Seat 1, reviewer-opus, browser playwright: App: {$app}")
        ->and($output)->toContain('Seat 2, reviewer-haiku, browser playwright-review: App: http://127.0.0.1:'.($port + 100))
        ->and(is_file("{$review}/app/Models/Task.php"))->toBeFalse()
        ->and(is_file("{$review}/".SCREEN_ROUND_REFERENCE_MARK))->toBeFalse()
        ->and((string) file_get_contents("{$review}/routes/web.php"))->toBe("<?php\n")
        ->and((string) file_get_contents(dirname($repo).'/calls.txt'))->toContain(realpath($repo).' artisan migrate:fresh --seed');
});

test('an argument other than --reference changes nothing', function () {
    [$repo] = screenRoundReferenceProject();

    $process = screenRoundRunIn($repo, ['--refrence']);

    expect($process->getExitCode())->not->toBe(0)
        ->and($process->getOutput().$process->getErrorOutput())->toContain('Usage: scripts/screen-round [--reference]')
        ->and(file_exists(dirname($repo).'/calls.txt'))->toBeFalse();
});

test('a run inside a reference copy changes nothing', function () {
    [$repo] = screenRoundReferenceProject();
    rename($repo, "{$repo}-reference");

    $process = screenRoundRunIn("{$repo}-reference", ['--reference']);

    expect($process->getExitCode())->not->toBe(0)
        ->and($process->getOutput().$process->getErrorOutput())->toContain('not in a copy')
        ->and(file_exists(dirname($repo).'/calls.txt'))->toBeFalse();
});

test('an uncommitted file of the reference feature stops the round, its Markdown does not count as code', function () {
    [$repo] = screenRoundReferenceProject();
    file_put_contents("{$repo}/docs/reference/tasks/app/Models/Task.php", "<?php\n// changed\n");

    $process = screenRoundRunIn($repo, ['--reference']);

    expect($process->getExitCode())->not->toBe(0)
        ->and($process->getOutput().$process->getErrorOutput())->toContain('Commit the code before a screen round')->toContain('docs/reference/tasks/app/Models/Task.php')
        ->and(file_exists(dirname($repo).'/calls.txt'))->toBeFalse()
        ->and(screenRoundIsCode('docs/reference/tasks/app/Models/Task.php'))->toBeTrue()
        ->and(screenRoundIsCode('docs/reference/tasks/README.md'))->toBeFalse();
});

test('with --reference and MySQL, seat 1 has a database of its own, apart from the review copy\'s and composer check:reference\'s', function () {
    [$repo, $port] = screenRoundReferenceProject();
    // .env is not committed, so the project switches to MySQL on this machine.
    file_put_contents("{$repo}/.env", "APP_ENV=local\nAPP_URL=http://127.0.0.1:{$port}\nSERVER_PORT={$port}\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_DATABASE=shop\n");

    $process = screenRoundRunIn($repo, ['--reference']);
    $seats = array_map(fn (string $copy): string => screenRoundParse((string) file_get_contents(dirname(realpath($repo))."/{$copy}/.env"))['DB_DATABASE'] ?? '', ['project-reference', 'project-review']);

    expect($process->getExitCode())->toBe(0, $process->getOutput().$process->getErrorOutput())
        ->and($seats)->toBe(['shop_reference_seat', 'shop_review'])
        ->and(array_intersect($seats, ['shop', 'shop_reference', 'shop_reference_test']))->toBe([]);
});
