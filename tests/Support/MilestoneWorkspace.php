<?php

use Symfony\Component\Process\Process;

function milestoneWorkspace(): string
{
    $repo = sys_get_temp_dir().'/ezdev-milestone-'.bin2hex(random_bytes(6));
    $root = dirname(__DIR__, 2);
    foreach (['scripts/hooks', 'vendor', 'bin', 'tests/Feature', 'tests/js', 'app', 'docs/exec-plans/active'] as $folder) {
        mkdir("{$repo}/{$folder}", 0777, true);
    }
    copy("{$root}/scripts/hooks/finish-check.php", "{$repo}/scripts/hooks/finish-check.php");
    foreach (['check-milestone.php', 'check.php', 'check-run.php', 'check-process.php'] as $script) {
        if (is_file("{$root}/scripts/{$script}")) {
            copy("{$root}/scripts/{$script}", "{$repo}/scripts/{$script}");
        }
    }
    file_put_contents("{$repo}/vendor/autoload.php", '<?php require '.var_export("{$root}/vendor/autoload.php", true).';');
    file_put_contents("{$repo}/.gitignore", "/vendor/\n/runtime/\n/storage/\n");
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
if ($phase === 'php' && getenv('CHECK_NO_SENTINEL') !== '1') {
    @mkdir(getcwd().'/storage/framework/testing', 0777, true);
    file_put_contents(getcwd().'/storage/framework/testing/ezdev-tests-finished', (string) microtime(true));
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

function milestoneFullRun(string $repo, array $env = []): Process
{
    $process = new Process([PHP_BINARY, 'scripts/check.php'], $repo, [
        'PATH' => "{$repo}/bin:".getenv('PATH'), 'COMPOSER_BINARY' => "{$repo}/composer.php", ...$env,
    ]);
    $process->run();

    return $process;
}
