<?php

use Symfony\Component\Process\Process;

require_once dirname(__DIR__, 3).'/scripts/check-run.php';

/*
| scripts/hooks/finish-check.php, run the way Claude Code and composer check
| run it, in a throwaway git repository: each test starts a conversation,
| changes files and reads what the script sends back when the AI is about to stop.
*/

/**
 * A new git repository with the files the finish check reads, committed.
 */
function finishCheckRepo(): string
{
    $repo = sys_get_temp_dir().'/ezdev-finish-check-'.bin2hex(random_bytes(6));
    $root = dirname(__DIR__, 3);

    finishCheckWrite($repo, 'scripts/check-run.php', file_get_contents("{$root}/scripts/check-run.php") ?: '');
    finishCheckWrite($repo, 'scripts/hooks/finish-check.php', file_get_contents("{$root}/scripts/hooks/finish-check.php") ?: '');
    finishCheckWrite($repo, 'docs/PLANS.md', file_get_contents("{$root}/docs/PLANS.md") ?: '');
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n");
    finishCheckWrite($repo, 'composer.json', "{\"scripts\": {\"test\": \"pest\"}}\n");
    finishCheckWrite($repo, 'package.json', "{\"scripts\": {\"build\": \"vite build\"}}\n");
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
 *
 * @param  array<string, mixed>  $input
 */
function finishCheckOutput(string $repo, string $mode, string $session = 'test', array $input = []): string
{
    $process = new Process(['php', 'scripts/hooks/finish-check.php', $mode], $repo);
    $process->setInput(json_encode([...['session_id' => $session, 'cwd' => $repo, 'stop_hook_active' => false, 'last_assistant_message' => 'Done.'], ...$input]) ?: '');
    $process->mustRun();

    return $process->getOutput();
}

/**
 * The reason the script sends back ('' when it lets the AI stop).
 */
function finishCheckRun(string $repo, string $mode, string $session = 'test', string $message = 'Done.'): string
{
    return json_decode(finishCheckOutput($repo, $mode, $session, ['last_assistant_message' => $message]), true)['reason'] ?? '';
}

/**
 * The user sends a message (UserPromptSubmit).
 */
function finishCheckSay(string $repo, string $prompt, string $session = 'test'): string
{
    return finishCheckOutput($repo, 'approve', $session, ['hook_event_name' => 'UserPromptSubmit', 'prompt' => $prompt]);
}

/**
 * Runs a step of composer check (checking or checked), which may refuse.
 */
function finishCheckComposer(string $repo, string $mode): Process
{
    static $runs = [];
    if ($mode === 'checking') {
        unset($runs[$repo]);
        $runs[$repo] = new CheckRun("{$repo}/.git/ezdev");
    }
    $process = new Process(['php', 'scripts/hooks/finish-check.php', $mode], $repo, ['EZDEV_CHECK_RUN_ID' => $runs[$repo]->id ?? false]);
    $process->run();
    if ($mode === 'checked') {
        if ($process->isSuccessful()) {
            $runs[$repo]->succeed();
        }
        unset($runs[$repo]);
    }

    return $process;
}

/**
 * A composer check that passed: it started, its tests ran to their end, it finished.
 */
function finishCheckPass(string $repo): void
{
    finishCheckComposer($repo, 'checking');
    $id = CheckRun::read("{$repo}/.git/ezdev/run.json")['id'];
    finishCheckWrite($repo, 'storage/framework/testing/ezdev-tests-finished', json_encode(['id' => $id, 'time' => microtime(true)]));

    expect(finishCheckComposer($repo, 'checked')->getErrorOutput())->toBe('');
}

function finishCheckPlan(
    string $progress = '',
    string $outcome = '',
    string $purpose = 'Sales staff send a quote to the customer by email.',
    string $agreed = '1. A quote is sent as a PDF attachment.',
    string $milestones = '- [ ] M1 Actions: SendQuote + tests',
    string $approved = '- Protected files: none.',
): string {
    return "# Send quotes\n\n## Purpose\n{$purpose}\n\n## Agreed decisions\n{$agreed}\n\n## Approved changes\n{$approved}\n\n## Milestones\n{$milestones}\n\n## Progress\n{$progress}\n\n## Decision Log\n\n## Outcomes & Retrospective\n{$outcome}\n";
}

/**
 * The files the reminder to revert names ([] when there is none).
 *
 * @return list<string>
 */
function finishCheckReverts(string $reason): array
{
    return preg_match('/Revert (.*?): protected/', $reason, $match) === 1 ? explode(', ', $match[1]) : [];
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
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan());

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('a plan from before the conversation counts for new code once it changes, or once the user approved it', function (bool $committed) {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan());

    if ($committed) {
        finishCheckCommit($repo);
    }

    finishCheckRun($repo, 'start', 'second');
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE);
    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop', 'second'))->toContain('there is no plan in docs/exec-plans/active/');

    finishCheckSay($repo, '开始', 'second');

    expect(finishCheckRun($repo, 'stop', 'second'))->toBe('');

    finishCheckRun($repo, 'start', 'third');
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE."\$e = 5;\n");
    finishCheckPass($repo);

    // The approval lives in the snapshot, also for the next conversation.
    expect(finishCheckRun($repo, 'stop', 'third'))->toBe('');
})->with(['committed' => [true], 'not yet committed' => [false]]);

