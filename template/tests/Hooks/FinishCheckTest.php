<?php

use Symfony\Component\Process\Process;

require_once dirname(__DIR__, 3).'/tests/Support/Workspace.php';

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
    $repo = workspaceFolder('finish-check');
    workspaceCopy($repo, ['scripts/hooks/finish-check.php']);
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n");
    workspaceWrite($repo, 'docs/notes.md', "# Notes\n");
    workspaceWrite($repo, 'phpstan.neon', "parameters:\n");
    workspaceWrite($repo, '.gitignore', "/transcript.jsonl\n");
    workspaceGitInit($repo, time() - 3600);
    finishCheckTranscript($repo, time());

    return $repo;
}

/**
 * The conversation's transcript: its first entry says when it started.
 */
function finishCheckTranscript(string $repo, int $start): void
{
    workspaceWrite($repo, 'transcript.jsonl', json_encode(['type' => 'user', 'timestamp' => gmdate('Y-m-d\TH:i:s.000\Z', $start)])."\n");
}

/**
 * Runs the Stop hook and returns its output as an array ([] when it prints nothing).
 *
 * @param  array<string, mixed>  $input
 * @param  array<string, string>  $env
 * @return array<string, mixed>
 */
function finishCheckStop(string $repo, array $input = [], array $env = []): array
{
    $output = workspaceStop($repo, [...['session_id' => 'test', 'cwd' => $repo, 'stop_hook_active' => false, 'transcript_path' => "{$repo}/transcript.jsonl"], ...$input], $env);

    return $output === '' ? [] : json_decode($output, true, flags: JSON_THROW_ON_ERROR);
}

/**
 * A check that passed on the code as it is now.
 */
function finishCheckPass(string $repo): void
{
    expect(workspaceFinishCheck($repo, ['revoke'])->getExitCode())->toBe(0);
    $fingerprint = trim(workspaceFinishCheck($repo, ['fingerprint'])->getOutput());

    expect(workspaceFinishCheck($repo, ['passed', $fingerprint])->getExitCode())->toBe(0);
}

/**
 * Runs the Stop hook with a git that runs $fail (shell) for the git command
 * $command and is the real git otherwise.
 *
 * @return array<string, mixed>
 */
function finishCheckStopWithGit(string $repo, string $command, string $fail): array
{
    return finishCheckStop($repo, [], ['PATH' => workspaceFakeGit($repo, "for a in \"\$@\"; do [ \"\$a\" = {$command} ] && {$fail}; done")]);
}

test('a conversation that changed no file may stop', function () {
    expect(finishCheckStop(finishCheckRepo()))->toBe([]);
});

test('changed code with no passing check is sent back once, and the next stop goes through with one line for Ryan', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");

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
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");
    finishCheckPass($repo);

    expect(finishCheckStop($repo))->toBe([]);

    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = false;\n");

    expect(finishCheckStop($repo)['decision'] ?? null)->toBe('block');
});

test('code committed in this conversation needs a pass too', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");
    workspaceCommit($repo);

    expect(finishCheckStop($repo)['decision'] ?? null)->toBe('block');
});

test('code changed before the conversation started is not this conversation\'s change', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\$earlier = true;\n", time() - 600);

    expect(finishCheckStop($repo))->toBe([]);
});

test('a file deleted before the conversation started is not this conversation\'s change', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/Old/OldQuote.php', "<?php\n");
    workspaceCommit($repo, time() - 3600);
    // Deleting a file changes the time of its folder, here before the conversation.
    workspaceRemove("{$repo}/app/Actions/Quotes/SendQuote.php");
    touch("{$repo}/app/Actions/Quotes", time() - 600) ?: throw new RuntimeException('cannot date the folder');
    // A folder deleted with its file: the nearest folder that is still there tells.
    workspaceRemove("{$repo}/app/Actions/Quotes/Old");
    touch("{$repo}/app/Actions/Quotes", time() - 600) ?: throw new RuntimeException('cannot date the folder');

    expect(finishCheckStop($repo))->toBe([]);
});

