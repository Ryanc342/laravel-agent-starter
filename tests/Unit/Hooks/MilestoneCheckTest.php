<?php

use Symfony\Component\Process\Process;

function milestoneWorkspace(): string
{
    $repo = sys_get_temp_dir().'/ezdev-milestone-'.bin2hex(random_bytes(6));
    $root = dirname(__DIR__, 3);
    foreach (['scripts/hooks', 'vendor', 'bin', 'tests/Feature', 'tests/js', 'app', 'docs/exec-plans/active'] as $folder) {
        mkdir("{$repo}/{$folder}", 0777, true);
    }
    copy("{$root}/scripts/hooks/finish-check.php", "{$repo}/scripts/hooks/finish-check.php");
    if (is_file("{$root}/scripts/check-milestone.php")) {
        copy("{$root}/scripts/check-milestone.php", "{$repo}/scripts/check-milestone.php");
    }
    file_put_contents("{$repo}/vendor/autoload.php", '<?php require '.var_export("{$root}/vendor/autoload.php", true).';');
    file_put_contents("{$repo}/.gitignore", "/vendor/\n/runtime/\n");
    file_put_contents("{$repo}/tests/Feature/QuoteTest.php", "<?php\n");
    file_put_contents("{$repo}/tests/js/quote.test.ts", '');
    file_put_contents("{$repo}/app/quote.php", "<?php\n");
    $stub = <<<'PHP'
<?php
$phase = match (basename(__FILE__)) { 'composer.php' => 'static', 'artisan' => 'php', default => 'js' };
file_put_contents(__DIR__.'/'.($phase === 'js' ? '../' : '').'runtime/commands', json_encode([$phase, array_slice($argv, 1)])."\n", FILE_APPEND);
if (getenv('MILESTONE_TEST_EDIT') === $phase) {
    file_put_contents(getcwd().'/app/quote.php', "<?php\n\$edited = true;\n");
}
exit(getenv('MILESTONE_TEST_FAIL') === $phase ? 7 : 0);
PHP;
    file_put_contents("{$repo}/composer.php", $stub);
    file_put_contents("{$repo}/artisan", $stub);
    file_put_contents("{$repo}/bin/npm", '#!'.PHP_BINARY."\n".$stub);
    chmod("{$repo}/bin/npm", 0755);
    foreach ([['init', '-q', '-b', 'main'], ['add', '-A'], ['-c', 'user.name=t', '-c', 'user.email=t@t', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null', 'commit', '-qm', 'fixture']] as $args) {
        (new Process(['git', ...$args], $repo))->mustRun();
    }
    mkdir("{$repo}/runtime");
    milestoneHook($repo, 'start');
    file_put_contents("{$repo}/app/quote.php", "<?php\n\$sent = true;\n");
    milestonePlan($repo);
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $repo]))->run());

    return $repo;
}

function milestonePlan(string $repo, bool $finished = false, bool $archived = false): void
{
    $folder = $archived ? 'completed' : 'active';
    if (! is_dir("{$repo}/docs/exec-plans/{$folder}")) {
        mkdir("{$repo}/docs/exec-plans/{$folder}");
    }
    if ($archived) {
        unlink("{$repo}/docs/exec-plans/active/quotes.md");
    }
    $tick = $finished ? 'x' : ' ';
    file_put_contents("{$repo}/docs/exec-plans/{$folder}/quotes.md", "# Quotes\n\n## Purpose\nSend customer quotes.\n\n## Agreed decisions\n1. Customers receive a quote.\n\n## Milestones\n- [x] M1 Send quotes\n- [{$tick}] M2 View quotes\n\n## Progress\n- M1 affected tests passed.\n\n## Decision Log\n\n## Outcomes & Retrospective\nQuotes can be sent and viewed by the customer.\n");
}

function milestoneHook(string $repo, string $mode): string
{
    $process = new Process([PHP_BINARY, 'scripts/hooks/finish-check.php', $mode], $repo);
    $process->setInput(json_encode(['session_id' => 'milestone', 'cwd' => $repo]) ?: '');
    $process->mustRun();

    return $process->getOutput();
}

/** @param list<string> $paths
 * @param  array<string, string>  $env
 */
function milestoneRun(string $repo, array $paths = ['tests/Feature'], array $env = []): Process
{
    $process = new Process([PHP_BINARY, 'scripts/check-milestone.php', ...$paths], $repo, [
        'PATH' => "{$repo}/bin:".getenv('PATH'),
        'COMPOSER_BINARY' => "{$repo}/composer.php",
        ...$env,
    ]);
    $process->run();

    return $process;
}

test('milestone validation runs all static checks and the selected PHP and frontend scopes without granting final acceptance', function () {
    $repo = milestoneWorkspace();
    $run = milestoneRun($repo, ['tests/Feature', 'tests/js/quote.test.ts']);

    expect($run->getExitCode())->toBe(0)
        ->and(is_file("{$repo}/.git/ezdev/checked"))->toBeFalse()
        ->and(milestoneHook($repo, 'stop'))->toBe('');
    $commands = array_map(fn (string $line) => json_decode($line, true), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES));
    expect($commands)->toBe([
        ['static', ['check:static']],
        ['php', ['test', '--compact', '--fail-on-empty-test-suite', 'tests/Feature']],
        ['js', ['test', '--', 'tests/js/quote.test.ts']],
    ]);

    milestonePlan($repo, finished: true);
    expect(milestoneHook($repo, 'stop'))->toContain('Run composer check')->not->toContain('Run composer check:milestone');
});