test('a plan that still holds the template or its placeholders is no plan for the code', function () {
    $repo = finishCheckRepo();
    preg_match('/```markdown\n(.*?)```/s', (string) file_get_contents("{$repo}/docs/PLANS.md"), $template);
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', $template[1]);

    // Without code the plan is being written: nothing is asked yet.
    expect(finishCheckRun($repo, 'stop'))->toBe('');

    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE);
    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop'))->toContain('docs/exec-plans/active/send-quotes.md is not filled in (empty: Purpose, Agreed decisions, Milestones)');

    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan(agreed: "1. <the first answer>\n2. <decision>", milestones: '- [ ] M1 <slice>: <what is built> + <tests>'));

    expect(finishCheckRun($repo, 'stop'))->toContain('(empty: Agreed decisions, Milestones)');

    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan());

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('a plan with every milestone ticked needs a full composer check, and nothing else asks for the move or its records', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE);
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan(milestones: "- [x] M1 Actions: SendQuote + tests\n- [ ] M2 Pages: quote page + tests"));
    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop'))->toBe('');

    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan(milestones: "- [x] M1 Actions: SendQuote + tests\n- [x] M2 Pages: quote page + tests"));
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE."\$e = 5;\n");

    expect(finishCheckRun($repo, 'stop'))->toContain('Run composer check')->not->toContain('composer check:milestone');

    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop'))->toBe('');

    // Progress and Outcomes are not read: a completed plan without them lets the AI stop.
    unlink("{$repo}/docs/exec-plans/active/send-quotes.md");
    finishCheckWrite($repo, 'docs/exec-plans/completed/send-quotes.md', finishCheckPlan(milestones: '- [x] M1 Actions: SendQuote + tests'));

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('a one-line change needs no plan, but still a passing composer check', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = true;\n");

    $reason = finishCheckRun($repo, 'stop');

    expect($reason)->not->toContain('no plan')
        ->and($reason)->toContain('Run composer check');
});

test('removing a tech-debt line asks for nothing', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/tech-debt.md', "# Tech debt\n\n| Added | Where | What | Why deferred |\n| --- | --- | --- | --- |\n");

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('only the user\'s "开始", in any of its forms, snapshots the active plans with the commit they were taken at', function (string $prompt) {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan());
    finishCheckWrite($repo, 'docs/exec-plans/active/发送报价单.md', finishCheckPlan(purpose: '销售人员给客户发报价单。'));
    $head = trim((new Process(['git', 'rev-parse', 'HEAD'], $repo))->mustRun()->getOutput());

    expect(finishCheckSay($repo, $prompt))->toBe('');

    foreach (['send-quotes.md', '发送报价单.md'] as $name) {
        expect(file_get_contents("{$repo}/.git/ezdev/approved/{$name}"))->toBe(file_get_contents("{$repo}/docs/exec-plans/active/{$name}"))
            ->and(trim((string) file_get_contents("{$repo}/.git/ezdev/approved/{$name}.commit")))->toBe($head);
    }
})->with(['开始', '开始吧', '好，开始！', '好开始吧', '好的，开始。', 'OK 开始', 'ok，开始', 'Start.', ' start ']);

test('any other message takes no snapshot', function (string $prompt) {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan());

    expect(finishCheckSay($repo, $prompt))->toBe('')
        ->and(is_file("{$repo}/.git/ezdev/approved/send-quotes.md"))->toBeFalse();
})->with(['开始写计划吧', '先别开始', 'start the server', 'ok', '继续', '发布']);

