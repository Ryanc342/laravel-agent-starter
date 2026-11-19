<?php

use Symfony\Component\Process\Process;

/*
| scripts/hooks/finish-check.php, run the way Claude Code runs the Stop hook, in
| a throwaway git repository: each test starts a conversation (its transcript),
| changes files and reads what the script prints when the AI is about to stop.
*/

/**
 * A new git repository with a committed file of each kind, files from before
 * the conversation dated an hour back, and a transcript that starts now.
 */
function finishCheckRepo(): string
{
    $repo = sys_get_temp_dir().'/ezdev-finish-check-'.bin2hex(random_bytes(6));
    $root = dirname(__DIR__, 3);

    finishCheckWrite($repo, 'scripts/hooks/finish-check.php', file_get_contents("{$root}/scripts/hooks/finish-check.php") ?: throw new RuntimeException('no scripts/hooks/finish-check.php'));
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n");
    finishCheckWrite($repo, 'docs/notes.md', "# Notes\n");
    finishCheckWrite($repo, 'phpstan.neon', "parameters:\n");
    finishCheckWrite($repo, '.gitignore', "/transcript.jsonl\n");

    (new Process(['git', 'init', '-q', '-b', 'main'], $repo))->mustRun();
    finishCheckCommit($repo, time() - 3600);
    finishCheckTranscript($repo, time());

    register_shutdown_function(fn () => (new Process(['rm', '-rf', $repo]))->run());

    return $repo;
}

function finishCheckWrite(string $repo, string $path, string $content, ?int $time = null): void
{
    if (! is_dir(dirname("{$repo}/{$path}"))) {
        mkdir(dirname("{$repo}/{$path}"), 0777, true);
    }

    file_put_contents("{$repo}/{$path}", $content);

    if ($time !== null) {
        touch("{$repo}/{$path}", $time);
    }
}

function finishCheckCommit(string $repo, ?int $time = null): void
{
    $date = $time === null ? [] : ['GIT_COMMITTER_DATE' => "@{$time}", 'GIT_AUTHOR_DATE' => "@{$time}"];

    // Settings from the user's own git config (signing, hooks) must not reach these commits.
    foreach ([['add', '-A'], ['-c', 'user.name=t', '-c', 'user.email=t@t', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null', 'commit', '-q', '-m', 'work']] as $args) {
        (new Process(['git', ...$args], $repo, $date))->mustRun();
    }
}

/**
 * The conversation's transcript: its first entry says when it started.
 */
function finishCheckTranscript(string $repo, int $start): void
{
    file_put_contents("{$repo}/transcript.jsonl", json_encode(['type' => 'user', 'timestamp' => gmdate('Y-m-d\TH:i:s.000\Z', $start)])."\n");
}

/**
 * Runs the Stop hook and returns its output as an array ([] when it prints nothing).
 *
 * @param  array<string, mixed>  $input
 * @return array<string, mixed>
 */
function finishCheckStop(string $repo, array $input = []): array
{
    $process = new Process(['php', 'scripts/hooks/finish-check.php', 'stop'], $repo);
    $process->setInput(json_encode([...['session_id' => 'test', 'cwd' => $repo, 'stop_hook_active' => false, 'transcript_path' => "{$repo}/transcript.jsonl"], ...$input]) ?: '');
    $process->mustRun();

    return $process->getOutput() === '' ? [] : json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * Runs a mode of the finish check the way composer check does.
 */
function finishCheckMode(string $repo, string ...$arguments): Process
{
    $process = new Process(['php', 'scripts/hooks/finish-check.php', ...$arguments], $repo);
    $process->run();

    return $process;
}

/**
 * A check that passed on the code as it is now.
 */
function finishCheckPass(string $repo): void
{
    finishCheckMode($repo, 'revoke');
    $fingerprint = trim(finishCheckMode($repo, 'fingerprint')->getOutput());

    expect(finishCheckMode($repo, 'passed', $fingerprint)->getExitCode())->toBe(0);
}

test('a conversation that changed no file may stop', function () {
    expect(finishCheckStop(finishCheckRepo()))->toBe([]);
});

test('changed code with no passing check is sent back once, and the next stop goes through with one line for Ryan', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");

    $first = finishCheckStop($repo);
    $again = finishCheckStop($repo, ['stop_hook_active' => true]);

    expect($first['decision'])->toBe('block')
        ->and($first['reason'])->toContain('app/Actions/Quotes/AcceptQuote.php')->toContain('composer check:milestone')
        ->and($first['reason'])->not->toContain("\n")
        ->and($again)->not->toHaveKey('decision')
        ->and($again['systemMessage'])->toContain('without a passing composer check');
});

test('a pass on the code as it is now lets the AI stop, and a change after it does not', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");
    finishCheckPass($repo);

    expect(finishCheckStop($repo))->toBe([]);

    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = false;\n");

    expect(finishCheckStop($repo)['decision'] ?? null)->toBe('block');
});

test('code committed in this conversation needs a pass too', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");
    finishCheckCommit($repo);

    expect(finishCheckStop($repo)['decision'] ?? null)->toBe('block');
});

