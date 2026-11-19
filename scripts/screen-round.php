<?php

/*
| scripts/screen-round: prepares a screen round of the final review
| (docs/workflow.md, step 4). One command, run without asking the user:
|
| 1. It changes nothing unless APP_ENV is `local` and the project's database
|    server is on this machine: the copies' databases live on that server. The
|    screen lanes then do not run, and the report says why.
| 2. Each reviewer gets a copy of its own: ../<project folder>-review-opus for
|    reviewer-opus (`playwright`) and ../<project folder>-review-haiku for
|    reviewer-haiku (`playwright-review`). A copy is a git worktree detached at
|    the commit under review, with its own .env: its own database, session
|    cookie and port. It is made the first time and reused afterwards; nothing
|    deletes it.
| 3. The port is the project's plus 100 (opus) or 200 (haiku), or the next free
|    one when another program holds it; the copy's .env keeps the port it got.
| 4. It puts the commit into each copy (dependencies when a lock file changed,
|    the reference feature with --reference, assets built), resets each copy's
|    database to the seeded state (only the test accounts), starts or restarts
|    its app, checks that both answer, and prints the two addresses.
|
| The project's own app, its files and its development database are never
| touched, so `composer dev` need not run. Copies of earlier versions of this
| script (../<project folder>-review, ../<project folder>-reference) are no
| longer used: `git worktree remove` removes them.
*/

// The hosts that put a database on this machine.
const SCREEN_ROUND_LOCAL_HOSTS = ['127.0.0.1', 'localhost', '::1'];

// The two seats: the copy's folder suffix, its port offset from the project's,
// its database suffix, the reviewer and its browser.
const SCREEN_ROUND_SEATS = [
    ['-review-opus', 100, '_review_opus', 'reviewer-opus', 'playwright'],
    ['-review-haiku', 200, '_review_haiku', 'reviewer-haiku', 'playwright-review'],
];

// The folders of copies, of this version and of earlier ones: the command never runs inside one.
const SCREEN_ROUND_COPY_SUFFIXES = ['-review-opus', '-review-haiku', '-review', '-reference'];

// The reference feature, and the file that marks a copy it was installed in.
const SCREEN_ROUND_REFERENCE = 'docs/reference/tasks';
const SCREEN_ROUND_REFERENCE_MARK = 'storage/framework/screen-round-reference';

// Inside a copy: the pid of its app and the app's log.
const SCREEN_ROUND_PID = 'storage/logs/screen-round.pid';
const SCREEN_ROUND_LOG = 'storage/logs/screen-round-serve.log';

const SCREEN_ROUND_LOCKS = ['composer.lock', 'package-lock.json'];

/**
 * Runs the command in the project at $root; returns the exit code. With
 * $reference (`scripts/screen-round --reference`) both copies get the
 * reference feature.
 */
function screenRound(string $root, bool $reference = false): int
{
    $env = screenRoundParse((string) @file_get_contents("{$root}/.env"));
    $refusal = screenRoundRefusal($env);

    if ($refusal !== null) {
        return screenRoundFail("{$refusal} scripts/screen-round changed nothing: the copies' databases would be on that server. The screen lanes do not run; put that in the report (docs/workflow.md, step 5).");
    }

    $uncommitted = array_values(array_filter(
        screenRoundLines(screenRoundGit($root, ['status', '--porcelain'])),
        fn (string $line): bool => screenRoundIsCode(substr($line, 3)),
    ));

    if ($uncommitted !== []) {
        return screenRoundFail('Commit the code before a screen round (a round of uncommitted code does not count): '.implode(', ', array_map(fn (string $line): string => trim(substr($line, 3)), $uncommitted)).'. Nothing was changed.');
    }

    $commit = trim(screenRoundGit($root, ['rev-parse', 'HEAD']));
    $seats = [];

    foreach (SCREEN_ROUND_SEATS as [$suffix, $offset, $database, $reviewer, $browser]) {
        $seats[] = [...screenRoundCopy($root, $suffix, $commit, $offset, $database, $reference), 'reviewer' => $reviewer, 'browser' => $browser];
    }

    foreach ($seats as $seat) {
        if (! screenRoundAnswers($seat['url'], 20)) {
            return screenRoundFail($seat['down'].' No screen run starts before scripts/screen-round has passed.');
        }
    }

    echo 'Screen round ready on '.substr($commit, 0, 7).($reference ? ', with the reference feature in both copies' : '').", both copies' databases reset to the seeded state; the project's own app and database were not touched.\n";

    foreach ($seats as $number => $seat) {
        echo 'Seat '.($number + 1).", {$seat['reviewer']}, browser {$seat['browser']}: App: {$seat['url']}\n";
    }

    return 0;
}