test('a new "开始" takes a new snapshot, and what the AI writes into the plan after it approves nothing', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan());
    finishCheckSay($repo, '开始');
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan(approved: '- Protected files: docs/workflow.md'));
    finishCheckWrite($repo, 'docs/workflow.md', "changed\n");

    expect(finishCheckReverts(finishCheckRun($repo, 'stop')))->toBe(['docs/workflow.md']);

    finishCheckSay($repo, '好的，开始');

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('a protected file is approved when the snapshot\'s "Approved changes" names it or a folder above it, as a whole path', function (string $approved, string $path, bool $kept) {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan(approved: $approved));
    finishCheckSay($repo, '开始');
    finishCheckWrite($repo, $path, "changed\n");

    expect(finishCheckReverts(finishCheckRun($repo, 'stop')))->toBe($kept ? [] : [$path]);
})->with([
    'the file' => ['- Protected files: AGENTS.md, docs/workflow.md (decision 1)', 'docs/workflow.md', true],
    'the file in backticks' => ['- Protected files: `docs/rules/tests.md`.', 'docs/rules/tests.md', true],
    'a folder above it' => ['- Protected files: docs/design/ (the pattern files that repeat a rule)', 'docs/design/patterns/list.md', true],
    'a longer folder that starts the same' => ['- Protected files: tests/Unit/Hooks/', 'tests/Structure/CodeTest.php', false],
    'a file whose name starts the same' => ['- Protected files: scripts/screen-round.php', 'scripts/screen-round', false],
    'the same name in another folder' => ['- Protected files: docs/AGENTS.md', 'AGENTS.md', false],
    'not named' => ['- Protected files: none.', 'DESIGN.md', false],
]);

test('a protected change is sent back to be reverted, whatever the AI\'s last message asks', function (string $message) {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan());
    finishCheckSay($repo, '开始');
    finishCheckWrite($repo, 'docs/workflow.md', "changed\n");

    $reason = finishCheckRun($repo, 'stop', message: $message);

    expect(finishCheckReverts($reason))->toBe(['docs/workflow.md'])
        ->and($reason)->toContain('Approved changes')->toContain('report');
})->with(['Done.', "I changed docs/workflow.md.\n\nMay I keep it?", '我改了 docs/workflow.md，可以保留吗？']);

test('composer.json and package.json are protected in their dependency sections only, a lock file with its manifest', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan());
    finishCheckSay($repo, '开始');
    finishCheckWrite($repo, 'composer.json', "{\"scripts\": {\"test\": \"pest --parallel\"}}\n");
    finishCheckWrite($repo, 'package.json', "{\"scripts\": {\"build\": \"vite build --emptyOutDir\"}}\n");
    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop'))->toBe('');

    finishCheckWrite($repo, 'composer.json', "{\"scripts\": {\"test\": \"pest\"}, \"require\": {\"brick/math\": \"^0.14\"}}\n");
    finishCheckWrite($repo, 'composer.lock', "{}\n");
    finishCheckWrite($repo, 'package.json', "{\"scripts\": {\"build\": \"vite build\"}, \"devDependencies\": {\"vitest\": \"^4\"}}\n");
    finishCheckWrite($repo, 'package-lock.json', "{}\n");
    finishCheckPass($repo);

    expect(finishCheckReverts(finishCheckRun($repo, 'stop')))->toEqualCanonicalizing(['composer.json', 'composer.lock', 'package.json', 'package-lock.json']);

    finishCheckWrite($repo, 'docs/exec-plans/active/send-quotes.md', finishCheckPlan(approved: '- Dependencies: brick/math in composer.json (decision 1), vitest in package-lock.json (decision 1)'));
    finishCheckSay($repo, '开始');

    expect(finishCheckRun($repo, 'stop'))->toBe('');
});

