<?php

use Symfony\Component\Process\Process;

/** Writes a fixture file; a write that fails stops the test. */
function milestoneWrite(string $path, string $content): void
{
    if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0777, true)) {
        throw new RuntimeException("cannot make the folder of {$path}");
    }

    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException("cannot write {$path}");
    }
}

function milestoneWorkspace(): string
{
    $repo = sys_get_temp_dir().'/ezdev-milestone-'.bin2hex(random_bytes(6));
    $root = dirname(__DIR__, 2);
    foreach (['scripts/hooks', 'vendor/bin', 'bin', 'tests/Feature', 'tests/js', 'template/tests', 'app'] as $folder) {
        mkdir("{$repo}/{$folder}", 0777, true) ?: throw new RuntimeException("cannot make {$folder}");
    }
    copy("{$root}/scripts/hooks/finish-check.php", "{$repo}/scripts/hooks/finish-check.php") ?: throw new RuntimeException("cannot copy the finish check");
    foreach (['check-milestone.php', 'check.php', 'check-process.php'] as $script) {
        if (is_file("{$root}/scripts/{$script}")) {
            copy("{$root}/scripts/{$script}", "{$repo}/scripts/{$script}");
        }
    }
    milestoneWrite("{$repo}/vendor/autoload.php", '<?php require '.var_export("{$root}/vendor/autoload.php", true).';');
    milestoneWrite("{$repo}/.gitignore", "/vendor/\n/runtime/\n/storage/\n");
    milestoneWrite("{$repo}/tests/Feature/QuoteTest.php", "<?php\n");
    milestoneWrite("{$repo}/tests/js/quote.test.ts", '');
    milestoneWrite("{$repo}/app/quote.php", "<?php\n");
    $stub = <<<'PHP'
<?php
$phase = match (basename(__FILE__)) { 'composer.php' => 'static', 'artisan' => 'php', 'pest' => 'template', default => 'js' };
file_put_contents(getcwd().'/runtime/commands', json_encode([$phase, array_slice($argv, 1)])."\n", FILE_APPEND);
if (getenv('MILESTONE_TEST_EDIT') === $phase) {
    file_put_contents(getcwd().'/app/quote.php', "<?php\n\$edited = true;\n");
}
exit(getenv('MILESTONE_TEST_FAIL') === $phase ? 7 : 0);
PHP;
    milestoneWrite("{$repo}/composer.php", $stub);
    milestoneWrite("{$repo}/artisan", $stub);
    milestoneWrite("{$repo}/bin/npm", '#!'.PHP_BINARY."\n".$stub);
    chmod("{$repo}/bin/npm", 0755);
    milestoneWrite("{$repo}/template/tests/QuoteToolTest.php", "<?php\n");
    milestoneWrite("{$repo}/vendor/bin/pest", $stub);
    foreach ([['init', '-q', '-b', 'main'], ['add', '-A'], ['-c', 'user.name=t', '-c', 'user.email=t@t', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null', 'commit', '-qm', 'fixture']] as $args) {
        (new Process(['git', ...$args], $repo))->mustRun();
    }
    milestoneWrite("{$repo}/runtime/.keep", '');
    // The conversation starts now (its transcript); the code changes in it.
    milestoneWrite("{$repo}/runtime/transcript.jsonl", json_encode(['timestamp' => gmdate('Y-m-d\TH:i:s.000\Z')])."\n");
    milestoneWrite("{$repo}/app/quote.php", "<?php\n\$sent = true;\n");
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $repo]))->run());

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