test('a file deleted in the conversation is this conversation\'s change', function (string $removed) {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/Old/OldQuote.php', "<?php\n");
    workspaceCommit($repo, time() - 3600);
    touch("{$repo}/app/Actions/Quotes", time() - 600) ?: throw new RuntimeException('cannot date the folder');
    touch("{$repo}/app/Actions/Quotes/Old", time() - 600) ?: throw new RuntimeException('cannot date the folder');
    workspaceRemove("{$repo}/{$removed}");

    expect(finishCheckStop($repo)['decision'] ?? null)->toBe('block');
})->with(['a file' => 'app/Actions/Quotes/SendQuote.php', 'a folder with its file' => 'app/Actions/Quotes/Old']);

test('without a transcript every uncommitted code change counts', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\$earlier = true;\n", time() - 600);

    expect(finishCheckStop($repo, ['transcript_path' => null])['decision'] ?? null)->toBe('block');
});

test('Markdown and docs are not code', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'docs/notes.md', "# Notes\n\nMore.\n");
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', "# Quotes\n");
    workspaceWrite($repo, 'README.md', "# Readme\n");

    expect(finishCheckStop($repo))->toBe([]);
});

test('the reference feature\'s code is code', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'docs/reference/tasks/app/Models/Task.php', "<?php\n");

    expect(finishCheckStop($repo)['decision'] ?? null)->toBe('block');
});

test('the sample copies are code, their Markdown is not', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'docs/reference/pages/README.md', "# Samples\n");

    expect(finishCheckStop($repo))->toBe([]);

    workspaceWrite($repo, 'docs/reference/pages/auth/login.tsx', "export {};\n");

    expect(finishCheckStop($repo)['decision'] ?? null)->toBe('block');
});

test('scripts/ and template/ touch lane backend, and the sample copies the lanes of the page they copy', function () {
    $repo = finishCheckRepo();
    $lanes = json_decode(workspaceFinishCheck($repo, ['lanes'])->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    $touched = fn (string $path) => json_decode(workspaceFinishCheck($repo, ['lanes', $path])->getOutput(), true, flags: JSON_THROW_ON_ERROR);

    expect($lanes['backend'])->toContain('scripts/', 'template/')
        ->and($touched('scripts/hooks/finish-check.php'))->toBe(['backend'])
        ->and($touched('template/tests/Hooks/FinishCheckTest.php'))->toBe(['backend'])
        ->and($touched('docs/reference/pages/auth/login.tsx'))->toBe(['frontend', 'screens'])
        ->and($touched('docs/reference/pages/components/notifications/notification-row.tsx'))->toBe(['frontend', 'screens'])
        ->and($touched('docs/reference/tasks/app/Http/Resources/TaskResource.php'))->toBe(['backend', 'frontend', 'screens'])
        ->and($touched('docs/reference/pages/README.md'))->toBe([]);
});

test('a changed check file gives Ryan one line, does not block, and is told once', function (string $path) {
    $repo = finishCheckRepo();
    workspaceWrite($repo, $path, "changed\n");
    finishCheckPass($repo);

    $first = finishCheckStop($repo);

    expect($first)->not->toHaveKey('decision')
        ->and($first['systemMessage'])->toBe("This conversation changed a check: {$path}.")
        ->and(finishCheckStop($repo))->toBe([]);
})->with(['phpstan.neon', 'vite.config.ts', 'tsconfig.json', 'phpunit.xml', 'pint.json', 'lint/oxlint/ezdev.js', 'tests/Structure/CodeTest.php', 'template/phpunit.xml', 'template/tests/Lint/CodeTest.php']);

test('a check file and code with no pass block and tell Ryan in the same answer', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'phpstan.neon', "parameters:\n    level: 9\n");

    $output = finishCheckStop($repo);

    expect($output['decision'])->toBe('block')
        ->and($output['systemMessage'])->toBe('This conversation changed a check: phpstan.neon.');
});

test('a pass is recorded only for the code the check started on', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");
    $fingerprint = trim(workspaceFinishCheck($repo, ['fingerprint'])->getOutput());
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = false;\n");

    $passed = workspaceFinishCheck($repo, ['passed', $fingerprint]);

    expect($passed->getExitCode())->toBe(1)
        ->and($passed->getErrorOutput())->toContain('the code changed while the check ran')
        ->and(finishCheckStop($repo)['decision'] ?? null)->toBe('block');
});

