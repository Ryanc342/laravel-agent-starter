<?php

use Symfony\Component\Process\Process;

require_once __DIR__.'/check-run.php';

/** @param callable(): list<list<string>> $commands */
function runValidation(callable $commands, bool $milestone = false): int
{
    $run = null;
    try {
        $git = new Process(['git', 'rev-parse', '--path-format=absolute', '--git-path', 'ezdev']);
        $git->run();
        // Installer/reference copies may not be Git repositories: execute every check, without recording a pass.
        if ($git->isSuccessful()) {
            $run = new CheckRun(trim($git->getOutput()));
        }
        $env = ['EZDEV_CHECK_RUN_ID' => $run?->id ?? false];
        $prefix = $milestone ? 'milestone-' : '';
        $exit = validationCommand([PHP_BINARY, 'scripts/hooks/finish-check.php', $prefix.'checking'], $env);
        if ($exit !== 0) {
            return $exit;
        }
        foreach ([...$commands(), [PHP_BINARY, 'scripts/hooks/finish-check.php', $prefix.'checked']] as $command) {
            $exit = validationCommand($command, $env);
            if ($exit !== 0) {
                return $exit;
            }
        }
        $run?->succeed();

        return 0;
    } catch (Throwable $error) {
        fwrite(STDERR, $error->getMessage().PHP_EOL);

        return 1;
    } finally {
        $run?->close();
    }
}

/** @return list<string> */
function validationStaticCommand(): array
{
    $composer = getenv('COMPOSER_BINARY');

    return is_string($composer) && is_file($composer) ? [PHP_BINARY, $composer, 'check:static'] : ['composer', 'check:static'];
}

/** @param list<string> $command
 * @param  array<string, string|false>  $env
 */
function validationCommand(array $command, array $env): int
{
    $process = new Process($command, env: $env);
    $process->setTimeout(null);

    return $process->run(function (string $type, string $output): void {
        fwrite($type === Process::ERR ? STDERR : STDOUT, $output);
    });
}
