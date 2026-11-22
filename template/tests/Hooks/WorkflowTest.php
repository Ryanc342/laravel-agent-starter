<?php

use Symfony\Component\Process\Process;

require_once dirname(__DIR__).'/Support/Workspace.php';

/*
| scripts/workflow.php, run the way Claude Code runs its hooks and the way the
| AI runs its commands, in a throwaway git repository with the template's
| cards: each test writes a plan, says 开始, changes files and reads what the
| tool answers.
*/

const WORKFLOW_PLAN = <<<'MD'
    # Quotes

    ## Purpose

    Let a customer accept a quote.

    ## Questions

    Q1 Who can accept a quote?
    Recommended: only its customer.
    Answer: yes

    ## Decisions

    D1 Only the quote's customer can accept it.
    D2 The quote mail reads well (no test: how a mail reads is a judgement)

    ## Screens

    None.

    MD;

/**
 * A new git repository with a committed file of each kind, files from before
 * the conversation dated an hour back, and a transcript that starts now.
 */
function workflowRepo(): string
{
    $repo = workspaceWithWorkflow('workflow');
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n");
    workspaceWrite($repo, 'docs/notes.md', "# Notes\n");
    workspaceWrite($repo, 'phpstan.neon', "parameters:\n");
    workspaceWrite($repo, '.gitignore', "/transcript.jsonl\n/storage/\n/tests/Browser/Screenshots/\n");
    workspaceGitInit($repo, time() - 3600);
    workflowTranscript($repo, time());

    return $repo;
}

/**
 * The conversation's transcript: its first entry says when it started.
 */
function workflowTranscript(string $repo, int $start): void
{
    workspaceWrite($repo, 'transcript.jsonl', json_encode(['type' => 'user', 'timestamp' => gmdate('Y-m-d\TH:i:s.000\Z', $start)])."\n");
}

/**
 * Runs the Stop hook as Claude Code does.
 *
 * @param  array<string, mixed>  $input
 * @param  array<string, string>  $env
 * @return array<string, mixed>
 */
function workflowStop(string $repo, array $input = [], array $env = []): array
{
    return workspaceHook($repo, 'stop', [...['session_id' => 'test', 'cwd' => $repo, 'stop_hook_active' => false, 'transcript_path' => "{$repo}/transcript.jsonl"], ...$input], $env);
}

/**
 * Runs a command of the tool and returns what it printed; a command that fails stops the test.
 */
function workflowCommand(string $repo, string ...$arguments): string
{
    $process = workspaceWorkflow($repo, $arguments);

    return $process->getExitCode() === 0 ? $process->getOutput() : throw new RuntimeException("workflow {$arguments[0]} failed: {$process->getErrorOutput()}");
}

/**
 * The user's message, through the UserPromptSubmit hook.
 *
 * @return array<string, mixed>
 */
function workflowSay(string $repo, string $prompt): array
{
    return workspaceHook($repo, 'user-prompt', ['cwd' => $repo, 'prompt' => $prompt]);
}

/**
 * A file tool about to write $path, through the PreToolUse hook.
 *
 * @return array<string, mixed>
 */
function workflowWrite(string $repo, string $path, string $tool = 'Write'): array
{
    return workspaceHook($repo, 'pre-tool', ['cwd' => $repo, 'tool_name' => $tool, 'tool_use_id' => 'write-1', 'tool_input' => ['file_path' => str_starts_with($path, '/') ? $path : "{$repo}/{$path}"]]);
}

/**
 * The reason PreToolUse gives for refusing a tool, or null when it lets it run.
 *
 * @param  array<string, mixed>  $answer
 */
function workflowDenied(array $answer): ?string
{
    return ($answer['hookSpecificOutput']['permissionDecision'] ?? null) === 'deny' ? $answer['hookSpecificOutput']['permissionDecisionReason'] : null;
}

/**
 * The plan, approved: written, then the user's 开始.
 */
function workflowApproved(string $repo, string $plan = WORKFLOW_PLAN): void
{
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', $plan);
    expect(workflowSay($repo, '开始')['hookSpecificOutput']['additionalContext'] ?? '')->toContain('The user approved plan docs/exec-plans/active/quotes.md');
}

/**
 * The plan's change: the code and the test that names decision D1.
 */
function workflowBuilt(string $repo): void
{
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");
    workspaceWrite($repo, 'tests/Feature/Quotes/AcceptQuoteTest.php', "<?php\n\ntest('only the customer accepts', function () {})->group('quotes.D1');\n");
}

/**
 * A composer check that passed on the code as it is now.
 */
function workflowPass(string $repo): void
{
    workflowCommand($repo, 'revoke');
    workflowCommand($repo, 'passed', trim(workflowCommand($repo, 'fingerprint')));
}

/**
 * The packs a review round printed.
 *
 * @return list<string>
 */
function workflowPacks(string $output): array
{
    preg_match_all('#^reviewer-haiku: Pack: \S+/(r\d+-[\w-]+)\.md$#m', $output, $packs);

    return $packs[1];
}

/**
 * A reviewer's answer, through the SubagentStop hook.
 *
 * @return array<string, mixed>
 */
function workflowResult(string $repo, string $pack, int $findings, string $before = ''): array
{
    return workspaceHook($repo, 'subagent-stop', ['cwd' => $repo, 'agent_type' => 'reviewer-haiku', 'last_assistant_message' => "{$before}RESULT pack={$pack} findings={$findings}"]);
}

/**
 * A review round in which every reviewer finds nothing.
 */
function workflowCleanRound(string $repo): void
{
    foreach (workflowPacks(workflowCommand($repo, 'review')) as $pack) {
        expect(workflowResult($repo, $pack, 0))->toBe([]);
    }
}

/**
 * The plan from the user's 开始 to its report.
 */
function workflowReported(string $repo): string
{
    workflowApproved($repo);
    workflowBuilt($repo);
    workflowPass($repo);
    workflowCleanRound($repo);
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', WORKFLOW_PLAN."\n## Waits for you\n\nNothing.\n");

    return workflowCommand($repo, 'report');
}

/**
 * Runs the Stop hook with a git that runs $fail (shell) for the git command
 * $command and is the real git otherwise.
 *
 * @return array<string, mixed>
 */
function workflowStopWithGit(string $repo, string $command, string $fail): array
{
    return workflowStop($repo, [], ['PATH' => workspaceFakeGit($repo, "for a in \"\$@\"; do [ \"\$a\" = {$command} ] && {$fail}; done")]);
}

// ---------------------------------------------------------------- the plan and 开始