test('a check that starts forgets the last pass', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");
    finishCheckPass($repo);
    workspaceFinishCheck($repo, ['revoke']);

    expect(finishCheckStop($repo)['decision'] ?? null)->toBe('block');
});

test('an error inside the finish check is told to Ryan and lets the AI stop', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'phpstan.neon', "changed\n");
    // A state file that cannot be written.
    mkdir("{$repo}/.git/ezdev/told-".sha1('broken').'.json', 0777, true) ?: throw new RuntimeException('cannot make the state folder');

    $output = finishCheckStop($repo, ['session_id' => 'broken']);

    expect($output)->not->toHaveKey('decision')
        ->and($output['systemMessage'])->toStartWith('The finish check failed: ');
});

test('a git command that fails is told to Ryan, never read as no change', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");
    workspaceCommit($repo);
    // A git that fails for `log` only, so the commits of this conversation cannot be read.
    $output = finishCheckStopWithGit($repo, 'log', 'exit 128');

    expect($output['systemMessage'])->toBe('The finish check failed: git log failed');
});

test('a temporary file the finish check cannot remove is told to Ryan, never left silently', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");
    $temp = workspaceFolder('finish-check-temp');
    // A git that leaves the temporary folder read-only, so nothing in it can be removed.
    $path = workspaceFakeGit($repo, 'chmod 555 "$TMPDIR"');

    try {
        $output = finishCheckStop($repo, [], ['PATH' => $path, 'TMPDIR' => $temp]);
    } finally {
        chmod($temp, 0755) ?: throw new RuntimeException('cannot make the temporary folder writable again');
    }

    expect($output['systemMessage'] ?? null)->toStartWith('The finish check failed: cannot remove the temporary file');
});

test('a git that refuses the repository is told to Ryan, never read as no repository', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");
    // A git that refuses every rev-parse, as git does for a folder it does not trust.
    $output = finishCheckStopWithGit($repo, 'rev-parse', "{ echo 'fatal: detected dubious ownership' >&2; exit 128; }");

    expect($output['systemMessage'])->toBe('The finish check failed: git rev-parse failed: fatal: detected dubious ownership');
});

test('outside a git repository the finish check says nothing in any language', function () {
    $dir = workspaceFolder('no-git-zh');
    workspaceCopy($dir, ['scripts/hooks/finish-check.php']);
    // A git that answers in Chinese unless asked for English, as a translated git does.
    $path = workspaceFakeGit($dir, "[ \"\$LC_ALL\" = C ] || { echo '致命错误：不是 git 仓库' >&2; exit 128; }");

    expect(finishCheckStop($dir, ['transcript_path' => null], ['PATH' => $path, 'LANG' => 'zh_CN.UTF-8', 'LC_ALL' => 'zh_CN.UTF-8']))->toBe([]);
});

test('a transcript or state file that cannot be read is told to Ryan, never read as none', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");
    chmod("{$repo}/transcript.jsonl", 0000);

    expect(finishCheckStop($repo)['systemMessage'])->toStartWith('The finish check failed: cannot read the transcript');

    chmod("{$repo}/transcript.jsonl", 0644);
    workspaceWrite($repo, 'phpstan.neon', "changed\n");
    workspaceWrite($repo, '.git/ezdev/told-'.sha1('test').'.json', '{not json');

    expect(finishCheckStop($repo)['systemMessage'])->toStartWith('The finish check failed: ');
});

test('a Stop input that is not a JSON object, or a told file that holds no list, is told to Ryan', function () {
    $repo = finishCheckRepo();
    $process = workspaceFinishCheck($repo, ['stop'], input: 'not json');

    expect($process->getExitCode())->toBe(0)
        ->and(json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR)['systemMessage'])->toStartWith('The finish check failed: ');

    workspaceWrite($repo, 'phpstan.neon', "changed\n");
    workspaceWrite($repo, '.git/ezdev/told-'.sha1('test').'.json', '"phpstan.neon"');

    expect(finishCheckStop($repo)['systemMessage'])->toContain('holds no list of check files');
});