/**
 * Makes or reuses the copy ../<project folder><suffix>, a git worktree detached
 * at $commit with its own .env, puts the commit into it (dependencies when a
 * lock file changed, the reference feature with $reference, assets built),
 * resets its database to the seeded state and starts its app. A copy that held
 * the reference feature in an earlier round gets the commit's files only first.
 *
 * @return array{url: string, down: string}
 */
function screenRoundCopy(string $root, string $suffix, string $commit, int $offset, string $database, bool $reference): array
{
    $copy = dirname($root).'/'.basename($root).$suffix;
    $first = ! is_dir($copy);
    $before = null;

    if ($first) {
        screenRoundRun($root, ['git', 'worktree', 'add', '--detach', $copy, $commit]);
        file_put_contents("{$copy}/.env", screenRoundCopyEnv((string) file_get_contents("{$root}/.env"), $copy, $offset, $database));
        echo "Copy created: {$copy}\n";
    } else {
        $before = trim(screenRoundGit($copy, ['rev-parse', 'HEAD']));

        if (is_file("{$copy}/".SCREEN_ROUND_REFERENCE_MARK)) {
            screenRoundRun($copy, ['git', 'checkout', '--quiet', '--force', '--detach', $commit]);
            screenRoundRun($copy, ['git', 'clean', '-fdq']);
            @unlink("{$copy}/".SCREEN_ROUND_REFERENCE_MARK);
        } else {
            screenRoundRun($copy, ['git', 'checkout', '--quiet', '--detach', $commit]);
        }
    }

    $copyEnv = screenRoundParse((string) file_get_contents("{$copy}/.env"));

    if (($copyEnv['DB_CONNECTION'] ?? 'mysql') === 'sqlite') {
        $file = $copyEnv['DB_DATABASE'] ?? "{$copy}/database/database.sqlite";
        $file = str_starts_with($file, '/') ? $file : "{$copy}/{$file}";
        @mkdir(dirname($file), 0777, true);
        touch($file);
    }

    $locks = $first || ! is_dir("{$copy}/vendor") || ! is_dir("{$copy}/node_modules")
        || trim(screenRoundGit($copy, ['diff', '--name-only', $before ?? $commit, $commit, '--', ...SCREEN_ROUND_LOCKS])) !== '';

    if ($locks) {
        screenRoundRun($copy, ['composer', 'install', '--no-interaction', '--quiet']);
        screenRoundRun($copy, ['npm', 'ci', '--silent']);
    }

    if ($reference) {
        screenRoundInstallReference($copy);
        screenRoundRun($copy, ['composer', 'dump-autoload', '--quiet']);
    }

    // Also regenerates the Wayfinder routes, the reference feature's too.
    screenRoundRun($copy, ['npm', 'run', 'build', '--silent']);

    if ($first) {
        // A new MySQL database is created by migrate --force; migrate:fresh needs it to exist.
        screenRoundRun($copy, ['php', 'artisan', 'migrate', '--force', '--no-interaction']);
    }

    screenRoundRun($copy, ['php', 'artisan', 'migrate:fresh', '--seed', '--force', '--no-interaction']);

    // The copy's own app gives its port back first, so a copy keeps its port
    // unless another program took it.
    screenRoundStop($copy);
    $port = screenRoundFreePort(screenRoundPort($copyEnv));

    if ($port !== screenRoundPort($copyEnv)) {
        file_put_contents("{$copy}/.env", screenRoundSetPort((string) file_get_contents("{$copy}/.env"), $port));
        $copyEnv = screenRoundParse((string) file_get_contents("{$copy}/.env"));
    }

    $url = screenRoundUrl($copyEnv);
    screenRoundServe($copy, $port);

    return ['url' => $url, 'down' => "The copy's app at {$url} does not answer: read {$copy}/".SCREEN_ROUND_LOG.'.'];
}

/**
 * Installs the reference feature in a copy the way composer check:reference
 * does: its files, but its README, at their paths without the
 * docs/reference/tasks/ prefix, and its routes required from routes/web.php.
 */