test('with no plan the next step is to write one, and 开始 is held back', function () {
    $repo = workflowRepo();

    expect(workflowCommand($repo, 'status'))->toContain('No plan is open')
        ->and(workflowSay($repo, '开始'))->toBe(['decision' => 'block', 'reason' => 'There is no plan to start: docs/exec-plans/active/ is empty.']);
});

test('开始 with a question open is held back and names the question; once it is answered, 开始 approves the plan', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', str_replace('Answer: yes', "Answer:\n\nQ2 Can a quote be accepted twice?\nRecommended: no.\n**Answer:**", WORKFLOW_PLAN));

    expect(workflowCommand($repo, 'status'))->toContain('waits for the answers to Q1, Q2')
        ->and(workflowSay($repo, '开始'))->toBe(['decision' => 'block', 'reason' => 'The plan cannot start yet: Q1, Q2 have no answer. Answer them first.']);

    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', str_replace('Answer: yes', "Answer: yes\n\nQ2 Can a quote be accepted twice?\nRecommended: no.\n**Answer:** no", WORKFLOW_PLAN));

    expect(workflowCommand($repo, 'status'))->toContain('is ready. Give it to the user')
        ->and(workflowSay($repo, '好的开始！')['hookSpecificOutput']['additionalContext'])->toContain('Build the plan. D1 needs a test that names it (quotes.D1)')
        ->and(workflowCommand($repo, 'status'))->toStartWith('At step approved.');
});

test('a plan without the sections of docs/PLANS.md or without a decision cannot start', function (string $plan, string $problem) {
    $repo = workflowRepo();
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', $plan);

    expect(workflowSay($repo, 'start')['reason'] ?? '')->toContain($problem);
})->with([
    'no screens' => [str_replace("## Screens\n\nNone.\n", '', WORKFLOW_PLAN), 'it has no section ## Screens'],
    'no decision' => [preg_replace('/^D\d .*$/m', '', WORKFLOW_PLAN), 'it has no decision'],
]);

test('only a message that is 开始 or start approves; any other message passes untouched', function (string $prompt) {
    $repo = workflowRepo();
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', WORKFLOW_PLAN);

    expect(workflowSay($repo, $prompt))->toBe([])
        ->and(workflowCommand($repo, 'status'))->toStartWith('At step draft.');
})->with(['开始之前我想问一下', 'can we start with Q1?', '先不要开始']);

test('two plans in one folder are refused: code cannot change, commands and docs can', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', WORKFLOW_PLAN);
    workspaceWrite($repo, 'docs/exec-plans/active/invoices.md', WORKFLOW_PLAN);

    expect(workflowDenied(workflowWrite($repo, 'app/Actions/Quotes/AcceptQuote.php')))->toContain('holds 2 plans (invoices.md, quotes.md): one plan per folder')
        ->and(workflowWrite($repo, 'docs/exec-plans/active/invoices.md'))->toBe([])
        ->and(workspaceHook($repo, 'pre-tool', ['cwd' => $repo, 'tool_name' => 'Bash', 'tool_use_id' => 'b1', 'tool_input' => ['command' => 'git status']]))->toBe([])
        ->and(workflowSay($repo, '开始')['reason'])->toContain('one plan per folder');
});

// ---------------------------------------------------------------- code before 开始

