<?php

use Symfony\Component\Process\Process;

/*
| scripts/hooks/finish-check.php, run the way Claude Code, Codex and composer
| check run it, in a throwaway git repository: each test starts a conversation,
| changes files and reads what the script sends back when the AI is about to stop.
*/

/**
 * A new git repository with the files the finish check reads, committed.
 */
function finishCheckRepo(): string
{
    $repo = sys_get_temp_dir().'/ezdev-finish-check-'.bin2hex(random_bytes(6));
    $root = dirname(__DIR__, 3);

    finishCheckWrite($repo, 'scripts/hooks/finish-check.php', file_get_contents("{$root}/scripts/hooks/finish-check.php") ?: '');
    finishCheckWrite($repo, 'docs/PLANS.md', file_get_contents("{$root}/docs/PLANS.md") ?: '');
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n");
    finishCheckWrite($repo, 'composer.json', "{}\n");
    finishCheckWrite($repo, 'docs/QUALITY.md', "# Quality\n");
    finishCheckWrite($repo, 'docs/exec-plans/active/.gitkeep', '');
    finishCheckWrite($repo, 'docs/exec-plans/tech-debt.md', "# Tech debt\n\n| Added | Where | What | Why deferred |\n| --- | --- | --- | --- |\n| 2026-11-01 | app/Actions/Quotes/SendQuote.php | a | b |\n");
    // As in the project: the file tests/Structure/RunFinishedTest.php writes is no code change.
    finishCheckWrite($repo, 'storage/framework/testing/.gitignore', "*\n!.gitignore\n");

    (new Process(['git', 'init', '-q', '-b', 'main'], $repo))->mustRun();
    finishCheckCommit($repo);

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

function finishCheckCommit(string $repo): void
{
    // Settings from the user's own git config (signing, hooks) must not reach these commits.
    foreach ([['add', '-A'], ['-c', 'user.name=t', '-c', 'user.email=t@t', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null', 'commit', '-q', '-m', 'work']] as $args) {
        (new Process(['git', ...$args], $repo))->mustRun();
    }
}

/**
 * Runs the script like a hook does and returns everything it prints on stdout.
 */
function finishCheckOutput(string $repo, string $mode, string $session = 'test', string $message = 'Done.'): string
{
    $process = new Process(['php', 'scripts/hooks/finish-check.php', $mode], $repo);
    $process->setInput(json_encode(['session_id' => $session, 'cwd' => $repo, 'stop_hook_active' => false, 'last_assistant_message' => $message]) ?: '');
    $process->mustRun();

    return $process->getOutput();
}

/**
 * The reason the script sends back ('' when it lets the AI stop).
 */
function finishCheckRun(string $repo, string $mode, string $session = 'test', string $message = 'Done.'): string
{
    return json_decode(finishCheckOutput($repo, $mode, $session, $message), true)['reason'] ?? '';
}

/**
 * Runs a step of composer check (checking or checked), which may refuse.
 */
function finishCheckComposer(string $repo, string $mode): Process
{
    $process = new Process(['php', 'scripts/hooks/finish-check.php', $mode], $repo);
    $process->run();

    return $process;
}

/**
 * A composer check that passed: it started, its tests ran to their end, it finished.
 */
function finishCheckPass(string $repo): void
{
    finishCheckComposer($repo, 'checking');
    finishCheckWrite($repo, 'storage/framework/testing/ezdev-tests-finished', (string) microtime(true));

    expect(finishCheckComposer($repo, 'checked')->getErrorOutput())->toBe('');
}

function finishCheckPlan(
    string $progress = '',
    string $outcome = '',
    string $purpose = 'Sales staff send a quote to the customer by email.',
    string $agreed = '1. A quote is sent as a PDF attachment.',
    string $milestones = '- [ ] M1 Actions: SendQuote + tests',
    string $decisions = '- 2026-11-02 The user approved composer.json',
): string {
    return "# Send quotes\n\n## Purpose\n{$purpose}\n\n## Agreed decisions\n{$agreed}\n\n## Milestones\n{$milestones}\n\n## Progress\n{$progress}\n\n## Decision Log\n{$decisions}\n\n## Outcomes & Retrospective\n{$outcome}\n";
}

const FINISH_CHECK_NEW_CODE = "<?php\n\n\$a = 1;\n\$b = 2;\n\$c = 3;\n\$d = 4;\n";

test('a conversation that changed no file may always stop', function () {
    expect(finishCheckRun(finishCheckRepo(), 'stop'))->toBe('');
});

test('code without a plan is sent back, and so is code no composer check has passed', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE);

    $reason = finishCheckRun($repo, 'stop');

    expect($reason)->toContain('there is no plan in docs/exec-plans/active/')
        ->and($reason)->toContain('Run composer check');

    finishCheckPass($repo);
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan('', ''));

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('a plan from before the conversation counts for new code only once it changes', function (bool $committed) {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan());

    if ($committed) {
        finishCheckCommit($repo);
    }

    finishCheckRun($repo, 'start', 'second');
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE);
    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop', 'second'))->toContain('there is no plan in docs/exec-plans/active/ that changed in this conversation');

    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan('- 2026-11-02 10:00 M1 started'));

    expect(finishCheckRun($repo, 'stop', 'second'))->toBe('');
})->with(['committed' => [true], 'not yet committed' => [false]]);