function screenRoundInstallReference(string $copy): void
{
    $source = "{$copy}/".SCREEN_ROUND_REFERENCE;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        $path = substr($file->getPathname(), strlen($source) + 1);

        if ($path === 'README.md') {
            continue;
        }

        @mkdir(dirname("{$copy}/{$path}"), 0777, true);
        copy($file->getPathname(), "{$copy}/{$path}");
    }

    $routes = "{$copy}/routes/web.php";
    $require = "require __DIR__.'/tasks.php';";

    // The copy was reset to the commit first, so the line is not there yet.
    file_put_contents($routes, "{$require}\n", FILE_APPEND);

    @mkdir(dirname("{$copy}/".SCREEN_ROUND_REFERENCE_MARK), 0777, true);
    touch("{$copy}/".SCREEN_ROUND_REFERENCE_MARK);
}

/**
 * Why the copies' databases may not be made and reset, or null when they may:
 * the project's environment is not local or its database server is elsewhere.
 *
 * @param  array<string, string>  $env
 */
function screenRoundRefusal(array $env): ?string
{
    $appEnv = $env['APP_ENV'] ?? '';

    if ($appEnv !== 'local') {
        return 'APP_ENV is '.($appEnv === '' ? 'not set' : "`{$appEnv}`").', not `local`.';
    }

    $connection = $env['DB_CONNECTION'] ?? 'mysql';
    $host = $env['DB_HOST'] ?? '127.0.0.1';

    if ($connection !== 'sqlite' && ! in_array($host, SCREEN_ROUND_LOCAL_HOSTS, true)) {
        return "The database is on {$host}, not on this machine.";
    }

    return null;
}

/**
 * A copy's .env: the project's, with its own URL, port, session cookie and
 * database, and jobs run in the request (composer dev's queue worker runs only
 * for the project).
 */
function screenRoundCopyEnv(string $env, string $copy, int $offset, string $database): string
{
    $values = screenRoundParse($env);

    return screenRoundSetPort(screenRoundSet($env, [
        'SESSION_COOKIE' => preg_replace('/[^a-z0-9_]/', '_', strtolower(basename($copy))).'_session',
        'DB_DATABASE' => ($values['DB_CONNECTION'] ?? 'mysql') === 'sqlite'
            ? "{$copy}/database/database.sqlite"
            : ($values['DB_DATABASE'] ?? 'laravel').$database,
        // No queue worker runs for a copy: jobs run in the request.
        'QUEUE_CONNECTION' => 'sync',
    ]), screenRoundPort($values) + $offset);
}

/**
 * The .env with the app on $port: APP_URL and SERVER_PORT.
 */
function screenRoundSetPort(string $env, int $port): string
{
    $url = parse_url(screenRoundParse($env)['APP_URL'] ?? '') ?: [];

    return screenRoundSet($env, [
        'APP_URL' => ($url['scheme'] ?? 'http').'://'.($url['host'] ?? 'localhost').":{$port}",
        'SERVER_PORT' => (string) $port,
    ]);
}

/**
 * The .env with each KEY=value of $set, replacing the line of that key or added at the end.
 *
 * @param  array<string, string>  $set
 */
function screenRoundSet(string $env, array $set): string
{
    foreach ($set as $key => $value) {
        $line = "{$key}={$value}";
        $env = preg_match("/^{$key}=/m", $env) === 1
            ? (string) preg_replace("/^{$key}=.*$/m", addcslashes($line, '\\$'), $env)
            : rtrim($env)."\n{$line}\n";
    }

    return $env;
}

/**
 * $port, or the next port after it that is free: nothing answers on it and it
 * can be opened.
 */
function screenRoundFreePort(int $port): int
{
    for ($try = $port; $try < $port + 100; $try++) {
        $answer = @fsockopen('127.0.0.1', $try, $code, $message, 0.2);

        if ($answer !== false) {
            fclose($answer);

            continue;
        }

        $socket = @stream_socket_server("tcp://127.0.0.1:{$try}");

        if ($socket !== false) {
            fclose($socket);

            return $try;
        }
    }

    throw new RuntimeException("no free port from {$port} to ".($port + 99).'.');
}

/**
 * The KEY=value lines of a .env file, without quotes.
 *
 * @return array<string, string>
 */
function screenRoundParse(string $env): array
{
    preg_match_all('/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/m', $env, $lines, PREG_SET_ORDER);
    $values = [];

    foreach ($lines as [, $key, $value]) {
        $values[$key] = preg_match('/^(["\'])(.*)\1$/', $value, $quoted) === 1 ? $quoted[2] : $value;
    }

    return $values;
}

/**
 * @param  array<string, string>  $env
 */
function screenRoundPort(array $env): int
{
    return (int) ($env['SERVER_PORT'] ?? parse_url($env['APP_URL'] ?? '', PHP_URL_PORT) ?? 8000);
}