test('before 开始 no file of code can be written, a doc can, and after 开始 code can', function (string $tool) {
    $repo = workflowRepo();

    expect(workflowDenied(workflowWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', $tool)))->toContain('No plan is approved, so app/Actions/Quotes/AcceptQuote.php cannot change yet')
        ->and(workflowWrite($repo, 'docs/exec-plans/active/quotes.md', $tool))->toBe([])
        ->and(workflowWrite($repo, 'README.md', $tool))->toBe([]);

    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', WORKFLOW_PLAN);

    expect(workflowDenied(workflowWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', $tool)))->toContain('Plan docs/exec-plans/active/quotes.md is not approved')
        ->and(workflowDenied(workflowWrite($repo, 'resources/js/pages/quotes/new.tsx', $tool)))->not->toBeNull();

    workflowSay($repo, '开始');

    expect(workflowWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', $tool))->toBe([]);
})->with(['Write', 'Edit', 'MultiEdit']);

test('a file outside the project is not the plan\'s business', function () {
    $repo = workflowRepo();
    $outside = workspaceFolder('outside');

    expect(workflowWrite($repo, "{$outside}/notes.php"))->toBe([])
        ->and(workflowDenied(workflowWrite($repo, "{$repo}/app/New/Folder/Quote.php")))->not->toBeNull()
        ->and(workflowDenied(workflowWrite($repo, "{$repo}/.github/workflows/tests.yml")))->toContain('.github/workflows/tests.yml cannot change yet');
});

test('an edit of the approved plan voids the approval; what waits for the user does not', function () {
    $repo = workflowRepo();
    workflowApproved($repo);
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', WORKFLOW_PLAN."\n## Waits for you\n\nNothing.\n");

    expect(workflowCommand($repo, 'status'))->toStartWith('At step approved.');

    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', str_replace('can accept it.', 'or an admin can accept it.', WORKFLOW_PLAN));

    expect(workflowCommand($repo, 'status'))->toStartWith('At step draft.')
        ->and(workflowDenied(workflowWrite($repo, 'app/Actions/Quotes/AcceptQuote.php')))->toContain('An edit of an approved plan voids its approval');

    workflowSay($repo, '开始');

    expect(workflowWrite($repo, 'app/Actions/Quotes/AcceptQuote.php'))->toBe([]);
});

test('a command that changed code before 开始 is told to put it back; after 开始 commands are not watched', function () {
    $repo = workflowRepo();
    $command = fn (string $hook, string $id) => workspaceHook($repo, $hook, ['cwd' => $repo, 'tool_name' => 'Bash', 'tool_use_id' => $id, 'tool_input' => ['command' => 'php fix.php']]);

    expect($command('pre-tool', 'b1'))->toBe([]);
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed by a command\n");
    workspaceWrite($repo, 'docs/notes.md', "# Notes, changed by a command\n");
    $answer = $command('post-tool', 'b1');

    expect($answer['decision'])->toBe('block')
        ->and($answer['reason'])->toContain('That command changed code before the plan was approved: app/Actions/Quotes/SendQuote.php.');

    // A command that changed no code, and one whose start was not seen, pass.
    expect($command('pre-tool', 'b2'))->toBe([])
        ->and($command('post-tool', 'b2'))->toBe([])
        ->and($command('post-tool', 'never-started'))->toBe([]);

    workflowApproved($repo);
    $command('pre-tool', 'b3');
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n");

    expect($command('post-tool', 'b3'))->toBe([]);
});

// ---------------------------------------------------------------- built and checked

test('each decision and screen needs a test that names it, except a decision that says no test can prove it', function () {
    $repo = workflowRepo();
    workflowApproved($repo, str_replace("## Screens\n\nNone.", "## Screens\n\nS1 The quote page: the total, and an Accept button.", WORKFLOW_PLAN));

    expect(workflowCommand($repo, 'status'))->toContain('D1, S1 each need a test that names it (quotes.D1)')->not->toContain('D2');

    // A test that only mentions the name in a comment, or a longer name, does not count.
    workspaceWrite($repo, 'tests/Feature/QuoteTest.php', "<?php\n\n// quotes.D1\ntest('x', function () {})->group('quotes.D10');\n");

    expect(workflowCommand($repo, 'status'))->toContain('D1, S1 each need');

    workflowBuilt($repo);
    workspaceWrite($repo, 'tests/js/quote-page.test.tsx', "it('quotes.S1 shows the total and the Accept button', () => {});\n");

    expect(workflowCommand($repo, 'status'))->toBe("At step built. Next: Run composer check and fix every failure.\n");
});

test('a check counts only when it passed after 开始 on the code as it is now', function () {
    $repo = workflowRepo();
    workflowBuilt($repo);
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', WORKFLOW_PLAN);
    workflowPass($repo);
    workflowSay($repo, '开始');

    expect(workflowCommand($repo, 'status'))->toStartWith('At step built.');

    workflowPass($repo);

    expect(workflowCommand($repo, 'status'))->toStartWith('At step checked.');

    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = false;\n");

    expect(workflowCommand($repo, 'status'))->toStartWith('At step built.');
});

test('a pass is recorded only for the code the check started on', function () {
    $repo = workflowRepo();
    workflowApproved($repo);
    workflowBuilt($repo);
    $fingerprint = trim(workflowCommand($repo, 'fingerprint'));
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = false;\n");

    $passed = workspaceWorkflow($repo, ['passed', $fingerprint]);

    expect($passed->getExitCode())->toBe(1)
        ->and($passed->getErrorOutput())->toContain('the code changed while the check ran')
        ->and(workflowCommand($repo, 'status'))->toStartWith('At step built.');
});

test('a check that starts forgets the last pass', function () {
    $repo = workflowRepo();
    workflowApproved($repo);
    workflowBuilt($repo);
    workflowPass($repo);
    workflowCommand($repo, 'revoke');

    expect(workflowCommand($repo, 'status'))->toStartWith('At step built.');
});

// ---------------------------------------------------------------- review

test('a review round needs a passing check', function () {
    $repo = workflowRepo();
    workflowApproved($repo);
    workflowBuilt($repo);
    $review = workspaceWorkflow($repo, ['review']);

    expect($review->getExitCode())->toBe(1)
        ->and($review->getErrorOutput())->toBe("A review round needs a composer check that passed on the code as it is now. Next: Run composer check and fix every failure.\n");
});

test('a round packs the reviewer rules of the cards the change touches, ten at most, plus the global rules, with the plan and the diff', function () {
    $repo = workflowRepo();
    workflowApproved($repo);
    workflowBuilt($repo);
    workflowPass($repo);
    $packs = workflowPacks(workflowCommand($repo, 'review'));
    $folder = "{$repo}/storage/framework/testing/ezdev-review/quotes";
    $global = file_get_contents("{$folder}/r1-global.md") ?: throw new RuntimeException('no global pack');
    $first = file_get_contents("{$folder}/r1-p1.md") ?: throw new RuntimeException('no first pack');

    expect($packs)->toContain('r1-p1')->toContain('r1-global')
        ->and(file_get_contents("{$folder}/plan.md"))->toBe(WORKFLOW_PLAN)
        ->and(file_get_contents("{$folder}/change.diff"))->toContain('+$accepted = true;')->toContain('tests/Feature/Quotes/AcceptQuoteTest.php')
        ->and($global)->toContain('## ALL-2')->toContain('## FLOW-1')->toContain('## quotes.D2')->toContain('how a mail reads is a judgement')
        ->and($global)->toContain('The last line, exactly: `RESULT pack=r1-global findings=<the number of findings>`')
        ->and($first)->toContain('## ACT-')->toContain('Files: app/Actions/Quotes/AcceptQuote.php')->toContain('Check: Look at:')
        ->and($first)->not->toContain('## PAGE-');

    foreach (array_diff($packs, ['r1-global']) as $pack) {
        expect(substr_count(file_get_contents("{$folder}/{$pack}.md") ?: '', "\n## "))->toBeGreaterThan(0)
            ->and(substr_count(file_get_contents("{$folder}/{$pack}.md") ?: '', "\n## ") <= 10)->toBeTrue();
    }
});

test('a finding keeps its pack\'s cards open; the next round reviews only the open cards and the cards whose files changed', function () {
    $repo = workflowRepo();
    workflowApproved($repo);
    workflowBuilt($repo);
    workspaceWrite($repo, 'resources/js/pages/quotes/show.tsx', "export default function Show() { return null; }\n");
    workflowPass($repo);
    $first = workflowPacks(workflowCommand($repo, 'review'));

    foreach ($first as $pack) {
        workflowResult($repo, $pack, $pack === 'r1-global' ? 1 : 0);
    }

    expect(workflowCommand($repo, 'status'))->toContain('At step checked. Next: Run php scripts/workflow.php review');

    // The fix touches only the page: the global rules and the page's cards are reviewed again, the operation's are not.
    workspaceWrite($repo, 'resources/js/pages/quotes/show.tsx', "export default function Show() { return <p>Quote</p>; }\n");
    workflowPass($repo);
    $second = workflowPacks(workflowCommand($repo, 'review'));
    $text = implode("\n", array_map(fn (string $pack): string => file_get_contents("{$repo}/storage/framework/testing/ezdev-review/quotes/{$pack}.md") ?: '', $second));

    expect($second)->toContain('r2-global')
        ->and($text)->toContain('## PAGE-7')->not->toContain('## ACT-');

    foreach ($second as $pack) {
        workflowResult($repo, $pack, 0);
    }

    expect(workflowCommand($repo, 'status'))->toStartWith('At step reviewed.')
        ->and(workflowCommand($repo, 'review'))->toContain('The review is done');
});

test('a round still waiting for runs is not started again, and a result for other code or another round does not count', function () {
    $repo = workflowRepo();
    workflowApproved($repo);
    workflowBuilt($repo);
    workflowPass($repo);
    $packs = workflowPacks(workflowCommand($repo, 'review'));
    workflowResult($repo, $packs[0], 0);

    expect(workflowCommand($repo, 'status'))->toContain('Wait for the reviewer runs of round 1')
        ->and(workflowPacks(workflowCommand($repo, 'review')))->toBe(array_slice($packs, 1))
        ->and(workflowResult($repo, 'r9-global', 0)['systemMessage'])->toContain('without a RESULT line for a pack of the current review round');

    // A result given while the code was not the code under review does not count.
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = 1;\n");

    foreach (array_slice($packs, 1) as $pack) {
        workflowResult($repo, $pack, 0);
    }

    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");

    expect(workflowCommand($repo, 'status'))->toContain('Wait for the reviewer runs of round 1');
});

test('a reviewer that ends without its RESULT line counts as not run', function (string $message) {
    $repo = workflowRepo();
    workflowApproved($repo);
    workflowBuilt($repo);
    workflowPass($repo);
    workflowCommand($repo, 'review');
    $answer = workspaceHook($repo, 'subagent-stop', ['cwd' => $repo, 'agent_type' => 'reviewer-haiku', 'last_assistant_message' => $message]);

    expect($answer['systemMessage'])->toBe('A reviewer ended without a RESULT line for a pack of the current review round: its run counts as not run.')
        ->and(workflowCommand($repo, 'status'))->toContain('Wait for the reviewer runs of round 1');
})->with([
    'no line' => 'Looks fine to me.',
    'not the last line' => "RESULT pack=r1-global findings=0\nOne more thought.",
    'no number' => 'RESULT pack=r1-global findings=none',
]);

test('a changed screenshot baseline of a page the plan does not list holds back the review', function () {
    $repo = workflowRepo();
    $baseline = 'tests/.pest/snapshots/Browser/PagesTest/the_pages_of_a_signed_in_user_with_data_set__dataset__dashboard____dataset__phone__dark__.snap';
    workspaceWrite($repo, $baseline, "before\n");
    workspaceCommit($repo, time() - 3600);
    workflowApproved($repo);
    workflowBuilt($repo);
    workspaceWrite($repo, $baseline, "after\n");
    workflowPass($repo);
    $review = workspaceWorkflow($repo, ['review']);

    expect(workflowCommand($repo, 'status'))->toContain('The screenshot baselines of pages the plan does not list changed: '.$baseline)
        ->and($review->getExitCode())->toBe(1)
        ->and($review->getErrorOutput())->toContain('the page goes under ## Screens');

    // The plan lists the page: its baselines may change.
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', str_replace("## Screens\n\nNone.", "## Screens\n\nS1 dashboard: the latest quotes next to the notifications.", WORKFLOW_PLAN));
    workflowSay($repo, '开始');
    workspaceWrite($repo, 'tests/Browser/DashboardTest.php', "<?php\n\ntest('quotes.S1 the dashboard', function () {})->group('quotes.S1');\n");
    workflowPass($repo);

    expect(workflowCommand($repo, 'status'))->toContain('At step checked. Next: Run php scripts/workflow.php review');
});

test('a screen\'s own browser test may make its baseline', function () {
    $repo = workflowRepo();
    workflowApproved($repo, str_replace("## Screens\n\nNone.", "## Screens\n\nS1 The quote page: the total, and an Accept button.", WORKFLOW_PLAN));
    workflowBuilt($repo);
    workspaceWrite($repo, 'tests/Browser/QuotePageTest.php', "<?php\n\ntest('quotes.S1 the quote page', function () {})->group('quotes.S1');\n");
    workspaceWrite($repo, 'tests/.pest/snapshots/Browser/QuotePageTest/quotes_S1_the_quote_page.snap', "png\n");
    workflowPass($repo);

    expect(workflowCommand($repo, 'status'))->toContain('At step checked. Next: Run php scripts/workflow.php review');
});

test('the template\'s own tools are not reviewed, and a change to nothing else needs no reviewer', function () {
    $repo = workflowRepo();
    workflowApproved($repo);
    workspaceWrite($repo, 'scripts/tool.php', "<?php\n");
    workspaceWrite($repo, 'template/tests/Hooks/ToolTest.php', "<?php\n\ntest('the tool', function () {})->group('quotes.D1');\n");
    workflowPass($repo);

    expect(workflowCommand($repo, 'review'))->toContain('Nothing is left to review')
        ->and(workflowCommand($repo, 'status'))->toStartWith('At step reviewed.');
});

test('a sync plan leaves out of the review the files that are as they are in the template commit it names', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$fromTemplate = true;\n");
    workspaceCommit($repo, time() - 3600);
    $template = trim((new Process(['git', 'rev-parse', 'HEAD'], $repo))->mustRun()->getOutput());
    workspaceRemove("{$repo}/app/Actions/Quotes/AcceptQuote.php");
    workspaceCommit($repo, time() - 3600);
    workflowApproved($repo, str_replace("## Decisions\n", "## Decisions\n\nTemplate: `{$template}`\n", WORKFLOW_PLAN));
    workflowBuilt($repo);
    // As in the template, so not reviewed; and a file of the project's own, which is.
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$fromTemplate = true;\n");
    workspaceWrite($repo, 'app/Actions/Quotes/DeclineQuote.php', "<?php\n");
    workflowPass($repo);
    workflowCommand($repo, 'review');
    $diff = file_get_contents("{$repo}/storage/framework/testing/ezdev-review/quotes/change.diff") ?: '';

    expect($diff)->toContain('DeclineQuote.php')->not->toContain('AcceptQuote.php');
});

test('a sync plan whose template commit is not in the repository cannot be reviewed', function () {
    $repo = workflowRepo();
    workflowApproved($repo, str_replace("## Decisions\n", "## Decisions\n\nTemplate: 0123456789abcdef0123456789abcdef01234567\n", WORKFLOW_PLAN));
    workflowBuilt($repo);
    workflowPass($repo);
    $review = workspaceWorkflow($repo, ['review']);

    expect($review->getExitCode())->toBe(1)
        ->and($review->getErrorOutput())->toContain("the plan's template commit 0123456789abcdef0123456789abcdef01234567 is not in this repository");
});

test('the diff holds new files, empty ones and names git would quote', function () {
    $repo = workflowRepo();
    workflowApproved($repo);
    workflowBuilt($repo);
    workspaceWrite($repo, 'app/Actions/Quotes/Quote "draft".php', "<?php\n");
    workspaceWrite($repo, 'app/Actions/Quotes/Empty.php', '');
    workflowPass($repo);
    workflowCommand($repo, 'review');
    $diff = file_get_contents("{$repo}/storage/framework/testing/ezdev-review/quotes/change.diff") ?: '';

    // git quotes such a name in a diff's headers, whatever core.quotePath says.
    expect($diff)->toContain('app/Actions/Quotes/Quote \\"draft\\".php')->toContain("new file mode 100644\nindex 0000000..e69de29\n");
});

// ---------------------------------------------------------------- report

test('the report needs a reviewed plan and what waits for the user', function () {
    $repo = workflowRepo();
    workflowApproved($repo);
    workflowBuilt($repo);
    workflowPass($repo);

    expect(workspaceWorkflow($repo, ['report'])->getErrorOutput())->toContain('The report needs a plan reviewed on the code as it is now.');

    workflowCleanRound($repo);

    expect(workspaceWorkflow($repo, ['report'])->getErrorOutput())->toContain('Write what waits for the user under ## Waits for you');
});

test('the report proves each decision, lists the rule gaps and the existing debt, and moves the plan to completed/', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'docs/exec-plans/tech-debt.md', "# Existing debt\n\n- app/Models/User.php:9: an old problem\n");
    workspaceCommit($repo, time() - 3600);
    workflowApproved($repo);
    workflowBuilt($repo);
    workflowPass($repo);
    $packs = workflowPacks(workflowCommand($repo, 'review'));

    foreach ($packs as $pack) {
        workflowResult($repo, $pack, 0, $pack === 'r1-global' ? "Rule gaps:\n- a quote with no customer\n\nOlder code:\n- app/Actions/Quotes/SendQuote.php:1: sends without a lock\n- None\n\n" : '');
    }

    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', WORKFLOW_PLAN."\n## Waits for you\n\nWhether an admin may accept a quote.\n");

    expect(workflowCommand($repo, 'report'))->toBe("The report is at the end of docs/exec-plans/completed/quotes.md. Give it to the user in their language.\n");

    $report = file_get_contents("{$repo}/docs/exec-plans/completed/quotes.md") ?: '';

    expect(is_file("{$repo}/docs/exec-plans/active/quotes.md"))->toBeFalse()
        ->and($report)->toStartWith(WORKFLOW_PLAN)
        ->and($report)->toContain("- D1 Only the quote's customer can accept it.\n  Proved by the tests named quotes.D1, which passed in composer check.")
        ->and($report)->toContain("- D2 The quote mail reads well (no test: how a mail reads is a judgement)\n  Checked by the reviewers.")
        ->and($report)->toContain("### Rule gaps\n\n- a quote with no customer\n")
        ->and($report)->toContain("### Waits for you\n\nWhether an admin may accept a quote.")
        ->and($report)->toEndWith("### Existing debt\n\n- 2 items in docs/exec-plans/tech-debt.md, 1 of them found in this review.\n")
        ->and(file_get_contents("{$repo}/docs/exec-plans/tech-debt.md"))->toBe("# Existing debt\n\n- app/Models/User.php:9: an old problem\n- app/Actions/Quotes/SendQuote.php:1: sends without a lock (found while reviewing quotes)\n")
        ->and(is_dir("{$repo}/storage/framework/testing/ezdev-review/quotes"))->toBeFalse()
        ->and(workflowCommand($repo, 'status'))->toContain('The last plan is reported.');
});

test('the report keeps a copy of the screenshots each screen\'s test took, which the next test run would remove', function () {
    $repo = workflowRepo();
    workflowApproved($repo, str_replace("## Screens\n\nNone.", "## Screens\n\nS1 The quote page: the total, and an Accept button.", WORKFLOW_PLAN));
    workflowBuilt($repo);
    workspaceWrite($repo, 'tests/Browser/QuotePageTest.php', "<?php\n\ntest('the quote page', function () {})->group('quotes.S1');\n");
    workspaceWrite($repo, 'tests/Browser/Screenshots/quotes.S1.png', 'png');
    workspaceWrite($repo, 'tests/Browser/Screenshots/quotes.S1-phone.png', 'png');
    workflowPass($repo);
    workflowCleanRound($repo);
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', file_get_contents("{$repo}/docs/exec-plans/active/quotes.md")."\n## Waits for you\n\nNothing.\n");
    workflowCommand($repo, 'report');

    expect(file_get_contents("{$repo}/docs/exec-plans/completed/quotes.md"))->toContain('screenshots: storage/app/private/reports/quotes/quotes.S1-phone.png, storage/app/private/reports/quotes/quotes.S1.png.')
        ->and(file_get_contents("{$repo}/storage/app/private/reports/quotes/quotes.S1.png"))->toBe('png');
});

test('at ten items of existing debt the report brings a draft cleanup plan', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'docs/exec-plans/tech-debt.md', "# Existing debt\n\n".implode('', array_map(fn (int $n): string => "- app/Old{$n}.php:1: problem {$n}\n", range(1, 10))));
    workspaceCommit($repo, time() - 3600);
    workflowReported($repo);
    $cleanup = file_get_contents("{$repo}/docs/exec-plans/active/cleanup.md") ?: '';

    expect(file_get_contents("{$repo}/docs/exec-plans/completed/quotes.md"))->toContain('That is 10 or more: a draft cleanup plan waits in docs/exec-plans/active/cleanup.md')
        ->and($cleanup)->toContain("Q1 Fix this now? app/Old1.php:1: problem 1\nRecommended:")->toContain('Q10 Fix this now? app/Old10.php:1: problem 10')
        ->and(workflowSay($repo, '开始')['reason'])->toContain('have no answer');
});

