<?php

use Symfony\Component\Process\Process;

function milestoneWorkspace(): string
{
    $repo = sys_get_temp_dir().'/ezdev-milestone-'.bin2hex(random_bytes(6));
    $root = dirname(__DIR__, 2);
    foreach (['scripts/hooks', 'vendor/bin', 'bin', 'tests/Feature', 'tests/js', 'template/tests', 'app'] as $folder) {
        mkdir("{$repo}/{$folder}", 0777, true);
    }
    copy("{$root}/scripts/hooks/finish-check.php", "{$repo}/scripts/hooks/finish-check.php");
    foreach (['check-milestone.php', 'check.php', 'check-process.php'] as $script) {
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
$phase = match (basename(__FILE__)) { 'composer.php' => 'static', 'artisan' => 'php', 'pest' => 'template', default => 'js' };
file_put_contents(getcwd().'/runtime/commands', json_encode([$phase, array_slice($argv, 1)])."\n", FILE_APPEND);
if (getenv('MILESTONE_TEST_EDIT') === $phase) {
    file_put_contents(getcwd().'/app/quote.php', "<?php\n\$edited = true;\n");
}
exit(getenv('MILESTONE_TEST_FAIL') === $phase ? 7 : 0);
PHP;
    file_put_contents("{$repo}/composer.php", $stub);
    file_put_contents("{$repo}/artisan", $stub);
    file_put_contents("{$repo}/bin/npm", '#!'.PHP_BINARY."\n".$stub);
    chmod("{$repo}/bin/npm", 0755);
    file_put_contents("{$repo}/template/tests/QuoteToolTest.php", "<?php\n");
    file_put_contents("{$repo}/vendor/bin/pest", $stub);
    foreach ([['init', '-q', '-b', 'main'], ['add', '-A'], ['-c', 'user.name=t', '-c', 'user.email=t@t', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null', 'commit', '-qm', 'fixture']] as $args) {
        (new Process(['git', ...$args], $repo))->mustRun();
    }
    mkdir("{$repo}/runtime");
    // The conversation starts now (its transcript); the code changes in it.
    file_put_contents("{$repo}/runtime/transcript.jsonl", json_encode(['timestamp' => gmdate('Y-m-d\TH:i:s.000\Z')])."\n");
    file_put_contents("{$repo}/app/quote.php", "<?php\n\$sent = true;\n");
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $repo]))->run());

    return $repo;
}

/** The Stop hook's answer: what it sends back, or '' when the AI may stop. */
function milestoneStop(string $repo): string
{
    $process = new Process([PHP_BINARY, 'scripts/hooks/finish-check.php', 'stop'], $repo);
    $process->setInput(json_encode(['session_id' => 'milestone', 'cwd' => $repo, 'transcript_path' => "{$repo}/runtime/transcript.jsonl"]) ?: '');
    $process->mustRun();

    return json_decode($process->getOutput() ?: '{}', true)['reason'] ?? '';
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