test('milestone validation rejects an empty or invalid scope before executing checks', function (array $paths) {
    $repo = milestoneWorkspace();
    mkdir("{$repo}/tests/Feature/Empty");
    $run = milestoneRun($repo, $paths);

    expect($run->getExitCode())->toBe(1)
        ->and(is_file("{$repo}/runtime/commands"))->toBeFalse()
        ->and(milestoneHook($repo, 'stop'))->toContain('Run composer check:milestone');
})->with([
    'no paths' => [[]],
    'missing file' => [['tests/Feature/MissingTest.php']],
    'not a test' => [['app/quote.php']],
    'empty directory' => [['tests/Feature/Empty']],
    'runner option' => [['--filter=Quote']],
    'parent traversal' => [['tests/Feature/../../app']],
]);

test('a failed milestone command stops the run and invalidates an earlier partial pass', function (string $phase) {
    $repo = milestoneWorkspace();
    $paths = ['tests/Feature', 'tests/js'];
    expect(milestoneRun($repo, $paths)->getExitCode())->toBe(0);
    file_put_contents("{$repo}/runtime/commands", '');
    $run = milestoneRun($repo, $paths, ['MILESTONE_TEST_FAIL' => $phase]);
    $commands = file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES);

    expect($run->getExitCode())->toBe(7)
        ->and($commands)->toHaveCount(array_search($phase, ['static', 'php', 'js'], true) + 1)
        ->and(milestoneHook($repo, 'stop'))->toContain('Run composer check:milestone');
})->with(['static', 'php', 'js']);

test('a change during or after milestone validation requires a new check', function (bool $during) {
    $repo = milestoneWorkspace();
    $run = milestoneRun($repo, env: $during ? ['MILESTONE_TEST_EDIT' => 'php'] : []);
    if (! $during) {
        file_put_contents("{$repo}/app/quote.php", "<?php\n\$edited = true;\n");
    }

    expect($run->getExitCode())->toBe($during ? 1 : 0)
        ->and(milestoneHook($repo, 'stop'))->toContain('Run composer check:milestone');
})->with(['during' => [true], 'after' => [false]]);

test('a partial pass cannot replace final validation without an incomplete valid active plan', function (string $state) {
    $repo = milestoneWorkspace();
    expect(milestoneRun($repo)->getExitCode())->toBe(0);
    match ($state) {
        'archived' => milestonePlan($repo, finished: true, archived: true),
        'missing' => unlink("{$repo}/docs/exec-plans/active/quotes.md"),
        'invalid' => file_put_contents("{$repo}/docs/exec-plans/active/quotes.md", "# Empty\n## Milestones\n- [ ] M1 Build\n"),
    };

    expect(milestoneHook($repo, 'stop'))->toContain('Run composer check')->not->toContain('Run composer check:milestone');
})->with(['archived', 'missing', 'invalid']);

test('partial validation preserves protected-file approval requirements', function () {
    $repo = milestoneWorkspace();
    file_put_contents("{$repo}/composer.json", '{}');
    expect(milestoneRun($repo)->getExitCode())->toBe(0)
        ->and(milestoneHook($repo, 'stop'))->toContain('You changed composer.json');
});

test('a milestone result in an archived plan is not a recorded full check result', function () {
    $repo = milestoneWorkspace();
    milestonePlan($repo, finished: true, archived: true);
    $path = "{$repo}/docs/exec-plans/completed/quotes.md";
    file_put_contents($path, str_replace('M1 affected tests passed.', 'composer check:milestone passed; review round 1: 0 P0, 0 P1.', file_get_contents($path)));

    expect(milestoneHook($repo, 'stop'))->toContain('no composer check result in Progress');
});

test('a frontend-only milestone runs no backend suite', function () {
    $repo = milestoneWorkspace();
    expect(milestoneRun($repo, ['tests/js'])->getExitCode())->toBe(0);
    $commands = array_map(fn (string $line) => json_decode($line, true), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES));

    expect(array_column($commands, 0))->toBe(['static', 'js']);
});

test('a PHP scope with no discovered tests cannot record a milestone pass', function () {
    $repo = milestoneWorkspace();
    // A real configuration avoids Pest's generated-config path when PHP assertions are disabled in CI.
    file_put_contents("{$repo}/phpunit.xml", '<phpunit bootstrap="vendor/autoload.php"/>');
    $artisan = <<<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';
$process = new Symfony\Component\Process\Process([
    PHP_BINARY, '-d', 'zend.assertions=-1', PEST_BINARY, '--configuration', __DIR__.'/phpunit.xml', ...array_slice($argv, 2),
]);
exit($process->run(function ($type, $output) { echo $output; }));
PHP;
    file_put_contents("{$repo}/artisan", str_replace('PEST_BINARY', var_export(dirname(__DIR__, 3).'/vendor/bin/pest', true), $artisan));
    $run = milestoneRun($repo);

    expect($run->getExitCode())->toBe(1, $run->getOutput().$run->getErrorOutput())
        ->and(is_file("{$repo}/.git/ezdev/milestone-checked"))->toBeFalse();
});