test('in the template itself a reported plan goes to template/plans/', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'template/plans/template-1.6.0.md', "# 1.6.0\n");
    workspaceCommit($repo, time() - 3600);

    expect(workflowReported($repo))->toContain('template/plans/quotes.md')
        ->and(is_file("{$repo}/template/plans/quotes.md"))->toBeTrue();
});

// ---------------------------------------------------------------- cleanup and upgrade

test('a cleanup plan asks one question for each item of existing debt, and waits for an open plan to finish', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'docs/exec-plans/tech-debt.md', "# Existing debt\n\n- app/Old.php:1: a problem (found while reviewing tasks)\n");

    expect(workflowCommand($repo, 'cleanup'))->toContain('A draft cleanup plan is at docs/exec-plans/active/cleanup.md')
        ->and(file_get_contents("{$repo}/docs/exec-plans/active/cleanup.md"))->toContain("## Questions\n\nQ1 Fix this now? app/Old.php:1: a problem\nRecommended: <yes or later, and why>\nAnswer:\n");

    $again = workspaceWorkflow($repo, ['upgrade']);

    expect($again->getExitCode())->toBe(1)
        ->and($again->getErrorOutput())->toContain('docs/exec-plans/active/cleanup.md is open: finish it first');
});

test('an upgrade plan asks about each outdated package, each package with security advisories and each template merge conflict', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'bin/composer', "#!/bin/sh\ncase \"\$1\" in\noutdated) echo '{\"installed\":[{\"name\":\"laravel/framework\",\"version\":\"v13.1.0\",\"latest\":\"v13.4.0\"}]}';;\naudit) echo '{\"advisories\":{\"guzzlehttp/psr7\":[{\"advisoryId\":\"PKSA-1\",\"title\":\"Header injection\"}]},\"abandoned\":[]}'; exit 1;;\nesac\n");
    workspaceWrite($repo, 'bin/npm', "#!/bin/sh\necho '{\"vite\":{\"current\":\"7.1.0\",\"wanted\":\"7.1.0\",\"latest\":\"7.2.0\"}}'; exit 1\n");
    chmod("{$repo}/bin/composer", 0755) && chmod("{$repo}/bin/npm", 0755) ?: throw new RuntimeException('cannot make the stubs runnable');
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n<<<<<<< before updating\n\$a = 1;\n=======\n\$a = 2;\n>>>>>>> after updating\n");
    $process = workspaceWorkflow($repo, ['upgrade'], ['PATH' => "{$repo}/bin:".getenv('PATH')]);
    $plan = file_get_contents("{$repo}/docs/exec-plans/active/upgrade.md") ?: '';

    expect($process->getExitCode())->toBe(0, $process->getErrorOutput())
        ->and($plan)->toContain('Q1 Upgrade laravel/framework from v13.1.0 to v13.4.0?')
        ->and($plan)->toContain('Q2 Upgrade vite from 7.1.0 to 7.2.0?')
        ->and($plan)->toContain('Q3 Fix the security advisories of guzzlehttp/psr7: Header injection?')
        ->and($plan)->toContain('Q4 app/Actions/Quotes/SendQuote.php has a merge conflict from the template release');
});