test('a plan that still holds the template or its placeholders is sent back to be filled in', function () {
    $repo = finishCheckRepo();
    preg_match('/```markdown\n(.*?)```/s', (string) file_get_contents("{$repo}/docs/PLANS.md"), $template);
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', $template[1]);

    expect(finishCheckRun($repo, 'stop'))->toContain('docs/exec-plans/active/send-quotes.md is not filled in (empty: Purpose, Agreed decisions, Milestones)');

    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan(agreed: "1. <the first answer>\n2. <decision>", milestones: '- [ ] M1 <slice>: <what is built> + <tests>'));

    expect(finishCheckRun($repo, 'stop'))->toContain('(empty: Agreed decisions, Milestones)');

    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan());

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('a plan with every milestone ticked is sent back to be completed once composer check passed', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE);
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan(milestones: "- [x] M1 Actions: SendQuote + tests\n- [ ] M2 Pages: quote page + tests"));
    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop'))->toBe('');

    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan(milestones: "- [x] M1 Actions: SendQuote + tests\n- [x] M2 Pages: quote page + tests"));
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE."\$e = 5;\n");

    expect(finishCheckRun($repo, 'stop'))->toContain('Run composer check')->not->toContain('Every milestone');

    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop'))->toContain('Every milestone in docs/exec-plans/active/send-quotes.md is ticked and composer check passed. Fill in Outcomes & Retrospective and move the plan to docs/exec-plans/completed/');

    unlink("{$repo}/docs/exec-plans/active/send-quotes.md");
    finishCheckWrite($repo, 'docs/exec-plans/completed/send-quotes.md', finishCheckPlan("- 10:00 M2 done, composer check passed\n- 10:30 review round 1: 0 P0, 0 P1", 'Quotes can be sent; nothing is left.', milestones: '- [x] M1 Actions: SendQuote + tests'));

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
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan(decisions: ''));
    finishCheckWrite($repo, 'composer.json', "{\"require\": {}}\n");
    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop'))->toContain('You changed composer.json');

    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan('', ''));

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('only a Decision Log line added in this conversation and naming the file approves it', function (bool $committed) {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan());

    if ($committed) {
        finishCheckCommit($repo);
    }

    finishCheckRun($repo, 'start', 'second');
    finishCheckWrite($repo, 'composer.json', "{\"require\": {}}\n");
    finishCheckWrite($repo, 'composer.lock', "{}\n");
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan('- 2026-11-02 10:00 M1 started'));
    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop', 'second'))->toContain('You changed composer.json, composer.lock');

    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan('- 2026-11-02 10:00 M1 started', decisions: "- 2026-11-02 The user approved composer.json\n- 2026-11-02 The user approved the new package"));

    expect(finishCheckRun($repo, 'stop', 'second'))->toContain('You changed composer.json, composer.lock');

    // A lock file is approved with its manifest.
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan('- 2026-11-02 10:00 M1 started', decisions: "- 2026-11-02 The user approved composer.json\n- 2026-11-02 The user approved adding brick/math to composer.json"));

    expect(finishCheckRun($repo, 'stop', 'second'))->toBe('');
})->with(['committed' => [true], 'not yet committed' => [false]]);

