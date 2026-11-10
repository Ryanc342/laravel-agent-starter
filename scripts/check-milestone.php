<?php

use Symfony\Component\Process\Process;

require __DIR__.'/../vendor/autoload.php';

chdir(dirname(__DIR__));

try {
    runMilestoneCommand([PHP_BINARY, 'scripts/hooks/finish-check.php', 'milestone-checking']);
    $scopes = milestoneTestScopes(array_slice($argv, 1));
    $composer = getenv('COMPOSER_BINARY');
    $staticCommand = is_string($composer) && is_file($composer) ? [PHP_BINARY, $composer, 'check:static'] : ['composer', 'check:static'];
    $commands = [$staticCommand];

    if ($scopes['php'] !== []) {
        $commands[] = [PHP_BINARY, 'artisan', 'test', '--compact', '--fail-on-empty-test-suite', ...$scopes['php']];
    }
    if ($scopes['js'] !== []) {
        $commands[] = ['npm', 'test', '--', ...$scopes['js']];
    }
    $commands[] = [PHP_BINARY, 'scripts/hooks/finish-check.php', 'milestone-checked'];

    foreach ($commands as $command) {
        runMilestoneCommand($command);
    }

    fwrite(STDOUT, 'Milestone checks passed for: '.implode(', ', [...$scopes['php'], ...$scopes['js']]).". Full plan acceptance still requires composer check.\n");
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
}

/** @param list<string> $command */
function runMilestoneCommand(array $command): void
{
    $process = new Process($command);
    $process->setTimeout(null);
    $exit = $process->run(function (string $type, string $output): void {
        fwrite($type === Process::ERR ? STDERR : STDOUT, $output);
    });
    if ($exit !== 0) {
        exit($exit);
    }
}

/**
 * Accept test files/directories, never runner options or paths outside the test suites.
 * Selecting the affected callers is the plan author's responsibility.
 *
 * @param  list<string>  $paths
 * @return array{php: list<string>, js: list<string>}
 */
function milestoneTestScopes(array $paths): array
{
    if ($paths === []) {
        throw new InvalidArgumentException('Usage: composer check:milestone -- <affected test files or directories>');
    }

    $scopes = ['php' => [], 'js' => []];
    $root = str_replace('\\', '/', (string) getcwd()).'/';
    foreach (array_unique($paths) as $path) {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $resolved = str_replace('\\', '/', realpath($path) ?: '');
        if (preg_match('#^tests/(Unit|Feature|Structure|js)(/|$)#', $path, $match) !== 1
            || in_array('..', explode('/', $path), true)
            || (! str_starts_with($resolved, $root.'tests/'.$match[1].'/') && $resolved !== $root.'tests/'.$match[1])) {
            throw new InvalidArgumentException("Invalid test scope: {$path}. Select existing files or directories within tests/Unit, tests/Feature, tests/Structure or tests/js.");
        }
        $kind = $match[1] === 'js' ? 'js' : 'php';
        $pattern = $kind === 'js' ? '/\.test\.tsx?$/' : '/Test\.php$/';
        $hasTests = is_file($path) && preg_match($pattern, $path) === 1;
        if (is_dir($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile() && ! $file->isLink() && preg_match($pattern, $file->getFilename()) === 1) {
                    $hasTests = true;
                    break;
                }
            }
        }
        if (! $hasTests) {
            throw new InvalidArgumentException("Test scope contains no matching tests: {$path}");
        }
        $scopes[$kind][] = $path;
    }

    return $scopes;
}