test('an upgrade plan whose package list cannot be read is not written', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'bin/composer', "#!/bin/sh\necho 'Your requirements could not be resolved' >&2; exit 2\n");
    chmod("{$repo}/bin/composer", 0755) ?: throw new RuntimeException('cannot make the stub runnable');
    $process = workspaceWorkflow($repo, ['upgrade'], ['PATH' => "{$repo}/bin:".getenv('PATH')]);

    expect($process->getExitCode())->toBe(1)
        ->and($process->getErrorOutput())->toContain('composer outdated --direct --format=json failed: Your requirements could not be resolved')
        ->and(is_file("{$repo}/docs/exec-plans/active/upgrade.md"))->toBeFalse();
});

// ---------------------------------------------------------------- the Stop hook

test('a conversation that changed no code may stop, whatever step the plan is at', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', WORKFLOW_PLAN);

    expect(workflowStop($repo))->toBe([]);
});

test('code changed with no approved plan is sent back once, and the next stop goes through with one line for the user', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");

    $first = workflowStop($repo);
    $again = workflowStop($repo, ['stop_hook_active' => true]);

    expect($first['decision'])->toBe('block')
        ->and($first['reason'])->toContain('This conversation changed code (app/Actions/Quotes/AcceptQuote.php) with no approved plan.')
        ->and($first['reason'])->not->toContain("\n")
        ->and($again)->not->toHaveKey('decision')
        ->and($again['systemMessage'])->toStartWith('The AI stopped before the work was done.');
});