test('moving a plan to completed/ makes none of its Decision Log lines new', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan());
    finishCheckCommit($repo);
    finishCheckRun($repo, 'start', 'second');

    finishCheckWrite($repo, 'composer.json', "{\"require\": {}}\n");
    unlink("{$repo}/docs/exec-plans/active/send-quotes.md");
    finishCheckWrite($repo, 'docs/exec-plans/completed/send-quotes.md', finishCheckPlan("- 10:00 M1 done, composer check passed\n- 10:30 review round 1: 0 P0, 0 P1", 'Quotes can be sent; nothing is left.'));
    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop', 'second'))->toContain('You changed composer.json');
});

test('stopping to ask about the protected files counts only when the message ends with the question', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan(decisions: ''));
    finishCheckWrite($repo, 'composer.json', "{\"require\": {}}\n");
    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop', message: "I added a package to composer.json.\n\nMay I keep it?\n"))->toBe('')
        ->and(finishCheckRun($repo, 'stop', message: '我在 composer.json 里加了一个包，可以保留吗？'))->toBe('')
        ->and(finishCheckRun($repo, 'stop', message: "May I keep the change to composer.json?\n\nThe rest is done."))->toContain('You changed composer.json')
        ->and(finishCheckRun($repo, 'stop', message: 'I added a package. May I keep it?'))->toContain('You changed composer.json');
});

test('the test setup, artisan, .gitignore, .npmrc and the workflow docs are protected', function () {
    $repo = finishCheckRepo();
    $paths = ['.npmrc', 'artisan', '.gitignore', 'tests/bootstrap.php', 'tests/Pest.php', 'tests/TestCase.php', 'tests/js/setup.ts', 'docs/workflow.md', 'docs/PLANS.md', 'docs/reference/tasks/send-quote.md'];

    foreach ($paths as $path) {
        finishCheckWrite($repo, $path, "changed\n");
    }

    preg_match('/You changed (.*) \(protected files/', finishCheckRun($repo, 'stop'), $protected);

    expect(explode(', ', $protected[1] ?? ''))->toEqualCanonicalizing($paths);
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

test('a step the AI stopped without is told to the user and to the next conversation, once', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = true;\n");

    foreach (range(1, 3) as $stop) {
        finishCheckRun($repo, 'stop');
    }

    expect(json_decode(finishCheckOutput($repo, 'stop'), true))->toBe([
        'systemMessage' => 'The finish check sent these steps back 3 times and let the AI stop without them: pass composer check. The next conversation is told about them.',
    ]);

    expect(finishCheckOutput($repo, 'start', 'next'))->toStartWith("The previous conversation stopped with these steps missing (docs/golden-principles.md, principle 9):\n- The code changed since the last passing composer check.")
        ->and(finishCheckOutput($repo, 'start', 'after-next'))->toBe('');

    // A step done later in the same conversation is not passed on.
    finishCheckRun($repo, 'stop');
    finishCheckPass($repo);

    expect(finishCheckOutput($repo, 'stop'))->toBe('')
        ->and(finishCheckOutput($repo, 'start', 'last'))->toBe('');
});

test('a stop that sends one step back and lets another go does both', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = true;\n");

    foreach (range(1, 3) as $stop) {
        finishCheckRun($repo, 'stop');
    }

    finishCheckWrite($repo, 'docs/exec-plans/tech-debt.md', "# Tech debt\n\n| Added | Where | What | Why deferred |\n| --- | --- | --- | --- |\n");
    $output = json_decode(finishCheckOutput($repo, 'stop'), true);

    expect($output['decision'])->toBe('block')
        ->and($output['reason'])->toContain('docs/QUALITY.md did not change')->not->toContain('Run composer check')
        ->and($output['systemMessage'])->toContain('without them: pass composer check.');

    expect(finishCheckOutput($repo, 'start', 'next'))->toContain('Run composer check')->not->toContain('docs/QUALITY.md');
});