test('the template\'s own files are protected, and a project\'s own files next to them are not', function () {
    $repo = finishCheckRepo();
    $template = ['DESIGN.md', '.npmrc', 'artisan', '.gitignore', 'tests/bootstrap.php', 'tests/Pest.php', 'tests/TestCase.php', 'tests/js/setup.ts', 'docs/workflow.md', 'docs/PLANS.md', 'docs/reference/tasks/README.md', 'docs/design/foundations.md', 'docs/design/patterns/list.md', 'tests/Support/TestsRowLocks.php', 'scripts/worktree-up', '.claude/agents/reviewer-opus.md', '.github/workflows/tests.yml'];
    $project = ['docs/design/patterns/quote-board.md', 'tests/Support/QuoteFactories.php', 'scripts/deploy', '.claude/agents/quote-writer.md', '.github/workflows/deploy.yml', 'docs/rules/quotes.md'];

    foreach ([...$template, ...$project] as $path) {
        finishCheckWrite($repo, $path, "changed\n");
    }

    expect(finishCheckReverts(finishCheckRun($repo, 'stop')))->toEqualCanonicalizing($template);
});

test('the protected list names only files the template has', function () {
    $root = dirname(__DIR__, 3);
    $listed = json_decode((new Process(['php', "{$root}/scripts/hooks/finish-check.php", 'protected'], $root))->mustRun()->getOutput(), true);

    expect($listed)->toBeArray()->not->toBeEmpty()
        ->and(array_values(array_filter($listed, fn (string $path): bool => ! is_file("{$root}/{$path}"))))->toBe([]);
});

test('the same reminder is sent back at most three times in a conversation, also when the code changes in between', function () {
    $repo = finishCheckRepo();

    $reasons = array_map(function (int $stop) use ($repo): string {
        finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = {$stop};\n");

        return finishCheckRun($repo, 'stop');
    }, range(1, 4));

    expect(array_map(fn (string $reason): bool => $reason !== '', $reasons))->toBe([true, true, true, false]);
});

test('a reminder whose text changes at every stop is still sent back at most three times', function (string $kind) {
    $repo = finishCheckRepo();
    $files = $kind === 'protected'
        ? ['docs/workflow.md', 'docs/PLANS.md', 'AGENTS.md', 'DESIGN.md']
        : ['app/Actions/Quotes/SendQuote.php', 'app/Actions/Quotes/AcceptQuote.php', 'app/Actions/Quotes/RejectQuote.php', 'app/Actions/Quotes/CopyQuote.php'];

    $reasons = array_map(function (string $file) use ($repo, $kind): string {
        finishCheckWrite($repo, $file, $kind === 'protected' ? "changed\n" : FINISH_CHECK_NEW_CODE);
        finishCheckPass($repo);

        return finishCheckRun($repo, 'stop');
    }, $files);

    expect(array_unique(array_slice($reasons, 0, 3)))->toHaveCount(3)
        ->and(array_map(fn (string $reason): bool => $reason !== '', $reasons))->toBe([true, true, true, false]);
})->with(['protected', 'plan']);

test('each reminder is one line', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE);
    finishCheckWrite($repo, 'docs/workflow.md', "changed\n");

    $lines = explode("\n", finishCheckRun($repo, 'stop'));

    expect($lines)->toHaveCount(4)
        ->and($lines[0])->toBe('Before you stop:')
        ->and(array_slice($lines, 1))->each->toStartWith('- ');
});

test('a step the AI stopped without is told to the user once per conversation, and the next conversation starts silent', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = true;\n");

    foreach (range(1, 3) as $stop) {
        finishCheckRun($repo, 'stop');
    }

    expect(json_decode(finishCheckOutput($repo, 'stop'), true))->toBe([
        'systemMessage' => 'The finish check let the AI stop without: pass composer check.',
    ])->and(finishCheckOutput($repo, 'stop'))->toBe('');

    finishCheckWrite($repo, 'docs/workflow.md', "changed\n");

    foreach (range(1, 3) as $stop) {
        finishCheckRun($repo, 'stop');
    }

    expect(finishCheckOutput($repo, 'stop'))->toBe('')
        ->and(finishCheckOutput($repo, 'start', 'next'))->toBe('');
});

test('a stop that sends one step back and lets another go does both', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = true;\n");

    foreach (range(1, 3) as $stop) {
        finishCheckRun($repo, 'stop');
    }

    finishCheckWrite($repo, 'docs/workflow.md', "changed\n");
    $output = json_decode(finishCheckOutput($repo, 'stop'), true);

    expect($output['decision'])->toBe('block')
        ->and(finishCheckReverts($output['reason']))->toBe(['docs/workflow.md'])
        ->and($output['reason'])->not->toContain('Run composer check')
        ->and($output['systemMessage'])->toBe('The finish check let the AI stop without: pass composer check.');
});

