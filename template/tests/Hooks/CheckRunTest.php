<?php

use Symfony\Component\Process\Process;

require_once dirname(__DIR__).'/Support/Workspace.php';

/*
| composer check (scripts/check.php and scripts/check-process.php) in a
| throwaway repository whose commands are stubs that record their calls: it
| runs every command in order, stops at the first failure, and records its
| pass for the workflow tool only for the code it started on.
*/

function checkRunWorkspace(): string
{
    $repo = workspaceFolder('check-run');
    $root = dirname(__DIR__, 3);
    workspaceCopy($repo, ['scripts/workflow.php', 'scripts/check.php', 'scripts/check-process.php']);
    workspaceWrite($repo, 'vendor/autoload.php', '<?php require '.var_export("{$root}/vendor/autoload.php", true).';');
    workspaceWrite($repo, '.gitignore', "/vendor/\n/runtime/\n/storage/\n");
    workspaceWrite($repo, 'tests/Feature/QuoteTest.php', "<?php\n\ntest('a quote', function () {})->group('quotes.D1');\n");
    workspaceWrite($repo, 'app/quote.php', "<?php\n");
    $stub = <<<'PHP'
<?php
$phase = match (basename(__FILE__)) { 'composer.php' => 'static', 'artisan' => 'php', default => 'js' };
// A record or an edit that cannot be written fails the command, so no test reads a missing record as "nothing ran".
if (file_put_contents(getcwd().'/runtime/commands', json_encode([$phase, array_slice($argv, 1)])."\n", FILE_APPEND) === false) {
    fwrite(STDERR, "the stub cannot record its call\n");
    exit(9);
}
if (getenv('CHECK_TEST_EDIT') === $phase && file_put_contents(getcwd().'/app/quote.php', "<?php\n\$edited = true;\n") === false) {
    fwrite(STDERR, "the stub cannot edit app/quote.php\n");
    exit(9);
}
exit(getenv('CHECK_TEST_FAIL') === $phase ? 7 : 0);
PHP;
    workspaceWrite($repo, 'composer.php', $stub);
    workspaceWrite($repo, 'artisan', $stub);
    workspaceWrite($repo, 'bin/npm', '#!'.PHP_BINARY."\n".$stub);
    chmod("{$repo}/bin/npm", 0755) ?: throw new RuntimeException('cannot make bin/npm runnable');
    workspaceGitInit($repo);
    workspaceWrite($repo, 'runtime/.keep', '');
    // An approved plan whose one decision has its test: the check is the next step.
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', "# Quotes\n\n## Purpose\n\nQuotes.\n\n## Questions\n\nNone.\n\n## Decisions\n\nD1 A quote is sent.\n\n## Screens\n\nNone.\n");
    workspaceHook($repo, 'user-prompt', ['cwd' => $repo, 'prompt' => '开始']);
    workspaceWrite($repo, 'app/quote.php', "<?php\n\$sent = true;\n");

    return $repo;
}

/**
 * The step the workflow tool says the plan is at.
 */
function checkRunStep(string $repo): string
{
    $process = workspaceWorkflow($repo, ['status']);

    return preg_match('/^At step (\w+)\./', $process->getOutput(), $step) === 1 ? $step[1] : throw new RuntimeException("no step: {$process->getOutput()}{$process->getErrorOutput()}");
}

/**
 * @param  array<string, string>  $env
 */
function checkRun(string $repo, array $env = []): Process
{
    $process = new Process([PHP_BINARY, 'scripts/check.php'], $repo, [
        'PATH' => "{$repo}/bin:".getenv('PATH'), 'COMPOSER_BINARY' => "{$repo}/composer.php", ...$env,
    ]);
    $process->run();

    return $process;
}

/**
 * @return list<array{string, list<string>}>
 */
function checkRunCommands(string $repo): array
{
    return array_map(fn (string $line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES) ?: []);
}

test('composer check runs the static checks, the frontend tests, the build the browser tests open and the PHP tests, and its pass is the plan\'s check', function () {
    $repo = checkRunWorkspace();

    expect(checkRunStep($repo))->toBe('built')
        ->and(checkRun($repo)->getExitCode())->toBe(0)
        ->and(checkRunStep($repo))->toBe('checked')
        ->and(checkRunCommands($repo))->toBe([
            ['static', ['check:static']],
            ['js', ['test']],
            ['js', ['run', 'build']],
            ['php', ['test', '--compact']],
        ]);
});

test('a failed command stops the run and revokes an earlier pass', function (string $phase, int $ran) {
    $repo = checkRunWorkspace();
    expect(checkRun($repo)->getExitCode())->toBe(0);
    workspaceWrite($repo, 'runtime/commands', '');

    $run = checkRun($repo, ['CHECK_TEST_FAIL' => $phase]);

    expect($run->getExitCode())->toBe(7)
        ->and(checkRunCommands($repo))->toHaveCount($ran)
        ->and(checkRunStep($repo))->toBe('built');
})->with(['static' => ['static', 1], 'frontend tests' => ['js', 2], 'PHP tests' => ['php', 4]]);

test('a change during or after the check needs a new check', function (bool $during) {
    $repo = checkRunWorkspace();
    $run = checkRun($repo, $during ? ['CHECK_TEST_EDIT' => 'php'] : []);

    if (! $during) {
        workspaceWrite($repo, 'app/quote.php', "<?php\n\$edited = true;\n");
    }

    expect($run->getExitCode())->toBe($during ? 1 : 0)
        ->and(checkRunStep($repo))->toBe('built');
})->with(['during' => [true], 'after' => [false]]);

test('the template\'s own tests run last, with their own configuration', function () {
    $repo = checkRunWorkspace();
    workspaceWrite($repo, 'template/phpunit.xml', "<phpunit/>\n");
    workspaceWrite($repo, 'vendor/bin/pest', "<?php\n".'file_put_contents(getcwd()."/runtime/commands", json_encode(["template", array_slice($argv, 1)])."\n", FILE_APPEND) !== false || exit(9);'."\n");

    expect(checkRun($repo)->getExitCode())->toBe(0)
        ->and(checkRunCommands($repo)[4])->toBe(['template', ['-c', 'template/phpunit.xml', '--test-directory=template/tests', '--compact']]);
});
