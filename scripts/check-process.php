<?php

use Symfony\Component\Process\Process;

/**
 * Runs a check (composer check or composer check:milestone): it forgets the last
 * pass, takes the fingerprint of the code, runs every command, and records the
 * pass when they all succeeded and the code is still the same
 * (scripts/hooks/finish-check.php).
 *
 * @param  callable(): list<list<string>>  $commands
 */
function runValidation(callable $commands): int
{
    try {
        // Installer and reference copies may not be git repositories: there the
        // finish check prints nothing and records nothing.
        $exit = validationCommand([PHP_BINARY, 'scripts/hooks/finish-check.php', 'revoke']);
        if ($exit !== 0) {
            return $exit;
        }
        $fingerprint = finishCheckOutput('fingerprint');
        foreach ($commands() as $command) {
            $exit = validationCommand($command);
            if ($exit !== 0) {
                return $exit;
            }
        }

        return $fingerprint === '' ? 0 : validationCommand([PHP_BINARY, 'scripts/hooks/finish-check.php', 'passed', $fingerprint]);
    } catch (Throwable $error) {
        fwrite(STDERR, $error->getMessage().PHP_EOL);

        return 1;
    }
}

function finishCheckOutput(string $mode): string
{
    $process = new Process([PHP_BINARY, 'scripts/hooks/finish-check.php', $mode]);
    $process->mustRun();

    return trim($process->getOutput());
}

/** @return list<string> */
function validationStaticCommand(): array
{
    $composer = getenv('COMPOSER_BINARY');

    return is_string($composer) && is_file($composer) ? [PHP_BINARY, $composer, 'check:static'] : ['composer', 'check:static'];
}

/** @param list<string> $command */
function validationCommand(array $command): int
{
    $process = new Process($command);
    $process->setTimeout(null);

    return $process->run(function (string $type, string $output): void {
        fwrite($type === Process::ERR ? STDERR : STDOUT, $output);
    });
}
