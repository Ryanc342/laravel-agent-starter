<?php

use Symfony\Component\Process\Process;

require_once dirname(__DIR__, 2).'/Support/Workspace.php';

function milestoneWorkspace(): string
{
    $repo = workspaceFolder('milestone');
    $root = dirname(__DIR__, 3);
    workspaceCopy($repo, ['scripts/hooks/finish-check.php', ...array_values(array_filter(['scripts/check-milestone.php', 'scripts/check.php', 'scripts/check-process.php'], fn (string $script) => is_file("{$root}/{$script}")))]);
    workspaceWrite($repo, 'vendor/autoload.php', '<?php require '.var_export("{$root}/vendor/autoload.php", true).';');
    workspaceWrite($repo, '.gitignore', "/vendor/\n/runtime/\n/storage/\n");
    workspaceWrite($repo, 'tests/Feature/QuoteTest.php', "<?php\n");
    workspaceWrite($repo, 'tests/js/quote.test.ts', '');
    workspaceWrite($repo, 'app/quote.php', "<?php\n");
    $stub = <<<'PHP'
<?php
$phase = match (basename(__FILE__)) { 'composer.php' => 'static', 'artisan' => 'php', 'pest' => 'template', default => 'js' };
file_put_contents(getcwd().'/runtime/commands', json_encode([$phase, array_slice($argv, 1)])."\n", FILE_APPEND);
if (getenv('MILESTONE_TEST_EDIT') === $phase) {
    file_put_contents(getcwd().'/app/quote.php', "<?php\n\$edited = true;\n");
}
exit(getenv('MILESTONE_TEST_FAIL') === $phase ? 7 : 0);
PHP;
    workspaceWrite($repo, 'composer.php', $stub);
    workspaceWrite($repo, 'artisan', $stub);
    workspaceWrite($repo, 'bin/npm', '#!'.PHP_BINARY."\n".$stub);
    chmod("{$repo}/bin/npm", 0755) ?: throw new RuntimeException('cannot make bin/npm runnable');
    workspaceWrite($repo, 'template/tests/QuoteToolTest.php', "<?php\n");
    workspaceWrite($repo, 'vendor/bin/pest', $stub);
    workspaceGitInit($repo);
    workspaceWrite($repo, 'runtime/.keep', '');
    // The conversation starts now (its transcript); the code changes in it.
    workspaceWrite($repo, 'runtime/transcript.jsonl', json_encode(['timestamp' => gmdate('Y-m-d\TH:i:s.000\Z')])."\n");
    workspaceWrite($repo, 'app/quote.php', "<?php\n\$sent = true;\n");

    return $repo;
}

/**
 * The Stop hook's answer: why it sends the AI back, or '' when it says nothing
 * and the AI may stop. Any other answer (the hook's own error) fails the test.
 */
function milestoneStop(string $repo): string
{
    $process = new Process([PHP_BINARY, 'scripts/hooks/finish-check.php', 'stop'], $repo);
    $process->setInput(json_encode(['session_id' => 'milestone', 'cwd' => $repo, 'transcript_path' => "{$repo}/runtime/transcript.jsonl"]) ?: '');
    $process->mustRun();

    if ($process->getOutput() === '') {
        return '';
    }

    $answer = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

    return is_array($answer) && ($answer['decision'] ?? null) === 'block' && is_string($answer['reason'] ?? null)
        ? $answer['reason']
        : throw new RuntimeException("The Stop hook gave another answer: {$process->getOutput()}");
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

function milestoneFullRun(string $repo, array $env = []): Process
{
    $process = new Process([PHP_BINARY, 'scripts/check.php'], $repo, [
        'PATH' => "{$repo}/bin:".getenv('PATH'), 'COMPOSER_BINARY' => "{$repo}/composer.php", ...$env,
    ]);
    $process->run();

    return $process;
}

test('milestone validation runs all static checks and the selected PHP and frontend scopes, and its pass lets the AI stop', function () {
    $repo = milestoneWorkspace();
    expect(milestoneStop($repo))->toContain('composer check:milestone');

    $run = milestoneRun($repo, ['tests/Feature', 'tests/js/quote.test.ts']);

    expect($run->getExitCode())->toBe(0)
        ->and(milestoneStop($repo))->toBe('');
    $commands = array_map(fn (string $line) => json_decode($line, true), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES));
    expect($commands)->toBe([
        ['static', ['check:static']],
        ['php', ['test', '--compact', '--fail-on-empty-test-suite', 'tests/Feature']],
        ['js', ['test', '--', 'tests/js/quote.test.ts']],
    ]);
});

