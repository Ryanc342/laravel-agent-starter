<?php

use Symfony\Component\Process\Process;

/*
| Throwaway folders and git repositories for the tests of the template's tools
| (template/tests). Every call that cannot do its job stops the test: a
| fixture that was not written would let a test pass without testing anything.
*/

/**
 * A new empty folder in the system's temp folder, removed when the run ends.
 * Its real path: macOS reaches the temp folder through a symlink.
 */
function workspaceFolder(string $name): string
{
    $folder = sys_get_temp_dir().'/ezdev-'.$name.'-'.bin2hex(random_bytes(6));
    mkdir($folder, 0777, true) ?: throw new RuntimeException("cannot make {$folder}");
    $folder = realpath($folder) ?: throw new RuntimeException("cannot resolve {$folder}");
    // Removed after every other shutdown function (a function registered during
    // shutdown runs last), so the servers a test started are stopped first.
    register_shutdown_function(fn () => register_shutdown_function(fn () => workspaceRemove($folder)));

    return $folder;
}

/**
 * A new folder holding a copy of this project's workflow tool
 * (scripts/workflow.php) and its cards, for the tests that run it.
 */
function workspaceWithWorkflow(string $name): string
{
    $folder = workspaceFolder($name);
    $root = dirname(__DIR__, 3);
    workspaceCopy($folder, ['scripts/workflow.php', '.claude/rules/skeleton.md', ...array_map(fn (string $card): string => substr($card, strlen($root) + 1), glob("{$root}/.claude/rules/skeleton/*.md") ?: throw new RuntimeException('no cards to copy'))]);

    return $folder;
}

/**
 * Writes a file under $root, making its folders; with $time, dates it then.
 */
function workspaceWrite(string $root, string $path, string $content, ?int $time = null): void
{
    if (! is_dir(dirname("{$root}/{$path}")) && ! mkdir(dirname("{$root}/{$path}"), 0777, true)) {
        throw new RuntimeException("cannot make the folder of {$path}");
    }

    if (file_put_contents("{$root}/{$path}", $content) === false || ($time !== null && ! touch("{$root}/{$path}", $time))) {
        throw new RuntimeException("cannot write {$path}");
    }
}

/**
 * Copies files of this project into $root, at the same paths.
 *
 * @param  list<string>  $paths
 */
function workspaceCopy(string $root, array $paths): void
{
    foreach ($paths as $path) {
        $source = dirname(__DIR__, 3)."/{$path}";
        $content = file_get_contents($source);
        workspaceWrite($root, $path, $content === false ? throw new RuntimeException("cannot read {$path} to copy") : $content);
        chmod("{$root}/{$path}", fileperms($source) & 0777) ?: throw new RuntimeException("cannot set the mode of {$path}");
    }
}

/**
 * Removes a file, or a folder with everything in it.
 */
function workspaceRemove(string $path): void
{
    if (is_link($path) || is_file($path)) {
        unlink($path) ?: throw new RuntimeException("cannot remove {$path}");

        return;
    }

    if (! is_dir($path)) {
        return;
    }

    foreach (array_diff(scandir($path) ?: throw new RuntimeException("cannot list {$path}"), ['.', '..']) as $name) {
        workspaceRemove("{$path}/{$name}");
    }

    rmdir($path) ?: throw new RuntimeException("cannot remove {$path}");
}

/**
 * Makes $root a git repository on branch main and commits all its files.
 */
function workspaceGitInit(string $root, ?int $time = null): void
{
    (new Process(['git', 'init', '-q', '-b', 'main'], $root))->mustRun();
    workspaceCommit($root, $time);
}

/**
 * Commits every file of the repository; with $time, dates the commit then.
 */
function workspaceCommit(string $repo, ?int $time = null): void
{
    $date = $time === null ? [] : ['GIT_COMMITTER_DATE' => "@{$time}", 'GIT_AUTHOR_DATE' => "@{$time}"];

    // Settings from the user's own git config (signing, hooks) must not reach these commits.
    foreach ([['add', '-A'], ['-c', 'user.name=t', '-c', 'user.email=t@t', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null', 'commit', '-q', '-m', 'work']] as $args) {
        (new Process(['git', ...$args], $repo, $date))->mustRun();
    }
}

/**
 * Puts a git in front of the real one in $root/bin: it runs the shell lines
 * $script first, then the real git. Returns the PATH that finds it first.
 */
function workspaceFakeGit(string $root, string $script): string
{
    $real = trim((new Process(['which', 'git']))->mustRun()->getOutput());
    workspaceWrite($root, 'bin/git', "#!/bin/sh\n{$script}\nexec {$real} \"\$@\"\n");
    chmod("{$root}/bin/git", 0755) ?: throw new RuntimeException('cannot make bin/git runnable');

    return "{$root}/bin:".getenv('PATH');
}

/**
 * Runs scripts/workflow.php in $repo, with $input on stdin when given, and
 * returns the process after it ended.
 *
 * @param  list<string>  $arguments
 * @param  array<string, string>  $env
 */
function workspaceWorkflow(string $repo, array $arguments, array $env = [], ?string $input = null): Process
{
    $process = new Process([PHP_BINARY, 'scripts/workflow.php', ...$arguments], $repo, $env === [] ? null : $env);

    if ($input !== null) {
        $process->setInput($input);
    }

    $process->run();

    return $process;
}

/**
 * Runs one of the workflow tool's hooks in $repo with $input as Claude Code
 * sends it and returns its answer ([] when it prints nothing). A hook answers
 * with exit 0 and nothing or one JSON object; anything else stops the test.
 *
 * @param  array<string, mixed>  $input
 * @param  array<string, string>  $env
 * @return array<string, mixed>
 */
function workspaceHook(string $repo, string $event, array $input, array $env = []): array
{
    $process = workspaceWorkflow($repo, ['hook', $event], $env, json_encode($input, JSON_THROW_ON_ERROR));

    if ($process->getExitCode() !== 0) {
        throw new RuntimeException("The {$event} hook failed: {$process->getErrorOutput()}");
    }

    return $process->getOutput() === '' ? [] : json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
}