test('code changed before the conversation started is not this conversation\'s change', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\$earlier = true;\n", time() - 600);

    expect(finishCheckStop($repo))->toBe([]);
});

test('without a transcript every uncommitted code change counts', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\$earlier = true;\n", time() - 600);

    expect(finishCheckStop($repo, ['transcript_path' => null])['decision'] ?? null)->toBe('block');
});

test('Markdown and docs are not code', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/notes.md', "# Notes\n\nMore.\n");
    finishCheckWrite($repo, 'docs/exec-plans/active/quotes.md', "# Quotes\n");
    finishCheckWrite($repo, 'README.md', "# Readme\n");

    expect(finishCheckStop($repo))->toBe([]);
});

test('the reference feature\'s code is code', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/reference/tasks/app/Models/Task.php', "<?php\n");

    expect(finishCheckStop($repo)['decision'] ?? null)->toBe('block');
});

test('a changed check file gives Ryan one line, does not block, and is told once', function (string $path) {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, $path, "changed\n");
    finishCheckPass($repo);

    $first = finishCheckStop($repo);

    expect($first)->not->toHaveKey('decision')
        ->and($first['systemMessage'])->toBe("This conversation changed a check: {$path}.")
        ->and(finishCheckStop($repo))->toBe([]);
})->with(['phpstan.neon', 'vite.config.ts', 'tsconfig.json', 'phpunit.xml', 'pint.json', 'lint/oxlint/ezdev.js', 'tests/Structure/CodeTest.php']);

test('a check file and code with no pass block and tell Ryan in the same answer', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'phpstan.neon', "parameters:\n    level: 9\n");

    $output = finishCheckStop($repo);

    expect($output['decision'])->toBe('block')
        ->and($output['systemMessage'])->toBe('This conversation changed a check: phpstan.neon.');
});

test('a pass is recorded only for the code the check started on', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");
    $fingerprint = trim(finishCheckMode($repo, 'fingerprint')->getOutput());
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = false;\n");

    $passed = finishCheckMode($repo, 'passed', $fingerprint);

    expect($passed->getExitCode())->toBe(1)
        ->and($passed->getErrorOutput())->toContain('the code changed while the check ran')
        ->and(finishCheckStop($repo)['decision'] ?? null)->toBe('block');
});

test('a check that starts forgets the last pass', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");
    finishCheckPass($repo);
    finishCheckMode($repo, 'revoke');

    expect(finishCheckStop($repo)['decision'] ?? null)->toBe('block');
});

test('an error inside the finish check is told to Ryan and lets the AI stop', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'phpstan.neon', "changed\n");
    // A state file that cannot be written.
    mkdir("{$repo}/.git/ezdev/told-".sha1('broken').'.json', 0777, true);

    $output = finishCheckStop($repo, ['session_id' => 'broken']);

    expect($output)->not->toHaveKey('decision')
        ->and($output['systemMessage'])->toStartWith('The finish check failed: ');
});

test('a git command that fails is told to Ryan, never read as no change', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");
    finishCheckCommit($repo);
    // A git that fails for `log` only, so the commits of this conversation cannot be read.
    $real = trim((new Process(['which', 'git']))->mustRun()->getOutput());
    finishCheckWrite($repo, 'bin/git', "#!/bin/sh\nfor a in \"\$@\"; do [ \"\$a\" = log ] && exit 128; done\nexec {$real} \"\$@\"\n");
    chmod("{$repo}/bin/git", 0755);

    $process = new Process(['php', 'scripts/hooks/finish-check.php', 'stop'], $repo, ['PATH' => "{$repo}/bin:".getenv('PATH')]);
    $process->setInput(json_encode(['session_id' => 'test', 'cwd' => $repo, 'stop_hook_active' => false, 'transcript_path' => "{$repo}/transcript.jsonl"]) ?: '');
    $output = json_decode($process->mustRun()->getOutput(), true, flags: JSON_THROW_ON_ERROR);

    expect($output['systemMessage'])->toBe('The finish check failed: git log failed');
});