test('a full composer check runs the static checks and both test suites, and its pass lets the AI stop', function () {
    $repo = milestoneWorkspace();

    expect(milestoneFullRun($repo)->getExitCode())->toBe(0)
        ->and(milestoneStop($repo))->toBe('');
    $commands = array_map(fn (string $line) => json_decode($line, true), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES));
    expect(array_column($commands, 0))->toBe(['static', 'js', 'php']);
});

test('milestone validation rejects an empty or invalid scope before executing checks, and revokes an earlier pass', function (array $paths) {
    $repo = milestoneWorkspace();
    mkdir("{$repo}/tests/Feature/Empty") ?: throw new RuntimeException('cannot make tests/Feature/Empty');
    expect(milestoneRun($repo)->getExitCode())->toBe(0);
    unlink("{$repo}/runtime/commands") ?: throw new RuntimeException('cannot remove runtime/commands');

    $run = milestoneRun($repo, $paths);

    expect($run->getExitCode())->toBe(1)
        ->and(is_file("{$repo}/runtime/commands"))->toBeFalse()
        ->and(milestoneStop($repo))->toContain('composer check:milestone');
})->with([
    'no paths' => [[]],
    'missing file' => [['tests/Feature/MissingTest.php']],
    'not a test' => [['app/quote.php']],
    'empty directory' => [['tests/Feature/Empty']],
    'runner option' => [['--filter=Quote']],
    'parent traversal' => [['tests/Feature/../../app']],
]);

test('a failed command stops the run and revokes an earlier pass', function (string $phase, bool $full) {
    $repo = milestoneWorkspace();
    $paths = ['tests/Feature', 'tests/js'];
    expect(milestoneRun($repo, $paths)->getExitCode())->toBe(0);
    workspaceWrite($repo, 'runtime/commands', '');

    $run = $full ? milestoneFullRun($repo, ['MILESTONE_TEST_FAIL' => $phase]) : milestoneRun($repo, $paths, ['MILESTONE_TEST_FAIL' => $phase]);
    $order = $full ? ['static', 'js', 'php'] : ['static', 'php', 'js'];

    expect($run->getExitCode())->toBe(7)
        ->and(file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES))->toHaveCount(array_search($phase, $order, true) + 1)
        ->and(milestoneStop($repo))->toContain('composer check:milestone');
})->with(['static', 'php', 'js'])->with(['milestone' => [false], 'full' => [true]]);

test('a change during or after the check needs a new check', function (bool $during) {
    $repo = milestoneWorkspace();
    $run = milestoneRun($repo, env: $during ? ['MILESTONE_TEST_EDIT' => 'php'] : []);
    if (! $during) {
        workspaceWrite($repo, 'app/quote.php', "<?php\n\$edited = true;\n");
    }

    expect($run->getExitCode())->toBe($during ? 1 : 0)
        ->and(milestoneStop($repo))->toContain('composer check:milestone');
})->with(['during' => [true], 'after' => [false]]);

test('the template\'s own tests run with their own configuration', function () {
    $repo = milestoneWorkspace();
    expect(milestoneRun($repo, ['template/tests'])->getExitCode())->toBe(0);
    $commands = array_map(fn (string $line) => json_decode($line, true), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES));

    expect($commands)->toBe([
        ['static', ['check:static']],
        ['template', ['-c', 'template/phpunit.xml', '--test-directory=template/tests', '--compact', '--fail-on-empty-test-suite', 'template/tests']],
    ]);
});

test('a frontend-only milestone runs no backend suite', function () {
    $repo = milestoneWorkspace();
    expect(milestoneRun($repo, ['tests/js'])->getExitCode())->toBe(0);
    $commands = array_map(fn (string $line) => json_decode($line, true), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES));

    expect(array_column($commands, 0))->toBe(['static', 'js']);
});

test('a PHP scope with no discovered tests cannot record a pass', function () {
    $repo = milestoneWorkspace();
    // A real configuration avoids Pest's generated-config path when PHP assertions are disabled in CI.
    workspaceWrite($repo, 'phpunit.xml', '<phpunit bootstrap="vendor/autoload.php"/>');
    $artisan = <<<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';
$process = new Symfony\Component\Process\Process([
    PHP_BINARY, '-d', 'zend.assertions=-1', PEST_BINARY, '--configuration', __DIR__.'/phpunit.xml', ...array_slice($argv, 2),
]);
exit($process->run(function ($type, $output) { echo $output; }));
PHP;
    workspaceWrite($repo, 'artisan', str_replace('PEST_BINARY', var_export(dirname(__DIR__, 3).'/vendor/bin/pest', true), $artisan));
    $run = milestoneRun($repo);

    expect($run->getExitCode())->toBe(1, $run->getOutput().$run->getErrorOutput())
        ->and(milestoneStop($repo))->toContain('composer check:milestone');
});