test('the start of a conversation says nothing', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = true;\n");

    foreach (range(1, 4) as $stop) {
        finishCheckRun($repo, 'stop');
    }

    expect(finishCheckOutput($repo, 'start', 'next'))->toBe('');
});

test('a stop without a start record tells the user and counts changes from there', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE);

    expect(json_decode(finishCheckOutput($repo, 'stop', 'unknown'), true))->toBe([
        'systemMessage' => 'The finish check had no start record for this conversation, so it counts changes from now on.',
    ])->and(finishCheckOutput($repo, 'stop', 'unknown'))->toBe('');

    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE."\$e = 5;\n");

    expect(finishCheckRun($repo, 'stop', 'unknown'))->toContain('there is no plan');

    // That was the one message of this conversation.
    foreach (range(1, 3) as $stop) {
        finishCheckRun($repo, 'stop', 'unknown');
    }

    expect(finishCheckOutput($repo, 'stop', 'unknown'))->toBe('');
});

test('an error inside the finish check is told to the user and lets the AI stop', function () {
    $repo = finishCheckRepo();
    // A state file that cannot be written.
    mkdir("{$repo}/.git/ezdev/session-".sha1('broken').'.json');

    $output = json_decode(finishCheckOutput($repo, 'stop', 'broken'), true, flags: JSON_THROW_ON_ERROR);

    expect($output)->toHaveKey('systemMessage')->not->toHaveKey('decision')
        ->and($output['systemMessage'])->toStartWith('The finish check failed: ');
});

test('Claude Code runs the finish check, and a missing or broken script still tells the user', function () {
    $root = dirname(__DIR__, 3);
    $repo = finishCheckRepo();
    $missing = sys_get_temp_dir().'/ezdev-no-finish-check-'.bin2hex(random_bytes(6));
    mkdir($missing);
    $broken = finishCheckRepo();
    finishCheckWrite($broken, 'scripts/hooks/finish-check.php', "<?php\n\nthis is not php\n");
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $missing]))->run());

    $run = function (string $command, string $dir): string {
        $process = Process::fromShellCommandline($command, $dir, ['CLAUDE_PROJECT_DIR' => $dir]);
        $process->setInput(json_encode(['session_id' => 'test', 'cwd' => $dir, 'prompt' => '开始', 'last_assistant_message' => 'Done.']) ?: '');
        $process->mustRun();

        return $process->getOutput();
    };

    // The template supports Claude Code only: no other tool's hook configuration is shipped.
    expect(glob("{$root}/.*/hooks.json"))->toBe([]);

    $hooks = json_decode((string) file_get_contents("{$root}/.claude/settings.json"), true)['hooks'];

    foreach (['SessionStart' => 'The finish check could not run.', 'UserPromptSubmit' => 'The approval of the plan could not be saved.', 'Stop' => 'The finish check could not run.'] as $event => $failure) {
        $command = $hooks[$event][0]['hooks'][0]['command'];

        expect($run($command, $repo))->toBe('', $event)
            ->and($run($command, $missing))->toBe("{\"systemMessage\":\"{$failure}\"}\n", $event)
            ->and($run($command, $broken))->toBe("{\"systemMessage\":\"{$failure}\"}\n", $event);
    }
});

test('the session has no question tool and no workflows, and its subagents run in the foreground', function () {
    $settings = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/.claude/settings.json'), true);

    expect($settings['permissions']['deny'] ?? [])->toContain('AskUserQuestion')->toContain('Workflow')
        ->and($settings['env']['CLAUDE_CODE_DISABLE_BACKGROUND_TASKS'] ?? null)->toBe('1');
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
    finishCheckWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', FINISH_CHECK_NEW_CODE);
    finishCheckWrite($repo, 'docs/exec-plans/active/发送报价单.md', finishCheckPlan(purpose: ''));
    finishCheckPass($repo);

    expect(finishCheckRun($repo, 'stop'))->toContain('docs/exec-plans/active/发送报价单.md is not filled in (empty: Purpose)');
});

test('a file already changed when the conversation started counts once it changes again', function () {
    $repo = finishCheckRepo();
    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$a = 1;\n");
    finishCheckRun($repo, 'start', 'second');

    expect(finishCheckRun($repo, 'stop', 'second'))->toBe('');

    finishCheckWrite($repo, 'app/Actions/Quotes/SendQuote.php', FINISH_CHECK_NEW_CODE);

    expect(finishCheckRun($repo, 'stop', 'second'))->toContain('there is no plan');
});