test('a git that refuses the repository is told to Ryan, never read as no repository', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");
    // A git that refuses every rev-parse, as git does for a folder it does not trust.
    $real = trim((new Process(['which', 'git']))->mustRun()->getOutput());
    finishCheckWrite($repo, 'bin/git', "#!/bin/sh\nfor a in \"\$@\"; do [ \"\$a\" = rev-parse ] && { echo 'fatal: detected dubious ownership' >&2; exit 128; }; done\nexec {$real} \"\$@\"\n");
    chmod("{$repo}/bin/git", 0755);

    $process = new Process(['php', 'scripts/hooks/finish-check.php', 'stop'], $repo, ['PATH' => "{$repo}/bin:".getenv('PATH')]);
    $process->setInput(json_encode(['session_id' => 'test', 'cwd' => $repo, 'stop_hook_active' => false, 'transcript_path' => "{$repo}/transcript.jsonl"]) ?: '');
    $output = json_decode($process->mustRun()->getOutput(), true, flags: JSON_THROW_ON_ERROR);

    expect($output['systemMessage'])->toBe('The finish check failed: git rev-parse failed: fatal: detected dubious ownership');
});

test('before the first commit the finish check says nothing', function () {
    $dir = sys_get_temp_dir().'/ezdev-no-commit-'.bin2hex(random_bytes(6));
    finishCheckWrite($dir, 'scripts/hooks/finish-check.php', file_get_contents(dirname(__DIR__, 3).'/scripts/hooks/finish-check.php') ?: throw new RuntimeException('no scripts/hooks/finish-check.php'));
    finishCheckWrite($dir, 'app/code.php', "<?php\n");
    (new Process(['git', 'init', '-q', '-b', 'main'], $dir))->mustRun();
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $dir]))->run());

    expect(finishCheckStop($dir))->toBe([]);
});

test('Claude Code runs only the Stop hook, and a missing or broken script still tells Ryan', function () {
    $root = dirname(__DIR__, 3);
    $repo = finishCheckRepo();
    $missing = sys_get_temp_dir().'/ezdev-no-finish-check-'.bin2hex(random_bytes(6));
    mkdir($missing);
    $broken = finishCheckRepo();
    finishCheckWrite($broken, 'scripts/hooks/finish-check.php', "<?php\n\nthis is not php\n");
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $missing]))->run());

    $run = function (string $command, string $dir): string {
        $process = Process::fromShellCommandline($command, $dir, ['CLAUDE_PROJECT_DIR' => $dir]);
        $process->setInput(json_encode(['session_id' => 'test', 'cwd' => $dir]) ?: '');
        $process->mustRun();

        return $process->getOutput();
    };

    // The template supports Claude Code only: no other tool's hook configuration is shipped.
    expect(glob("{$root}/.*/hooks.json"))->toBe([]);

    $hooks = json_decode((string) file_get_contents("{$root}/.claude/settings.json"), true)['hooks'];
    $command = $hooks['Stop'][0]['hooks'][0]['command'];

    expect(array_keys($hooks))->toBe(['Stop'])
        ->and($run($command, $repo))->toBe('')
        ->and($run($command, $missing))->toBe("{\"systemMessage\":\"The finish check could not run.\"}\n")
        ->and($run($command, $broken))->toBe("{\"systemMessage\":\"The finish check could not run.\"}\n");
});

test('the session has no question tool and no workflows, and its subagents run in the foreground', function () {
    $settings = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/.claude/settings.json'), true);

    expect($settings['permissions']['deny'] ?? [])->toContain('AskUserQuestion')->toContain('Workflow')
        ->and($settings['env']['CLAUDE_CODE_DISABLE_BACKGROUND_TASKS'] ?? null)->toBe('1');
});

test('outside a git repository the finish check says nothing', function () {
    $dir = sys_get_temp_dir().'/ezdev-no-git-'.bin2hex(random_bytes(6));
    finishCheckWrite($dir, 'scripts/hooks/finish-check.php', file_get_contents(dirname(__DIR__, 3).'/scripts/hooks/finish-check.php') ?: throw new RuntimeException('no scripts/hooks/finish-check.php'));
    finishCheckWrite($dir, 'app/code.php', "<?php\n");
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $dir]))->run());

    expect(finishCheckStop($dir))->toBe([])
        ->and(finishCheckMode($dir, 'fingerprint')->getOutput())->toBe('');
});