test('the stop names the missing step until the plan is reported, then lets the AI stop', function () {
    $repo = workflowRepo();
    workflowApproved($repo);
    workflowBuilt($repo);

    expect(workflowStop($repo)['reason'])->toBe('This conversation changed code and the plan is at step built. Next: Run composer check and fix every failure.');

    workflowPass($repo);

    expect(workflowStop($repo)['reason'])->toContain('the plan is at step checked. Next: Run php scripts/workflow.php review');

    workflowCleanRound($repo);
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', WORKFLOW_PLAN."\n## Waits for you\n\nNothing.\n");
    workflowCommand($repo, 'report');

    expect(workflowStop($repo))->toBe([]);

    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = 'after the report';\n");

    expect(workflowStop($repo)['reason'] ?? '')->toContain('with no approved plan');
});

test('a new conversation is told where the plan is', function () {
    $repo = workflowRepo();
    workflowApproved($repo);

    expect(workspaceHook($repo, 'session-start', ['cwd' => $repo, 'source' => 'startup']))->toBe(['hookSpecificOutput' => [
        'hookEventName' => 'SessionStart',
        'additionalContext' => 'Workflow: '.trim(workflowCommand($repo, 'status')),
    ]]);
});

test('code committed in this conversation counts as changed', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', "<?php\n\$accepted = true;\n");
    workspaceCommit($repo);

    expect(workflowStop($repo)['decision'] ?? null)->toBe('block');
});

test('code changed before the conversation started is not this conversation\'s change', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\$earlier = true;\n", time() - 600);

    expect(workflowStop($repo))->toBe([]);
});

test('a file deleted before the conversation started is not this conversation\'s change', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/Old/OldQuote.php', "<?php\n");
    workspaceCommit($repo, time() - 3600);
    // Deleting a file changes the time of its folder, here before the conversation.
    workspaceRemove("{$repo}/app/Actions/Quotes/SendQuote.php");
    touch("{$repo}/app/Actions/Quotes", time() - 600) ?: throw new RuntimeException('cannot date the folder');
    // A folder deleted with its file: the nearest folder that is still there tells.
    workspaceRemove("{$repo}/app/Actions/Quotes/Old");
    touch("{$repo}/app/Actions/Quotes", time() - 600) ?: throw new RuntimeException('cannot date the folder');

    expect(workflowStop($repo))->toBe([]);
});

test('a file deleted in the conversation is this conversation\'s change', function (string $removed) {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/Old/OldQuote.php', "<?php\n");
    workspaceCommit($repo, time() - 3600);
    touch("{$repo}/app/Actions/Quotes", time() - 600) ?: throw new RuntimeException('cannot date the folder');
    touch("{$repo}/app/Actions/Quotes/Old", time() - 600) ?: throw new RuntimeException('cannot date the folder');
    workspaceRemove("{$repo}/{$removed}");

    expect(workflowStop($repo)['decision'] ?? null)->toBe('block');
})->with(['a file' => 'app/Actions/Quotes/SendQuote.php', 'a folder with its file' => 'app/Actions/Quotes/Old']);

test('code whose name git would quote counts like any code, changed or committed', function (bool $commit) {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/Quote "draft".php', "<?php\n");

    if ($commit) {
        workspaceCommit($repo);
    }

    expect(workflowStop($repo)['reason'] ?? null)->toContain('app/Actions/Quotes/Quote "draft".php');
})->with(['uncommitted' => false, 'committed' => true]);

