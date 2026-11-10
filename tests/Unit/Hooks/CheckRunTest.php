<?php

use Symfony\Component\Process\Process;

require_once __DIR__.'/../../Support/MilestoneWorkspace.php';

function checkRunFixture(): string
{
    $repo = milestoneWorkspace();
    $barrier = <<<'PHP'
if (getenv('CHECK_BLOCK_STATIC') === '1') {
    echo "STATIC_READY\n";
    $until = microtime(true) + 15;
    while (! is_file(__DIR__.'/runtime/release')) {
        if (microtime(true) > $until) { exit(8); }
        usleep(10000);
    }
}
PHP;
    $composer = file_get_contents("{$repo}/composer.php");
    file_put_contents("{$repo}/composer.php", str_replace("exit(getenv('MILESTONE_TEST_FAIL')", $barrier."\nexit(getenv('MILESTONE_TEST_FAIL')", $composer));

    return $repo;
}

function checkRunProcess(string $repo, string $mode = 'milestone', array $env = []): Process
{
    $args = $mode === 'full' ? ['scripts/check.php'] : ['scripts/check-milestone.php', 'tests/Feature'];

    return new Process([PHP_BINARY, ...$args], $repo, [
        'PATH' => "{$repo}/bin:".getenv('PATH'), 'COMPOSER_BINARY' => "{$repo}/composer.php", ...$env,
    ]);
}

function checkRunStartBlocked(string $repo, string $mode = 'milestone'): Process
{
    $run = checkRunProcess($repo, $mode, ['CHECK_BLOCK_STATIC' => '1']);
    $run->start();
    expect($run->waitUntil(fn ($type, $out) => str_contains($out, 'STATIC_READY')))->toBeTrue($run->getErrorOutput());

    return $run;
}

test('the full runner executes every phase and records only a full pass', function () {
    $repo = checkRunFixture();
    $run = checkRunProcess($repo, 'full');
    $run->run();
    $commands = array_map(fn ($line) => json_decode($line, true), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES) ?: []);

    expect($run->getExitCode())->toBe(0, $run->getErrorOutput())
        ->and($commands)->toBe([['static', ['check:static']], ['js', ['test']], ['php', ['test', '--compact']]])
        ->and(is_file("{$repo}/.git/ezdev/checked"))->toBeTrue()
        ->and(is_file("{$repo}/.git/ezdev/milestone-checked"))->toBeFalse();
});

test('the full runner stops at a failing phase without recording a pass', function (string $phase) {
    $repo = checkRunFixture();
    $run = checkRunProcess($repo, 'full', ['MILESTONE_TEST_FAIL' => $phase]);
    $run->run();
    $commands = file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES) ?: [];

    expect($run->getExitCode())->toBe(7)
        ->and($commands)->toHaveCount(array_search($phase, ['static', 'js', 'php'], true) + 1)
        ->and(is_file("{$repo}/.git/ezdev/checked"))->toBeFalse();
})->with(['static', 'js', 'php']);

test('the full runner requires the end of suite proof', function () {
    $repo = checkRunFixture();
    $run = checkRunProcess($repo, 'full', ['CHECK_NO_SENTINEL' => '1']);
    $run->run();

    expect($run->getExitCode())->toBe(1)
        ->and($run->getErrorOutput())->toContain('the tests did not run to their end')
        ->and(is_file("{$repo}/.git/ezdev/checked"))->toBeFalse();
});

test('the full runner cannot borrow another run end of suite proof', function (bool $legacy) {
    $repo = checkRunFixture();
    $a = checkRunProcess($repo, 'full', ['CHECK_BLOCK_STATIC' => '1', 'CHECK_NO_SENTINEL' => '1']);
    $a->start();
    expect($a->waitUntil(fn ($type, $out) => str_contains($out, 'STATIC_READY')))->toBeTrue();
    try {
        mkdir("{$repo}/storage/framework/testing", 0777, true);
        // A foreign test invocation finishes after this full check started.
        $proof = $legacy ? (string) microtime(true) : json_encode(['id' => str_repeat('f', 32), 'time' => microtime(true)]);
        file_put_contents("{$repo}/storage/framework/testing/ezdev-tests-finished", $proof);
    } finally {
        file_put_contents("{$repo}/runtime/release", 'go');
        $a->wait();
    }
    expect($a->getExitCode())->toBe(1)
        ->and(is_file("{$repo}/.git/ezdev/checked"))->toBeFalse();
})->with(['legacy timestamp' => [true], 'foreign run' => [false]]);