/**
 * @param  array<string, string>  $env
 */
function screenRoundUrl(array $env): string
{
    return rtrim($env['APP_URL'] ?? 'http://localhost:'.screenRoundPort($env), '/');
}

/**
 * Whether the app at $url answers (any status below 500), waiting up to $wait seconds.
 */
function screenRoundAnswers(string $url, int $wait = 0): bool
{
    $until = microtime(true) + $wait;
    $context = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true, 'follow_location' => 0]]);

    do {
        $http_response_header = [];

        // Laravel's health page, not only a status: a server that cannot load
        // the app still answers 200 with PHP's fatal error.
        $body = @file_get_contents("{$url}/up", false, $context);

        if ($body !== false && str_contains($body, 'Application up') && preg_match('#^HTTP/\S+ (\d{3})#', $http_response_header[0] ?? '', $status) === 1 && (int) $status[1] === 200) {
            return true;
        }

        if ($wait > 0) {
            usleep(500_000);
        }
    } while (microtime(true) < $until);

    return false;
}

/**
 * Stops a copy's app if it runs.
 */
function screenRoundStop(string $copy): void
{
    $pid = (int) @file_get_contents("{$copy}/".SCREEN_ROUND_PID);

    if ($pid > 0 && posix_kill($pid, 0)) {
        posix_kill($pid, SIGTERM);

        for ($i = 0; $i < 20 && posix_kill($pid, 0); $i++) {
            usleep(100_000);
        }
    }
}

/**
 * Stops a copy's app if it runs, and starts it again on $port.
 */
function screenRoundServe(string $copy, int $port): void
{
    $pidFile = "{$copy}/".SCREEN_ROUND_PID;
    screenRoundStop($copy);

    // The server artisan serve runs, started directly so that its pid is the
    // server's: one process, no workers, so stopping that pid stops the app. Only
    // the server goes to the background, with its output in the log and no other
    // inherited descriptor, so neither the shell nor the caller waits for it.
    $process = proc_open(
        ['sh', '-c', 'nohup "$0" -S "127.0.0.1:$1" ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php > "../$2" 2>&1 < /dev/null 3>&- 4>&- 5>&- 6>&- 7>&- 8>&- 9>&- 10>&- 11>&- 12>&- 13>&- 14>&- 15>&- 16>&- 17>&- 18>&- 19>&- 20>&- 21>&- 22>&- 23>&- 24>&- 25>&- 26>&- 27>&- 28>&- 29>&- 30>&- & echo $!', PHP_BINARY, (string) $port, SCREEN_ROUND_LOG],
        [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']],
        $pipes,
        // server.php serves the folder it runs in, as artisan serve does.
        "{$copy}/public",
    );

    if (! is_resource($process)) {
        throw new RuntimeException("cannot start the copy's app in {$copy}.");
    }

    $pid = trim((string) stream_get_contents($pipes[1]));
    proc_close($process);
    file_put_contents($pidFile, $pid);
}

/**
 * Runs a command in $dir, its output passed through; throws when it fails.
 *
 * @param  list<string>  $command
 */
function screenRoundRun(string $dir, array $command): void
{
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $dir);

    if (! is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException(implode(' ', $command)." failed in {$dir}.");
    }
}

/**
 * @param  list<string>  $args
 */
function screenRoundGit(string $dir, array $args): string
{
    $process = proc_open(['git', ...$args], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $dir);

    if (! is_resource($process)) {
        throw new RuntimeException('git '.implode(' ', $args).' failed.');
    }

    $output = (string) stream_get_contents($pipes[1]);
    $error = (string) stream_get_contents($pipes[2]);

    if (proc_close($process) !== 0) {
        throw new RuntimeException('git '.implode(' ', $args).' failed: '.trim($error));
    }

    return $output;
}

/**
 * @return list<string>
 */
function screenRoundLines(string $text): array
{
    return array_values(array_filter(explode("\n", $text), fn (string $line): bool => trim($line) !== ''));
}

/**
 * Code is every file outside docs/ and *.md, and the reference feature's files
 * other than Markdown, as for the review rounds.
 */
function screenRoundIsCode(string $path): bool
{
    $path = trim($path, ' "');

    return ! str_ends_with($path, '.md') && (! str_starts_with($path, 'docs/') || str_starts_with($path, SCREEN_ROUND_REFERENCE.'/'));
}

function screenRoundFail(string $message): int
{
    fwrite(STDERR, "Screen round NOT ready. {$message}\n");

    return 1;
}