test('without a transcript every uncommitted code change counts', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\$earlier = true;\n", time() - 600);

    expect(workflowStop($repo, ['transcript_path' => null])['decision'] ?? null)->toBe('block');
});

test('Markdown and docs are not code', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'docs/notes.md', "# Notes\n\nMore.\n");
    workspaceWrite($repo, 'docs/exec-plans/active/quotes.md', "# Quotes\n");
    workspaceWrite($repo, 'README.md', "# Readme\n");

    expect(workflowStop($repo))->toBe([]);
});

test('a file list from git without a path stops the fingerprint, never counts as code', function () {
    $repo = workflowRepo();
    $path = workspaceFakeGit($repo, 'case "$*" in *"ls-files -s"*) echo "100644 broken"; exit 0;; esac');

    $process = workspaceWorkflow($repo, ['fingerprint'], ['PATH' => $path]);

    expect($process->getExitCode())->toBe(1)
        ->and($process->getErrorOutput())->toContain('git ls-files gave a line without a path: 100644 broken');
});

test('a changed check file gives the user one line, does not block, and is told once', function (string $path) {
    $repo = workflowRepo();
    workflowReported($repo);
    workflowTranscript($repo, time());
    workspaceWrite($repo, $path, "changed\n");
    workspaceCommit($repo);
    // The check file is committed and the code is as the report left it, so only the check is told.
    workflowCommand($repo, 'status');

    $first = workflowStop($repo, [], []);

    expect($first['systemMessage'] ?? null)->toContain("This conversation changed a check: {$path}.")
        ->and(workflowStop($repo)['systemMessage'] ?? '')->not->toContain('changed a check');
})->with(['phpstan.neon', 'vite.config.ts', 'tsconfig.json', 'phpunit.xml', 'pint.json', 'lint/oxlint/ezdev.js', 'tests/Structure/CodeTest.php', 'template/phpunit.xml', 'template/tests/Lint/CodeTest.php']);

test('a check file and code with no plan block and tell the user in the same answer', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'phpstan.neon', "parameters:\n    level: 9\n");

    $output = workflowStop($repo);

    expect($output['decision'])->toBe('block')
        ->and($output['systemMessage'])->toBe('This conversation changed a check: phpstan.neon.');
});

// ---------------------------------------------------------------- errors

test('an error inside a hook is told to the user and blocks nothing', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'phpstan.neon', "changed\n");
    // A state file that cannot be written.
    mkdir("{$repo}/.git/ezdev/told-".sha1('broken').'.json', 0777, true) ?: throw new RuntimeException('cannot make the state folder');

    $output = workflowStop($repo, ['session_id' => 'broken']);

    expect($output)->not->toHaveKey('decision')
        ->and($output['systemMessage'])->toStartWith('The workflow tool could not run: ');
});

test('a git command that fails is told to the user, never read as no change', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");
    workspaceCommit($repo);
    // A git that fails for `log` only, so the commits of this conversation cannot be read.
    $output = workflowStopWithGit($repo, 'log', 'exit 128');

    expect($output['systemMessage'])->toBe('The workflow tool could not run: git log failed');
});

test('a temporary file the tool cannot remove is told to the user, never left silently', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");
    $temp = workspaceFolder('workflow-temp');
    // A git that leaves the temporary folder read-only, so nothing in it can be removed.
    $path = workspaceFakeGit($repo, 'chmod 555 "$TMPDIR"');

    try {
        $output = workflowStop($repo, [], ['PATH' => $path, 'TMPDIR' => $temp]);
    } finally {
        chmod($temp, 0755) ?: throw new RuntimeException('cannot make the temporary folder writable again');
    }

    expect($output['systemMessage'] ?? null)->toStartWith('The workflow tool could not run: cannot remove ');
});

test('an error after another error is told with the first one', function () {
    $repo = workflowRepo();
    $temp = workspaceFolder('workflow-temp');
    // `git add` finds the temporary folder read-only: removing git's error file
    // fails first, then removing the fingerprint's index fails while that error is pending.
    $path = workspaceFakeGit($repo, 'case "$*" in *"add -A"*) chmod 555 "$TMPDIR";; esac');

    try {
        $process = workspaceWorkflow($repo, ['fingerprint'], ['PATH' => $path, 'TMPDIR' => $temp]);
    } finally {
        chmod($temp, 0755) ?: throw new RuntimeException('cannot make the temporary folder writable again');
    }

    expect($process->getExitCode())->toBe(1)
        ->and(substr_count($process->getErrorOutput(), 'cannot remove '))->toBe(2);
});

test('a temporary file that cannot be removed after git failed is told with git\'s message', function () {
    $repo = workflowRepo();
    $temp = workspaceFolder('workflow-temp');
    $path = workspaceFakeGit($repo, 'chmod 555 "$TMPDIR"; echo "fatal: the object store broke" >&2; exit 128');

    try {
        $output = workflowStop($repo, [], ['PATH' => $path, 'TMPDIR' => $temp]);
    } finally {
        chmod($temp, 0755) ?: throw new RuntimeException('cannot make the temporary folder writable again');
    }

    expect($output['systemMessage'] ?? null)->toContain('cannot remove ')->toContain('fatal: the object store broke');
});

test('a top folder the tool cannot work in is told to the user', function () {
    $repo = workflowRepo();
    $path = workspaceFakeGit($repo, 'case "$*" in *--show-toplevel*) echo /nonexistent/ezdev-top; exit 0;; esac');

    expect(workflowStop($repo, [], ['PATH' => $path])['systemMessage'] ?? null)->toBe('The workflow tool could not run: cannot work in /nonexistent/ezdev-top');
});

test('a git that refuses the repository is told to the user, never read as no repository', function () {
    $repo = workflowRepo();
    // A git that refuses every rev-parse, as git does for a folder it does not trust.
    $output = workflowStopWithGit($repo, 'rev-parse', "{ echo 'fatal: detected dubious ownership' >&2; exit 128; }");

    expect($output['systemMessage'])->toBe('The workflow tool could not run: git rev-parse failed: fatal: detected dubious ownership');
});