test('a Stop input without a session, or a transcript time that cannot be read, is told to Ryan', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");

    expect(finishCheckStop($repo, ['session_id' => null])['systemMessage'])->toBe('The finish check failed: the Stop input names no session');

    workspaceWrite($repo, 'transcript.jsonl', json_encode(['type' => 'user', 'timestamp' => 'not a time'])."\n");

    expect(finishCheckStop($repo)['systemMessage'])->toStartWith('The finish check failed: the transcript has a time stamp that cannot be read');

    workspaceWrite($repo, 'transcript.jsonl', "{broken\n");

    expect(finishCheckStop($repo)['systemMessage'])->toStartWith('The finish check failed: ');
});

test('a transcript that is named but not there, or has no time stamp, is told to Ryan', function () {
    $repo = finishCheckRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");

    expect(finishCheckStop($repo, ['transcript_path' => "{$repo}/gone.jsonl"])['systemMessage'])->toBe('The finish check failed: the transcript the Stop input names is not there');

    workspaceWrite($repo, 'transcript.jsonl', json_encode(['type' => 'summary'])."\n");

    expect(finishCheckStop($repo)['systemMessage'])->toStartWith('The finish check failed: the transcript has no time stamp');
});

test('before the first commit the finish check says nothing', function () {
    $dir = workspaceFolder('no-commit');
    workspaceCopy($dir, ['scripts/hooks/finish-check.php']);
    workspaceWrite($dir, 'app/code.php', "<?php\n");
    (new Process(['git', 'init', '-q', '-b', 'main'], $dir))->mustRun();

    expect(finishCheckStop($dir))->toBe([]);
});

test('Claude Code runs only the Stop hook, and a missing or broken script still tells Ryan', function () {
    $root = dirname(__DIR__, 3);
    $repo = finishCheckRepo();
    $missing = workspaceFolder('no-finish-check');
    $broken = finishCheckRepo();
    workspaceWrite($broken, 'scripts/hooks/finish-check.php', "<?php\n\nthis is not php\n");

    $run = function (string $command, string $dir): string {
        $process = Process::fromShellCommandline($command, $dir, ['CLAUDE_PROJECT_DIR' => $dir]);
        $process->setInput(json_encode(['session_id' => 'test', 'cwd' => $dir]) ?: '');
        $process->mustRun();

        return $process->getOutput();
    };

    // The template supports Claude Code only: no other tool's hook configuration is shipped.
    expect(glob("{$root}/.*/hooks.json"))->toBe([]);

    $hooks = json_decode(file_get_contents("{$root}/.claude/settings.json") ?: throw new RuntimeException('no .claude/settings.json'), true, flags: JSON_THROW_ON_ERROR)['hooks'];
    $command = $hooks['Stop'][0]['hooks'][0]['command'];

    expect(array_keys($hooks))->toBe(['Stop'])
        ->and($run($command, $repo))->toBe('')
        ->and($run($command, $missing))->toBe("{\"systemMessage\":\"The finish check could not run.\"}\n")
        ->and($run($command, $broken))->toBe("{\"systemMessage\":\"The finish check could not run.\"}\n");
});

test('the session has no question tool and no workflows, and its subagents run in the foreground', function () {
    $settings = json_decode(file_get_contents(dirname(__DIR__, 3).'/.claude/settings.json') ?: throw new RuntimeException('no .claude/settings.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($settings['permissions']['deny'] ?? [])->toContain('AskUserQuestion')->toContain('Workflow')
        ->and($settings['env']['CLAUDE_CODE_DISABLE_BACKGROUND_TASKS'] ?? null)->toBe('1');
});

test('outside a git repository the finish check says nothing', function () {
    $dir = workspaceFolder('no-git');
    workspaceCopy($dir, ['scripts/hooks/finish-check.php']);
    workspaceWrite($dir, 'app/code.php', "<?php\n");

    expect(finishCheckStop($dir))->toBe([])
        ->and(workspaceFinishCheck($dir, ['fingerprint'])->getOutput())->toBe('');
});