test('a stop without a start record tells the user and counts changes from there', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE);

    expect(json_decode(finishCheckOutput($repo, 'stop', 'unknown'), true))->toBe([
        'systemMessage' => 'The finish check had no start record for this conversation, so it counts changes from now on.',
    ])->and(finishCheckOutput($repo, 'stop', 'unknown'))->toBe('');

    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE."\$e = 5;\n");

    expect(finishCheckRun($repo, 'stop', 'unknown'))->toContain('there is no plan');
});

test('an error inside the finish check is told to the user and lets the AI stop', function () {
    $repo = finishCheckRepo();
    // A state file that cannot be written.
    mkdir("{$repo}/.git/ezdev/session-".sha1('broken').'.json');

    $output = json_decode(finishCheckOutput($repo, 'stop', 'broken'), true, flags: JSON_THROW_ON_ERROR);

    expect($output)->toHaveKey('systemMessage')->not->toHaveKey('decision')
        ->and($output['systemMessage'])->toStartWith('The finish check failed: ');
});

test('both AI tools run the finish check, and a missing or broken script still tells the user', function () {
    $root = dirname(__DIR__, 3);
    $repo = finishCheckRepo();
    $missing = sys_get_temp_dir().'/ezdev-no-finish-check-'.bin2hex(random_bytes(6));
    mkdir($missing);
    $broken = finishCheckRepo();
    finishCheckWrite($broken, 'scripts/hooks/finish-check.php', "<?php\n\nthis is not php\n");
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $missing]))->run());

    $run = function (string $command, string $dir): string {
        $process = Process::fromShellCommandline($command, $dir, ['CLAUDE_PROJECT_DIR' => $dir]);
        $process->setInput(json_encode(['session_id' => 'test', 'cwd' => $dir, 'last_assistant_message' => 'Done.']) ?: '');
        $process->mustRun();

        return $process->getOutput();
    };

    foreach (['.claude/settings.json', '.codex/hooks.json'] as $config) {
        $hooks = json_decode((string) file_get_contents("{$root}/{$config}"), true)['hooks'];

        foreach (['SessionStart', 'Stop'] as $event) {
            $command = $hooks[$event][0]['hooks'][0]['command'];

            expect($run($command, $repo))->toBe('', "{$config} {$event}")
                ->and($run($command, $missing))->toBe("{\"systemMessage\":\"The finish check could not run.\"}\n", "{$config} {$event}")
                ->and($run($command, $broken))->toBe("{\"systemMessage\":\"The finish check could not run.\"}\n", "{$config} {$event}");
        }
    }
});

test('composer check records a pass only for the code it started on', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = true;\n");
    finishCheckComposer($repo, 'checking');
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = false;\n");
    finishCheckWrite($repo, 'storage/framework/testing/ezdev-tests-finished', (string) microtime(true));

    $checked = finishCheckComposer($repo, 'checked');

    expect($checked->getExitCode())->toBe(1)
        ->and($checked->getErrorOutput())->toContain('the code changed while composer check ran')
        ->and(finishCheckRun($repo, 'stop'))->toContain('Run composer check');
});

test('composer check records a pass only when its tests ran to their end', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = true;\n");

    expect(finishCheckComposer($repo, 'checked')->getErrorOutput())->toContain('composer check did not record its start');

    // Written by an earlier test run, before this check started.
    finishCheckWrite($repo, 'storage/framework/testing/ezdev-tests-finished', (string) microtime(true));
    finishCheckComposer($repo, 'checking');
    $checked = finishCheckComposer($repo, 'checked');

    expect($checked->getExitCode())->toBe(1)
        ->and($checked->getErrorOutput())->toContain('the tests did not run to their end')
        ->and(finishCheckRun($repo, 'stop'))->toContain('Run composer check');

    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop'))->toBe('');
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

    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', FINISH_CHECK_NEW_CODE);

    expect(finishCheckRun($repo, 'stop', 'second'))->toContain('there is no plan');
});