test('overlapping checks cannot overwrite the active run', function (string $first, string $second) {
    $repo = checkRunFixture();
    $a = checkRunStartBlocked($repo, $first);
    try {
        $prefix = $first === 'full' ? '' : 'milestone-';
        $before = file_get_contents("{$repo}/.git/ezdev/{$prefix}checking.json");
        $b = checkRunProcess($repo, $second);
        $b->run();
        expect($b->getExitCode())->toBe(1)
            ->and($b->getErrorOutput())->toContain('already running')
            ->and(file_get_contents("{$repo}/.git/ezdev/{$prefix}checking.json"))->toBe($before);
    } finally {
        file_put_contents("{$repo}/runtime/release", 'go');
        $a->wait();
    }
    expect($a->getExitCode())->toBe(0, $a->getErrorOutput());
})->with([['milestone', 'milestone'], ['full', 'full'], ['full', 'milestone'], ['milestone', 'full']]);

test('an invalid overlapping retry cannot certify code edited during a run', function () {
    $repo = checkRunFixture();
    $a = checkRunStartBlocked($repo);
    try {
        file_put_contents("{$repo}/app/quote.php", "<?php\n\$broken = ;\n");
        $b = milestoneRun($repo, []);
        expect($b->getExitCode())->toBe(1);
    } finally {
        file_put_contents("{$repo}/runtime/release", 'go');
        $a->wait();
    }
    expect($a->getExitCode())->toBe(1)
        ->and(is_file("{$repo}/.git/ezdev/milestone-checked"))->toBeFalse()
        ->and(milestoneHook($repo, 'stop'))->toContain('Run composer check:milestone');
});

test('a completion without matching ownership cannot publish the active run', function (string|false $id) {
    $repo = checkRunFixture();
    $a = checkRunStartBlocked($repo);
    try {
        $wrong = new Process([PHP_BINARY, 'scripts/hooks/finish-check.php', 'milestone-checked'], $repo, ['EZDEV_CHECK_RUN_ID' => $id]);
        $wrong->run();
        expect($wrong->getExitCode())->toBe(1)
            ->and(is_file("{$repo}/.git/ezdev/milestone-checked"))->toBeFalse();
    } finally {
        file_put_contents("{$repo}/runtime/release", 'go');
        $a->wait();
    }
    expect($a->getExitCode())->toBe(0);
})->with(['foreign' => [str_repeat('f', 32)], 'missing' => [false]]);

test('terminating a check releases its lock without certifying it', function () {
    $repo = checkRunFixture();
    $a = checkRunStartBlocked($repo);
    try {
        $a->stop(0, 9);
        expect(is_file("{$repo}/.git/ezdev/milestone-checked"))->toBeFalse();
        $busy = checkRunProcess($repo);
        $busy->run();
        expect($busy->getExitCode())->toBe(1)
            ->and($busy->getErrorOutput())->toContain('already running');
    } finally {
        file_put_contents("{$repo}/runtime/release", 'go');
    }
    $lock = fopen("{$repo}/.git/ezdev/check.lock", 'c+');
    $until = microtime(true) + 10;
    try {
        while (! flock($lock, LOCK_EX | LOCK_NB)) {
            if (microtime(true) > $until) {
                throw new RuntimeException('The child exited but the validation lock was not released.');
            }
            usleep(10000);
        }
    } finally {
        fclose($lock);
    }
    $retry = checkRunProcess($repo);
    $retry->run();
    expect($retry->getExitCode())->toBe(0, $retry->getErrorOutput());
});

test('a legacy fingerprint alone cannot establish a fresh validation pass', function () {
    $repo = checkRunFixture();
    expect(milestoneRun($repo)->getExitCode())->toBe(0);
    $path = "{$repo}/.git/ezdev/milestone-checked";
    $raw = file_get_contents($path);
    $record = json_decode($raw, true);
    file_put_contents($path, is_array($record) ? $record['fingerprint'] : $raw);

    expect(milestoneHook($repo, 'stop'))->toContain('Run composer check:milestone');
});

test('another worktree can validate while the first is busy', function () {
    $repo = checkRunFixture();
    $other = $repo.'-other';
    (new Process(['git', 'worktree', 'add', '--detach', $other, 'HEAD'], $repo))->mustRun();
    mkdir("{$other}/vendor");
    mkdir("{$other}/runtime");
    copy("{$repo}/vendor/autoload.php", "{$other}/vendor/autoload.php");
    $a = checkRunStartBlocked($repo);
    try {
        $b = checkRunProcess($other);
        $b->run();
        expect($b->getExitCode())->toBe(0, $b->getErrorOutput());
    } finally {
        file_put_contents("{$repo}/runtime/release", 'go');
        $a->wait();
        (new Process(['git', 'worktree', 'remove', '--force', $other], $repo))->mustRun();
    }
    expect($a->getExitCode())->toBe(0);
});
