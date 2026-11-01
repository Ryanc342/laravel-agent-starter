<?php

use Symfony\Component\Process\Process;

/*
| scripts/hooks/finish-check.php, run the way Claude Code and Codex run it, in a
| throwaway git repository: each test starts a conversation, changes files and
| reads what the script sends back when the AI is about to stop.
*/

/**
 * A new git repository with the files the finish check reads, committed.
 */
function finishCheckRepo(): string
{
    $repo = sys_get_temp_dir().'/ezdev-finish-check-'.bin2hex(random_bytes(6));

    finishCheckWrite($repo, 'scripts/hooks/finish-check.php', file_get_contents(dirname(__DIR__, 3).'/scripts/hooks/finish-check.php') ?: '');
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n");
    finishCheckWrite($repo, 'composer.json', "{}\n");
    finishCheckWrite($repo, 'docs/QUALITY.md', "# Quality\n");
    finishCheckWrite($repo, 'docs/exec-plans/active/.gitkeep', '');
    finishCheckWrite($repo, 'docs/exec-plans/tech-debt.md', "# Tech debt\n\n| Added | Where | What | Why deferred |\n| --- | --- | --- | --- |\n| 2026-11-01 | app/Actions/Quotes/SendQuote.php | a | b |\n");

    // Settings from the user's own git config (signing, hooks) must not reach these commits.
    foreach ([['init', '-q', '-b', 'main'], ['add', '-A'], ['-c', 'user.name=t', '-c', 'user.email=t@t', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null', 'commit', '-q', '-m', 'start']] as $args) {
        (new Process(['git', ...$args], $repo))->mustRun();
    }

    register_shutdown_function(fn () => (new Process(['rm', '-rf', $repo]))->run());

    finishCheckRun($repo, 'start');

    return $repo;
}

function finishCheckWrite(string $repo, string $path, string $content): void
{
    if (! is_dir(dirname("{$repo}/{$path}"))) {
        mkdir(dirname("{$repo}/{$path}"), 0777, true);
    }

    file_put_contents("{$repo}/{$path}", $content);
}

/**
 * Runs the script like a hook does and returns the reason it sends back ('' when it lets the AI stop).
 */
function finishCheckRun(string $repo, string $mode, string $session = 'test'): string
{
    $process = new Process(['php', 'scripts/hooks/finish-check.php', $mode], $repo);
    $process->setInput(json_encode(['session_id' => $session, 'cwd' => $repo, 'stop_hook_active' => false, 'last_assistant_message' => 'Done.']) ?: '');
    $process->mustRun();

    return json_decode($process->getOutput(), true)['reason'] ?? '';
}

function finishCheckPlan(string $progress, string $outcome): string
{
    return "# Send quotes\n\n## Purpose\nx\n\n## Progress\n{$progress}\n\n## Decision Log\n- The user approved composer.json\n\n## Outcomes & Retrospective\n{$outcome}\n";
}

test('a conversation that changed no file may always stop', function () {
    expect(finishCheckRun(finishCheckRepo(), 'stop'))->toBe('');
});

test('code without a plan is sent back, and so is code no composer check has passed', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\n\$a = 1;\n\$b = 2;\n\$c = 3;\n\$d = 4;\n");

    $reason = finishCheckRun($repo, 'stop');

    expect($reason)->toContain('there is no plan in docs/exec-plans/active/')
        ->and($reason)->toContain('Run composer check');

    finishCheckRun($repo, 'checked');
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan('', ''));

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('a one-line change needs no plan, but still a passing composer check', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = true;\n");

    $reason = finishCheckRun($repo, 'stop');

    expect($reason)->not->toContain('no plan')
        ->and($reason)->toContain('Run composer check');
});

test('removing a tech-debt line without updating docs/QUALITY.md is sent back', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/tech-debt.md', "# Tech debt\n\n| Added | Where | What | Why deferred |\n| --- | --- | --- | --- |\n");

    expect(finishCheckRun($repo, 'stop'))->toContain('docs/QUALITY.md did not change');

    finishCheckWrite($repo, 'docs/QUALITY.md', "# Quality\n\nQuotes: A\n");

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('a changed dependency needs the user\'s approval recorded in the plan', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', "# Send quotes\n\n## Purpose\nx\n");
    finishCheckWrite($repo, 'composer.json', "{\"require\": {}}\n");
    finishCheckRun($repo, 'checked');

    expect(finishCheckRun($repo, 'stop'))->toContain('You changed composer.json');

    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan('', ''));

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('a completed plan without check results, review rounds and outcome is sent back', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/completed/send-quotes.md', finishCheckPlan('- 10:00 M1 done', ''));

    expect(finishCheckRun($repo, 'stop'))->toContain('no composer check result in Progress, no review round in Progress, an empty Outcomes & Retrospective');

    finishCheckWrite($repo, 'docs/exec-plans/completed/send-quotes.md', finishCheckPlan("- 10:00 M1 done, composer check passed\n- 10:30 review round 1: no P0/P1", 'Quotes can be sent; nothing is left.'));

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('the same reason is sent back at most three times, so a stop never loops', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = true;\n");

    $reasons = array_map(fn () => finishCheckRun($repo, 'stop'), range(1, 4));

    expect(array_map(fn (string $reason): bool => $reason !== '', $reasons))->toBe([true, true, true, false]);
});

test('a plan with a non-English name is found and checked', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/completed/发送报价单.md', finishCheckPlan('- 10:00 M1 done', ''));

    expect(finishCheckRun($repo, 'stop'))->toContain('docs/exec-plans/completed/发送报价单.md is complete but has');
});

test('a file already changed when the conversation started counts once it changes again', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$a = 1;\n");
    finishCheckRun($repo, 'start', 'second');

    expect(finishCheckRun($repo, 'stop', 'second'))->toBe('');

    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$a = 1;\n\$b = 2;\n\$c = 3;\n\$d = 4;\n");

    expect(finishCheckRun($repo, 'stop', 'second'))->toContain('there is no plan');
});