test('outside a git repository, or before its first commit, the tool says nothing in any language', function () {
    $dir = workspaceWithWorkflow('no-git');
    workspaceWrite($dir, 'app/code.php', "<?php\n");
    // A git that answers in Chinese unless asked for English, as a translated git does.
    $path = workspaceFakeGit($dir, "[ \"\$LC_ALL\" = C ] || { echo '致命错误：不是 git 仓库' >&2; exit 128; }");

    expect(workflowStop($dir, ['transcript_path' => null], ['PATH' => $path, 'LANG' => 'zh_CN.UTF-8', 'LC_ALL' => 'zh_CN.UTF-8']))->toBe([])
        ->and(workspaceWorkflow($dir, ['fingerprint'])->getOutput())->toBe('')
        ->and(workflowWrite($dir, 'app/code.php'))->toBe([]);

    (new Process(['git', 'init', '-q', '-b', 'main'], $dir))->mustRun();

    expect(workflowStop($dir, ['transcript_path' => null]))->toBe([])
        ->and(workflowSay($dir, '开始'))->toBe([]);
});

test('a transcript or state file that cannot be read is told to the user, never read as none', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");
    chmod("{$repo}/transcript.jsonl", 0000);

    expect(workflowStop($repo)['systemMessage'])->toStartWith('The workflow tool could not run: cannot read the transcript');

    chmod("{$repo}/transcript.jsonl", 0644);
    workspaceWrite($repo, '.git/ezdev/workflow.json', '{not json');

    expect(workflowStop($repo)['systemMessage'])->toStartWith('The workflow tool could not run: the state file ')->toContain('workflow.json cannot be read: Syntax error');
});

test('a hook input that is not a JSON object, or a told file that holds no list, is told to the user', function () {
    $repo = workflowRepo();
    $process = workspaceWorkflow($repo, ['hook', 'stop'], input: 'not json');

    expect($process->getExitCode())->toBe(0)
        ->and(json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR)['systemMessage'])->toStartWith('The workflow tool could not run: ');

    workspaceWrite($repo, 'phpstan.neon', "changed\n");
    workspaceWrite($repo, '.git/ezdev/told-'.sha1('test').'.json', '"phpstan.neon"');

    expect(workflowStop($repo)['systemMessage'])->toContain('holds no list of check files');
});

test('a Stop input without a session, or a transcript time that cannot be read, is told to the user', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");

    expect(workflowStop($repo, ['session_id' => null])['systemMessage'])->toBe('The workflow tool could not run: the Stop input names no session');

    workspaceWrite($repo, 'transcript.jsonl', json_encode(['type' => 'user', 'timestamp' => 'not a time'])."\n");

    expect(workflowStop($repo)['systemMessage'])->toStartWith('The workflow tool could not run: the transcript has a time stamp that cannot be read');

    workspaceWrite($repo, 'transcript.jsonl', "{broken\n");

    expect(workflowStop($repo)['systemMessage'])->toStartWith('The workflow tool could not run: ');
});

test('a transcript that is named but not there, or has no time stamp, is told to the user', function () {
    $repo = workflowRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php // changed\n");

    expect(workflowStop($repo, ['transcript_path' => "{$repo}/gone.jsonl"])['systemMessage'])->toBe('The workflow tool could not run: the transcript the Stop input names is not there');

    workspaceWrite($repo, 'transcript.jsonl', json_encode(['type' => 'summary'])."\n");

    expect(workflowStop($repo)['systemMessage'])->toStartWith('The workflow tool could not run: the transcript has no time stamp');
});

// ---------------------------------------------------------------- Claude Code's settings

test('Claude Code runs the tool\'s six hooks, and a missing or broken script still tells the user', function () {
    $root = dirname(__DIR__, 3);
    $repo = workflowRepo();
    $missing = workspaceFolder('no-workflow');
    $broken = workflowRepo();
    workspaceWrite($broken, 'scripts/workflow.php', "<?php\n\nthis is not php\n");

    $run = function (string $command, string $dir): string {
        $process = Process::fromShellCommandline($command, $dir, ['CLAUDE_PROJECT_DIR' => $dir]);
        $process->setInput(json_encode(['session_id' => 'test', 'cwd' => $dir, 'prompt' => 'hello', 'tool_name' => 'Read', 'tool_use_id' => 'r1', 'tool_input' => []]) ?: '');
        $process->mustRun();

        return $process->getOutput();
    };

    // The template supports Claude Code only: no other tool's hook configuration is shipped.
    expect(glob("{$root}/.*/hooks.json"))->toBe([]);

    $hooks = json_decode(file_get_contents("{$root}/.claude/settings.json") ?: throw new RuntimeException('no .claude/settings.json'), true, flags: JSON_THROW_ON_ERROR)['hooks'];
    $events = ['SessionStart' => 'session-start', 'UserPromptSubmit' => 'user-prompt', 'PreToolUse' => 'pre-tool', 'PostToolUse' => 'post-tool', 'Stop' => 'stop', 'SubagentStop' => 'subagent-stop'];

    expect(array_keys($hooks))->toBe(array_keys($events))
        ->and($hooks['PreToolUse'][0]['matcher'])->toBe('Edit|MultiEdit|Write|NotebookEdit|Bash')
        ->and($hooks['PostToolUse'][0]['matcher'])->toBe('Bash')
        ->and($hooks['SubagentStop'][0]['matcher'])->toBe('reviewer-haiku');

    foreach ($events as $event => $argument) {
        $command = $hooks[$event][0]['hooks'][0]['command'];

        expect($command)->toContain("scripts/workflow.php\" hook {$argument} ||")
            ->and($run($command, $missing))->toBe("{\"systemMessage\":\"The workflow tool could not run.\"}\n")
            ->and($run($command, $broken))->toBe("{\"systemMessage\":\"The workflow tool could not run.\"}\n");
    }

    expect($run($hooks['UserPromptSubmit'][0]['hooks'][0]['command'], $repo))->toBe('');
});

test('the session has no question tool and no workflows, and its subagents run in the foreground', function () {
    $settings = json_decode(file_get_contents(dirname(__DIR__, 3).'/.claude/settings.json') ?: throw new RuntimeException('no .claude/settings.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($settings['permissions']['deny'] ?? [])->toContain('AskUserQuestion')->toContain('Workflow')
        ->and($settings['env']['CLAUDE_CODE_DISABLE_BACKGROUND_TASKS'] ?? null)->toBe('1');
});

test('the reviewer reads only, on Haiku, and follows the pack it is given', function () {
    $agents = glob(dirname(__DIR__, 3).'/.claude/agents/*.md') ?: [];
    $reviewer = file_get_contents(dirname(__DIR__, 3).'/.claude/agents/reviewer-haiku.md') ?: throw new RuntimeException('no reviewer');

    expect(array_map('basename', $agents))->toBe(['explorer.md', 'reviewer-haiku.md'])
        ->and($reviewer)->toContain("model: haiku\n")->toContain("tools: Read, Grep, Glob\n")->toContain('Pack:');
});
