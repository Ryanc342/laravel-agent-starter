<?php

use Symfony\Component\Process\Process;

require_once dirname(__DIR__, 3).'/scripts/check-run.php';

/*
| The review ledger (docs/workflow.md, steps 4 and 5):
| scripts/hooks/finish-check.php writes one line per reviewer run when the
| subagent stops, and reads the ledger when the AI is about to stop. Each test
| runs the script the way Claude Code does, in a throwaway git repository with
| the two reviewer subagents of .claude/agents/.
*/

const LEDGER_CODE = "<?php\n\n\$a = 1;\n\$b = 2;\n\$c = 3;\n";

function ledgerRepo(): string
{
    $repo = sys_get_temp_dir().'/ezdev-review-ledger-'.bin2hex(random_bytes(6));
    $root = dirname(__DIR__, 3);

    foreach (['scripts/check-run.php', 'scripts/hooks/finish-check.php', 'docs/PLANS.md', '.claude/agents/reviewer-opus.md', '.claude/agents/reviewer-haiku.md'] as $path) {
        ledgerWrite($repo, $path, (string) file_get_contents("{$root}/{$path}"));
    }

    ledgerWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n");
    ledgerWrite($repo, 'docs/exec-plans/active/.gitkeep', '');
    ledgerWrite($repo, 'storage/framework/testing/.gitignore', "*\n!.gitignore\n");

    (new Process(['git', 'init', '-q', '-b', 'main'], $repo))->mustRun();
    ledgerCommit($repo);
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $repo]))->run());
    ledgerHook($repo, 'start');

    return $repo;
}

function ledgerWrite(string $repo, string $path, string $content): void
{
    if (! is_dir(dirname("{$repo}/{$path}"))) {
        mkdir(dirname("{$repo}/{$path}"), 0777, true);
    }

    file_put_contents("{$repo}/{$path}", $content);
}

function ledgerCommit(string $repo): void
{
    foreach ([['add', '-A'], ['-c', 'user.name=t', '-c', 'user.email=t@t', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null', 'commit', '-q', '-m', 'work']] as $args) {
        (new Process(['git', ...$args], $repo))->mustRun();
    }
}

/**
 * Runs a mode of the finish check with a hook's JSON on stdin and returns its stdout.
 *
 * @param  array<string, mixed>  $input
 */
function ledgerHook(string $repo, string $mode, array $input = []): string
{
    $process = new Process(['php', 'scripts/hooks/finish-check.php', $mode], $repo);
    $process->setInput(json_encode([...['session_id' => 'conversation', 'cwd' => $repo, 'stop_hook_active' => false, 'last_assistant_message' => 'Done.'], ...$input]) ?: '');
    $process->mustRun();

    return $process->getOutput();
}

/**
 * The reason the finish check sends back when the AI is about to stop ('' when it may stop).
 */
function ledgerStop(string $repo, string $session = 'conversation'): string
{
    return json_decode(ledgerHook($repo, 'stop', ['session_id' => $session]), true)['reason'] ?? '';
}

/**
 * A reviewer subagent stops after one run; its transcript names the model that answered.
 */
function ledgerReview(string $repo, string $reviewer, string $lane, int|string $round, int $p0 = 0, int $p1 = 0, string $session = 'conversation', int $user = 0): string
{
    $model = $reviewer === 'reviewer-opus' ? 'claude-opus-5-5' : 'claude-haiku-5-5';
    $transcript = "{$repo}/../".basename($repo)."-{$reviewer}-{$round}.jsonl";
    file_put_contents($transcript, json_encode(['message' => ['model' => $model, 'role' => 'assistant']])."\n");
    register_shutdown_function(fn () => @unlink($transcript));

    return ledgerHook($repo, 'reviewed', [
        'session_id' => $session,
        'hook_event_name' => 'SubagentStop',
        'agent_type' => $reviewer,
        'agent_transcript_path' => $transcript,
        'last_assistant_message' => "[P1] app/x.php:1 a finding\n\nRESULT lane={$lane} round={$round} P0={$p0} P1={$p1}".($user > 0 || $lane === 'behaviour' ? " user={$user}" : '').($lane === 'behaviour' ? ' unwalked=0' : ''),
    ]);
}

/**
 * A reviewer subagent starts (SubagentStart).
 */
function ledgerStart(string $repo, string $reviewer, string $agent): string
{
    return ledgerHook($repo, 'reviewing', ['hook_event_name' => 'SubagentStart', 'agent_type' => $reviewer, 'agent_id' => $agent]);
}

/**
 * A reviewer subagent hands its report back with the SubagentHandback tool (PostToolUse).
 */
function ledgerHandback(string $repo, string $reviewer, string $agent, string $message): string
{
    return ledgerHook($repo, 'handback', ['hook_event_name' => 'PostToolUse', 'agent_type' => $reviewer, 'agent_id' => $agent, 'tool_name' => 'SubagentHandback', 'tool_input' => ['message' => $message]]);
}

/**
 * The reviewer subagent stops (SubagentStop); after a handback its last message holds only closing text.
 *
 * @param  array<string, mixed>  $input
 */
function ledgerStopped(string $repo, string $reviewer, string $agent, array $input = []): string
{
    return ledgerHook($repo, 'reviewed', [...['hook_event_name' => 'SubagentStop', 'agent_type' => $reviewer, 'agent_id' => $agent, 'last_assistant_message' => ''], ...$input]);
}

/**
 * @return list<array<string, mixed>>
 */
function ledgerLines(string $repo): array
{
    $file = "{$repo}/.git/ezdev/review-ledger.jsonl";

    return is_file($file) ? array_map(fn (string $line): array => json_decode($line, true), array_filter(explode("\n", (string) file_get_contents($file)))) : [];
}

function ledgerPass(string $repo): void
{
    $run = new CheckRun("{$repo}/.git/ezdev");
    $env = ['EZDEV_CHECK_RUN_ID' => $run->id];
    (new Process(['php', 'scripts/hooks/finish-check.php', 'checking'], $repo, $env))->mustRun();
    ledgerWrite($repo, 'storage/framework/testing/ezdev-tests-finished', json_encode(['id' => $run->id, 'time' => microtime(true)]) ?: '');
    (new Process(['php', 'scripts/hooks/finish-check.php', 'checked'], $repo, $env))->mustRun();
    $run->succeed();
}

function ledgerPlan(string $milestones): string
{
    return "# Send quotes\n\n## Purpose\nSales staff send a quote.\n\n## Agreed decisions\n1. A quote is sent as a PDF.\n\n## Milestones\n{$milestones}\n\n## Progress\n- M1 done, composer check passed, review round 1\n\n## Decision Log\n\n## Outcomes & Retrospective\nQuotes can be sent; nothing is left.\n";
}

/**
 * A finished plan, committed with its code, which touches the lanes business,
 * input, tests and behaviour (app/).
 */
function ledgerFinishedPlan(): string
{
    $repo = ledgerRepo();
    ledgerWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', LEDGER_CODE);
    ledgerWrite($repo, 'docs/exec-plans/active/send-quotes.md', ledgerPlan("- [x] M1 Actions\n- [x] M2 Pages"));
    ledgerCommit($repo);
    ledgerPass($repo);

    return $repo;
}

/**
 * One final round with a clean run of each model for each lane of ledgerFinishedPlan().
 */
function ledgerCleanRounds(string $repo, array $lanes = ['business', 'input', 'tests', 'behaviour'], int $round = 1): void
{
    foreach (['reviewer-opus', 'reviewer-haiku'] as $reviewer) {
        foreach ($lanes as $lane) {
            ledgerReview($repo, $reviewer, $lane, $round);
        }
    }
}

test('a reviewer run writes one ledger line: lane, round, counts, reviewer, model, commit and code', function () {
    $repo = ledgerRepo();
    ledgerWrite($repo, 'docs/exec-plans/active/send-quotes.md', ledgerPlan('- [ ] M1 Actions'));

    expect(ledgerReview($repo, 'reviewer-opus', 'tests', 2, p1: 3))->toBe('');

    $line = ledgerLines($repo)[0];
    expect(ledgerLines($repo))->toHaveCount(1)
        ->and($line)->toMatchArray([
            'session' => sha1('conversation'),
            'reviewer' => 'reviewer-opus',
            'model' => 'claude-opus-5-5',
            'lane' => 'tests',
            'round' => 2,
            'p0' => 0,
            'p1' => 3,
            'plans' => ['send-quotes.md'],
        ])
        ->and($line['head'])->toBe(trim((new Process(['git', 'rev-parse', 'HEAD'], $repo))->mustRun()->getOutput()))
        ->and($line['fingerprint'])->toBeString()->not->toBe('');
});

test('only the reviewer subagents write ledger lines', function () {
    $repo = ledgerRepo();

    expect(ledgerHook($repo, 'reviewed', ['agent_type' => 'explorer', 'last_assistant_message' => 'RESULT lane=tests round=1 P0=0 P1=0']))->toBe('')
        ->and(ledgerLines($repo))->toBe([]);
});

test('a reviewer that leaves out the result line is sent back once, then its run counts as not run', function () {
    $repo = ledgerRepo();
    $input = ['agent_type' => 'reviewer-haiku', 'last_assistant_message' => "No P0/P1 findings.\nThis is round 1."];

    expect(json_decode(ledgerHook($repo, 'reviewed', $input), true)['reason'])->toContain('RESULT lane=<lane> round=<n> P0=<count> P1=<count>')
        ->and(ledgerLines($repo))->toBe([]);

    expect(ledgerHook($repo, 'reviewed', [...$input, 'stop_hook_active' => true]))->toBe('')
        ->and(ledgerLines($repo)[0])->toMatchArray(['reviewer' => 'reviewer-haiku', 'model' => 'haiku', 'lane' => null]);
});

test('a report handed back with SubagentHandback writes the ledger line', function () {
    $repo = ledgerRepo();
    ledgerStart($repo, 'reviewer-haiku', 'a1');

    expect(ledgerHandback($repo, 'reviewer-haiku', 'a1', "[P1] app/x.php:1 a finding\n\nRESULT lane=input round=2 P0=0 P1=1"))->toBe('')
        ->and(ledgerStopped($repo, 'reviewer-haiku', 'a1'))->toBe('')
        ->and(ledgerLines($repo))->toHaveCount(1)
        ->and(ledgerLines($repo)[0])->toMatchArray(['reviewer' => 'reviewer-haiku', 'lane' => 'input', 'round' => 2, 'p0' => 0, 'p1' => 1, 'committed' => true]);
});

test('only the handback of the same reviewer run counts', function () {
    $repo = ledgerRepo();
    ledgerHandback($repo, 'reviewer-opus', 'a1', 'RESULT lane=input round=1 P0=0 P1=0');
    ledgerHandback($repo, 'explorer', 'a2', 'RESULT lane=input round=1 P0=0 P1=0');

    expect(json_decode(ledgerStopped($repo, 'reviewer-opus', 'a2'), true)['reason'] ?? '')->toContain('RESULT lane=<lane> round=<n> P0=<count> P1=<count>')
        ->and(ledgerLines($repo))->toBe([]);
});

test('a reviewer that saved files during its run is sent back once to delete them, and only those', function () {
    $repo = ledgerRepo();
    ledgerWrite($repo, 'mine.png', 'png');
    ledgerStart($repo, 'reviewer-haiku', 'a1');
    ledgerWrite($repo, 'tasks-index-desktop.png', 'png');
    ledgerWrite($repo, 'notes.txt', 'text');
    ledgerHandback($repo, 'reviewer-haiku', 'a1', 'RESULT lane=look round=2 P0=0 P1=0');

    $reason = json_decode(ledgerStopped($repo, 'reviewer-haiku', 'a1'), true)['reason'] ?? '';

    expect($reason)->toContain('tasks-index-desktop.png')->toContain('notes.txt')->not->toContain('mine.png')
        ->and(ledgerLines($repo))->toHaveCount(1)
        ->and(ledgerLines($repo)[0])->toMatchArray(['lane' => 'look', 'round' => 2]);

    expect(ledgerStopped($repo, 'reviewer-haiku', 'a1', ['stop_hook_active' => true]))->toBe('')
        ->and(ledgerLines($repo))->toHaveCount(1);
});

test('a run that leaves a new untracked file in a clean tree is recorded as committed code', function () {
    $repo = ledgerFinishedPlan();
    ledgerStart($repo, 'reviewer-haiku', 'a1');
    ledgerWrite($repo, 'style-snapshot.yml', 'snapshot');
    ledgerHandback($repo, 'reviewer-haiku', 'a1', 'RESULT lane=behaviour round=2 P0=0 P1=0 user=0 unwalked=0');

    $reason = json_decode(ledgerStopped($repo, 'reviewer-haiku', 'a1'), true)['reason'] ?? '';

    expect($reason)->toContain('style-snapshot.yml')
        ->and(ledgerLines($repo)[0])->toMatchArray(['lane' => 'behaviour', 'committed' => true]);
});

test('a tracked change during the run, or a file untracked before it, is recorded as uncommitted code and never named to the reviewer', function (bool $before) {
    $repo = ledgerFinishedPlan();

    if ($before) {
        ledgerWrite($repo, 'app/Actions/Quotes/RejectQuote.php', LEDGER_CODE);
    }

    ledgerStart($repo, 'reviewer-opus', 'a1');

    if (! $before) {
        ledgerWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', LEDGER_CODE."\$d = 4;\n");
    }

    ledgerHandback($repo, 'reviewer-opus', 'a1', 'RESULT lane=business round=1 P0=0 P1=0');

    expect(ledgerStopped($repo, 'reviewer-opus', 'a1'))->toBe('')
        ->and(ledgerLines($repo)[0])->toMatchArray(['lane' => 'business', 'committed' => false])
        ->and(is_file("{$repo}/app/Actions/Quotes/".($before ? 'RejectQuote.php' : 'AcceptQuote.php')))->toBeTrue();
})->with(['a file untracked before the run' => [true], 'a tracked change during the run' => [false]]);

test('code uncommitted at the run\'s start voids the round, even when it is committed before the run stops', function (string $path) {
    $repo = ledgerFinishedPlan();
    ledgerWrite($repo, $path, LEDGER_CODE."\$e = 5;\n");
    ledgerStart($repo, 'reviewer-opus', 'a1');
    ledgerCommit($repo);
    ledgerHandback($repo, 'reviewer-opus', 'a1', 'RESULT lane=business round=1 P0=0 P1=0');

    expect(ledgerStopped($repo, 'reviewer-opus', 'a1'))->toBe('')
        ->and(ledgerLines($repo)[0])->toMatchArray(['lane' => 'business', 'committed' => false]);
})->with(['a tracked change' => ['app/Actions/Quotes/AcceptQuote.php'], 'a new code file' => ['app/Actions/Quotes/RejectQuote.php']]);

test('without a start record of the run no image is held against the reviewer', function () {
    $repo = ledgerRepo();
    ledgerWrite($repo, 'mine.png', 'png');
    ledgerHandback($repo, 'reviewer-opus', 'a1', 'RESULT lane=look round=1 P0=0 P1=0');

    expect(ledgerStopped($repo, 'reviewer-opus', 'a1'))->toBe('')
        ->and(ledgerLines($repo))->toHaveCount(1);
});

test('the model is the one that answered, not one named elsewhere in the transcript', function () {
    $repo = ledgerRepo();
    $transcript = "{$repo}/../".basename($repo).'-mixed.jsonl';
    file_put_contents($transcript, implode("\n", [
        json_encode(['type' => 'assistant', 'message' => ['model' => 'claude-haiku-5-5', 'role' => 'assistant']]),
        json_encode(['type' => 'attachment', 'attachment' => ['tools' => [['name' => 'advisor', 'model' => 'claude-opus-5-5']]]]),
    ])."\n");
    register_shutdown_function(fn () => @unlink($transcript));

    ledgerStopped($repo, 'reviewer-haiku', 'a1', ['agent_transcript_path' => $transcript, 'last_assistant_message' => 'RESULT lane=tests round=2 P0=0 P1=0']);

    expect(ledgerLines($repo)[0]['model'])->toBe('claude-haiku-5-5');
});

test('a finished plan with no ledger line is sent back to the review, the screens after the other lanes', function () {
    $repo = ledgerFinishedPlan();

    expect(ledgerStop($repo))->toContain('each lane it touches needs a round in which reviewer-opus and reviewer-haiku both report no P0 and no P1')
        ->toContain('business (no round yet); input (no round yet); tests (no round yet); behaviour (starts when the other lanes are done)')
        ->not->toContain('frontend (')
        ->not->toContain('look (');
});

test('a finding of either model in the latest round leaves the lane not done', function (string $reviewer, string $model) {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo, ['business', 'input', 'behaviour']);
    ledgerReview($repo, $reviewer === 'reviewer-opus' ? 'reviewer-haiku' : 'reviewer-opus', 'tests', 1);
    ledgerReview($repo, $reviewer, 'tests', 1, p1: 1);

    expect(ledgerStop($repo))->toContain("tests (round 1 reports 0 P0, 1 P1 from {$model})")
        ->not->toContain('business (');
})->with(['from reviewer-opus' => ['reviewer-opus', 'claude-opus-5-5'], 'from reviewer-haiku' => ['reviewer-haiku', 'claude-haiku-5-5']]);

test('a clean run of only one model, or two clean runs of the same model, do not finish a lane', function (array $reviewers) {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo, ['business', 'tests', 'behaviour']);

    foreach ($reviewers as $reviewer) {
        ledgerReview($repo, $reviewer, 'input', 1);
    }

    expect(ledgerStop($repo))->toContain('input (round 1 has a run of only claude-haiku-5-5)');
})->with(['one run' => [['reviewer-haiku']], 'two runs of the same model' => [['reviewer-haiku', 'reviewer-haiku']]]);

test('a later round replaces an earlier one', function () {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo);
    ledgerReview($repo, 'reviewer-opus', 'tests', 2, p0: 1);
    ledgerReview($repo, 'reviewer-haiku', 'tests', 2);

    expect(ledgerStop($repo))->toContain('tests (round 2 reports 1 P0, 0 P1 from claude-opus-5-5)');

    ledgerCleanRounds($repo, ['tests'], 3);

    expect(ledgerStop($repo))->not->toContain('needs a round in which');
});

test('a clean round on an older commit does not finish a lane', function (bool $commit) {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo);
    ledgerWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', LEDGER_CODE."\$d = 4;\n");

    if ($commit) {
        ledgerCommit($repo);
    }

    ledgerPass($repo);

    expect(ledgerStop($repo))->toContain('business (the code changed after round 1)');
})->with(['a code commit after it' => [true], 'uncommitted code after it' => [false]]);

test('a round of uncommitted code does not count, even once that code is committed', function () {
    $repo = ledgerFinishedPlan();
    ledgerWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', LEDGER_CODE."\$d = 4;\n");
    ledgerCleanRounds($repo);
    ledgerCommit($repo);
    ledgerPass($repo);

    expect(ledgerStop($repo))->toContain('business (round 1 reviewed uncommitted code)');
});

test('a commit that changes only docs after the round keeps it', function () {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo);
    ledgerWrite($repo, 'docs/exec-plans/active/send-quotes.md', ledgerPlan("- [x] M1 Actions\n- [x] M2 Pages")."\n- review round 1: 0 P0, 0 P1\n");
    ledgerCommit($repo);

    expect(ledgerStop($repo))->toContain('Every milestone in docs/exec-plans/active/send-quotes.md is ticked')
        ->not->toContain('needs a round in which')
        ->and(ledgerLines($repo)[0]['committed'])->toBeTrue();
});

test('a screen lane with no round, once the other lanes are done, does not finish a plan', function () {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo, ['business', 'input', 'tests']);

    expect(ledgerStop($repo))->toContain('behaviour (no round yet)')
        ->not->toContain('tests (');
});

test('one round with a clean run of each model on the current code finishes the review', function () {
    $repo = ledgerFinishedPlan();
    ledgerReview($repo, 'reviewer-opus', 'business', 1, p0: 1);
    ledgerReview($repo, 'reviewer-haiku', 'business', 1);
    ledgerCleanRounds($repo, ['input', 'tests'], 1);
    ledgerCleanRounds($repo, ['business', 'behaviour'], 2);

    expect(ledgerStop($repo))->toContain('Every milestone in docs/exec-plans/active/send-quotes.md is ticked and composer check passed')
        ->not->toContain('needs a round in which');
});

test('a lane not done after its fourth round, or its fifth, still sends the AI back and names its number of rounds', function () {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo, ['business', 'input', 'behaviour']);

    foreach (range(1, 5) as $round) {
        ledgerReview($repo, 'reviewer-opus', 'tests', $round, p1: 1);
        ledgerReview($repo, 'reviewer-haiku', 'tests', $round);

        if ($round >= 4) {
            $output = json_decode(ledgerHook($repo, 'stop'), true);

            expect($output['decision'] ?? '')->toBe('block')
                ->and($output['reason'])->toContain("tests (round {$round} reports 0 P0, 1 P1 from claude-opus-5-5)")
                ->and($output['reason'])->toContain("Rounds so far: tests {$round}.")
                ->and($output['reason'])->not->toContain('at most')
                ->and($output['systemMessage'] ?? '')->not->toContain('not done after');
        }
    }
});

test('the send-back counts the rounds of each lane on its own', function () {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo, ['business', 'input']);

    foreach (range(1, 3) as $round) {
        ledgerReview($repo, 'reviewer-opus', 'tests', $round, p1: 1);
        ledgerReview($repo, 'reviewer-haiku', 'tests', $round);
    }

    ledgerReview($repo, 'reviewer-opus', 'behaviour', 4, p1: 1);
    ledgerReview($repo, 'reviewer-haiku', 'behaviour', 4);

    expect(ledgerStop($repo))->toContain('Rounds so far: tests 3, behaviour 1.');
});

test('a ticked milestone marked (review) asks for no round', function (string $path) {
    $repo = ledgerRepo();
    ledgerWrite($repo, 'docs/exec-plans/active/send-quotes.md', ledgerPlan("- [ ] M1 Actions (review)\n- [ ] M2 Pages (review)\n- [ ] M3 Mail"));
    ledgerCommit($repo);
    ledgerHook($repo, 'start', ['session_id' => 'next']);

    ledgerWrite($repo, $path, LEDGER_CODE);
    ledgerWrite($repo, 'docs/exec-plans/active/send-quotes.md', ledgerPlan("- [x] M1 Actions (review)\n- [x] M2 Pages (review)\n- [ ] M3 Mail"));
    ledgerPass($repo);

    expect(ledgerStop($repo, 'next'))->toBe('');
})->with(['an Action' => ['app/Actions/Quotes/AcceptQuote.php'], 'a test' => ['tests/Feature/Quotes/SendQuoteTest.php'], 'a page' => ['resources/js/pages/quotes/index.tsx']]);

/**
 * A behaviour run of a final round: its report lists the items it walked, and its
 * result line ends with the number it did not walk ('' leaves the field out).
 *
 * @param  list<string>  $walked
 */
function ledgerWalk(string $repo, string $reviewer, int $round, array $walked, string $unwalked, int $p0 = 0, bool $again = false): string
{
    $lines = array_map(fn (string $item): string => "{$item} walked", $walked);
    $field = $unwalked === '' ? '' : " unwalked={$unwalked}";

    return ledgerHook($repo, 'reviewed', [
        'hook_event_name' => 'SubagentStop',
        'agent_type' => $reviewer,
        'stop_hook_active' => $again,
        'agent_transcript_path' => ledgerTranscript($repo, $reviewer, "walk-{$round}-".count(ledgerLines($repo))),
        'last_assistant_message' => implode("\n", $lines)."\n\nRESULT lane=behaviour round={$round} P0={$p0} P1=0 user=0{$field}",
    ]);
}

/**
 * @return array<string, mixed>
 */
function ledgerLastLine(string $repo): array
{
    $lines = ledgerLines($repo);

    return $lines[count($lines) - 1] ?? [];
}

function ledgerTranscript(string $repo, string $reviewer, string $name): string
{
    $transcript = "{$repo}/../".basename($repo)."-{$name}.jsonl";
    file_put_contents($transcript, json_encode(['message' => ['model' => $reviewer === 'reviewer-opus' ? 'claude-opus-5-5' : 'claude-haiku-5-5', 'role' => 'assistant']])."\n");
    register_shutdown_function(fn () => @unlink($transcript));

    return $transcript;
}

/**
 * ledgerFinishedPlan() with a walk list of two items under Validation, the other lanes done.
 */
function ledgerWalkPlan(): string
{
    $repo = ledgerFinishedPlan();
    $plan = str_replace("## Progress\n", "## Validation\n- W1 employee: submit a quote, from the form to the list\n- W2 approver: approve it, from the list to the approved page\n\n## Progress\n", ledgerPlan("- [x] M1 Actions\n- [x] M2 Pages"));
    ledgerWrite($repo, 'docs/exec-plans/active/send-quotes.md', $plan);
    ledgerCommit($repo);
    ledgerCleanRounds($repo, ['business', 'input', 'tests']);

    return $repo;
}

test('a behaviour result line without unwalked= sends the reviewer back once', function () {
    $repo = ledgerWalkPlan();
    $lines = count(ledgerLines($repo));

    expect(json_decode(ledgerWalk($repo, 'reviewer-opus', 1, ['W1', 'W2'], ''), true)['reason'] ?? '')->toContain('unwalked=<count>')
        ->and(ledgerLines($repo))->toHaveCount($lines);

    expect(ledgerWalk($repo, 'reviewer-opus', 1, ['W1', 'W2'], '', again: true))->toBe('')
        ->and(ledgerLastLine($repo))->toMatchArray(['lane' => 'behaviour', 'unwalked' => 0]);
});

test('a behaviour run with no unwalked= after its send-back, for a plan with no walk list, counts as one item not walked', function () {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo, ['business', 'input', 'tests']);
    ledgerReview($repo, 'reviewer-opus', 'behaviour', 1);

    expect(json_decode(ledgerWalk($repo, 'reviewer-haiku', 1, [], ''), true)['reason'] ?? '')->toContain('unwalked=<count>');

    ledgerWalk($repo, 'reviewer-haiku', 1, [], '', again: true);

    expect(ledgerLastLine($repo))->toMatchArray(['lane' => 'behaviour', 'unwalked' => 1])
        ->and(ledgerStop($repo))->toContain('behaviour (round 1 has a run of only claude-opus-5-5; a walk of claude-haiku-5-5 left 1 item out)');
});

test('the result line of another lane needs no unwalked=', function () {
    $repo = ledgerWalkPlan();

    expect(ledgerReview($repo, 'reviewer-opus', 'look', 1))->toBe('')
        ->and(ledgerLastLine($repo))->not->toHaveKey('unwalked');
});

test('a walk with items not walked is recorded, counts as not run, and the second walk of that model completes the round', function () {
    $repo = ledgerWalkPlan();
    ledgerWalk($repo, 'reviewer-opus', 1, ['W1', 'W2'], '0');

    expect(ledgerWalk($repo, 'reviewer-haiku', 1, [], '2'))->toBe('')
        ->and(ledgerLastLine($repo))->toMatchArray(['lane' => 'behaviour', 'unwalked' => 2])
        ->and(ledgerStop($repo))->toContain('behaviour (round 1 has a run of only claude-opus-5-5; a walk of claude-haiku-5-5 left 2 items out)');

    ledgerWalk($repo, 'reviewer-haiku', 1, ['W1', 'W2'], '0');

    expect(ledgerStop($repo))->not->toContain('needs a round in which');
});

test('a round in which every walk left items out has no run of either model', function () {
    $repo = ledgerWalkPlan();
    ledgerWalk($repo, 'reviewer-opus', 1, ['W1'], '1');
    ledgerWalk($repo, 'reviewer-haiku', 1, [], '2');

    expect(ledgerStop($repo))->toContain('behaviour (round 1 has no run that walked every item; a walk of claude-opus-5-5 left 1 item out; a walk of claude-haiku-5-5 left 2 items out)');
});

test('a report that leaves out an item of the walk list counts it as not walked, whatever its result line says', function () {
    $repo = ledgerWalkPlan();
    ledgerWalk($repo, 'reviewer-opus', 1, ['W1', 'W2'], '0');
    ledgerWalk($repo, 'reviewer-haiku', 1, ['W1'], '0');

    expect(ledgerLastLine($repo))->toMatchArray(['unwalked' => 1])
        ->and(ledgerStop($repo))->toContain('a walk of claude-haiku-5-5 left 1 item out');
});

test('the walk list of a plan already in completed/ counts the items a report leaves out', function () {
    $repo = ledgerWalkPlan();
    mkdir("{$repo}/docs/exec-plans/completed");
    (new Process(['git', 'mv', 'docs/exec-plans/active/send-quotes.md', 'docs/exec-plans/completed/send-quotes.md'], $repo))->mustRun();
    ledgerCommit($repo);
    ledgerRequestedReview($repo, 'reviewer-haiku', 'h1', 'Lane: behaviour. Round: 1. App: http://localhost:8101. Plan: docs/exec-plans/completed/send-quotes.md.', "W1 walked\n\nRESULT lane=behaviour round=1 P0=0 P1=0 user=0 unwalked=0");

    expect(ledgerLastLine($repo))->toMatchArray(['lane' => 'behaviour', 'unwalked' => 1, 'plans' => ['send-quotes.md']]);
});

test('a "not walked" line does not count as walked', function () {
    $repo = ledgerWalkPlan();
    ledgerHook($repo, 'reviewed', [
        'hook_event_name' => 'SubagentStop',
        'agent_type' => 'reviewer-haiku',
        'agent_transcript_path' => ledgerTranscript($repo, 'reviewer-haiku', 'not-walked'),
        'last_assistant_message' => "W1 walked\nW2 not walked: the approver could not log in\n\nRESULT lane=behaviour round=1 P0=0 P1=0 user=0 unwalked=0",
    ]);

    expect(ledgerLastLine($repo))->toMatchArray(['lane' => 'behaviour', 'unwalked' => 1]);
});

test('a walk that is not complete still reports its findings', function () {
    $repo = ledgerWalkPlan();
    ledgerWalk($repo, 'reviewer-opus', 1, ['W1', 'W2'], '0');
    ledgerWalk($repo, 'reviewer-haiku', 1, ['W1'], '1', p0: 1);

    expect(ledgerStop($repo))->toContain('behaviour (round 1 reports 1 P0, 0 P1 from claude-haiku-5-5');
});

test('a second walk of the same model in the round drops the note on its first walk', function () {
    $repo = ledgerWalkPlan();
    ledgerWalk($repo, 'reviewer-opus', 1, ['W1', 'W2'], '0');
    ledgerWalk($repo, 'reviewer-haiku', 1, ['W1'], '1');
    ledgerWalk($repo, 'reviewer-haiku', 1, ['W1', 'W2'], '0', p0: 1);

    expect(ledgerStop($repo))->toContain('behaviour (round 1 reports 1 P0, 0 P1 from claude-haiku-5-5)')
        ->not->toContain('left 1 item out');
});

test('an old behaviour ledger line without the field is still read and counts as walked', function () {
    $repo = ledgerWalkPlan();
    $head = trim((new Process(['git', 'rev-parse', 'HEAD'], $repo))->mustRun()->getOutput());

    foreach (['reviewer-opus' => 'claude-opus-5-5', 'reviewer-haiku' => 'claude-haiku-5-5'] as $reviewer => $model) {
        file_put_contents("{$repo}/.git/ezdev/review-ledger.jsonl", json_encode(['time' => microtime(true), 'session' => sha1('conversation'), 'reviewer' => $reviewer, 'model' => $model, 'lane' => 'behaviour', 'round' => 1, 'milestone' => false, 'p0' => 0, 'p1' => 0, 'user' => 0, 'head' => $head, 'committed' => true, 'fingerprint' => 'f', 'plans' => ['send-quotes.md']])."\n", FILE_APPEND);
    }

    expect(ledgerStop($repo))->not->toContain('needs a round in which');
});

/**
 * A finished plan whose work touches all six lanes, with a clean round of both models for each.
 */
function ledgerReviewedPlanOfAllLanes(): string
{
    $repo = ledgerRepo();
    ledgerWrite($repo, 'app/Http/Resources/QuoteResource.php', LEDGER_CODE);
    ledgerWrite($repo, 'resources/js/pages/quotes/index.tsx', "export {};\n");
    ledgerWrite($repo, 'docs/exec-plans/active/send-quotes.md', ledgerPlan("- [x] M1 Actions\n- [x] M2 Pages"));
    ledgerCommit($repo);
    ledgerPass($repo);
    ledgerCleanRounds($repo, ['business', 'input', 'frontend', 'tests', 'look', 'behaviour']);

    return $repo;
}

test('a fix after the final rounds voids only the rounds of the lanes its files touch', function (string $path, array $voided, array $kept) {
    $repo = ledgerReviewedPlanOfAllLanes();
    expect(ledgerStop($repo))->not->toContain('needs a round in which');

    ledgerWrite($repo, $path, LEDGER_CODE."\$d = 4;\n");
    ledgerCommit($repo);
    ledgerPass($repo);
    $reason = ledgerStop($repo);

    foreach ($voided as $lane) {
        expect($reason)->toContain("{$lane} (the code changed after round 1)");
    }

    foreach ($kept as $lane) {
        expect($reason)->not->toContain("{$lane} (");
    }
})->with([
    'a fix in tests/' => ['tests/Feature/Quotes/SendQuoteTest.php', ['tests'], ['business', 'input', 'frontend', 'look', 'behaviour']],
    'a fix in app/' => ['app/Actions/Quotes/AcceptQuote.php', ['business', 'input', 'tests', 'behaviour'], ['frontend', 'look']],
    'a fix in a page' => ['resources/js/pages/quotes/index.tsx', ['input', 'frontend', 'look', 'behaviour'], ['business', 'tests']],
]);

test('the final review asks for the lanes each changed file touches', function (string $path, string $lanes) {
    $repo = ledgerRepo();
    ledgerWrite($repo, $path, LEDGER_CODE);
    ledgerWrite($repo, 'docs/exec-plans/active/send-quotes.md', ledgerPlan("- [x] M1 Slice\n- [x] M2 Mail"));
    ledgerCommit($repo);
    ledgerPass($repo);

    expect(ledgerStop($repo))->toContain("Rounds so far: {$lanes}.");
})->with([
    'an Action' => ['app/Actions/Quotes/AcceptQuote.php', 'business 0, input 0, tests 0, behaviour 0'],
    'a translation alone' => ['lang/de.json', 'behaviour 0'],
    'a page' => ['resources/js/pages/quotes/index.tsx', 'input 0, frontend 0, look 0, behaviour 0'],
    'a component' => ['resources/js/components/quote-card.tsx', 'input 0, frontend 0, look 0, behaviour 0'],
    'a layout' => ['resources/js/layouts/app-layout.tsx', 'input 0, frontend 0, look 0, behaviour 0'],
    'a stylesheet' => ['resources/css/app.css', 'look 0, behaviour 0'],
    'a view' => ['resources/views/app.blade.php', 'behaviour 0'],
    'a hook' => ['resources/js/hooks/use-quote.ts', 'input 0, frontend 0, tests 0, look 0, behaviour 0'],
]);

test('the lanes of the finish check are the lanes of docs/golden-principles.md', function () {
    $root = dirname(__DIR__, 3);
    $doc = (string) file_get_contents("{$root}/docs/golden-principles.md");
    preg_match_all('/^\| [^|]+\| `(\w+)`\s*\| [^|]+\| ([^|]+)\|$/m', $doc, $rows, PREG_SET_ORDER);
    $lanes = [];

    foreach ($rows as [, $key, $places]) {
        $lanes[$key] = array_map('trim', explode(',', trim($places)));
    }

    $hook = json_decode((new Process(['php', "{$root}/scripts/hooks/finish-check.php", 'lanes'], $root))->mustRun()->getOutput(), true);

    expect($lanes)->toBe($hook);
});

test('the two reviewers run on two different Claude models, and Claude Code writes their ledger lines', function () {
    $root = dirname(__DIR__, 3);
    $models = [];

    foreach (glob("{$root}/.claude/agents/reviewer-*.md") ?: [] as $file) {
        preg_match('/^model:\s*(\S+)/m', (string) file_get_contents($file), $model);
        $models[basename($file, '.md')] = $model[1] ?? '';
    }

    $hooks = json_decode((string) file_get_contents("{$root}/.claude/settings.json"), true)['hooks'];

    expect($models)->toBe(['reviewer-haiku' => 'haiku', 'reviewer-opus' => 'opus'])
        ->and($hooks['SubagentStart'][0]['hooks'][0]['command'] ?? '')->toContain('scripts/hooks/finish-check.php" reviewing')
        ->and($hooks['PostToolUse'][0]['matcher'] ?? '')->toBe('SubagentHandback')
        ->and($hooks['PostToolUse'][0]['hooks'][0]['command'] ?? '')->toContain('scripts/hooks/finish-check.php" handback')
        ->and($hooks['PostToolUse'][1]['matcher'] ?? '')->toBe('TaskStop')
        ->and($hooks['PostToolUse'][1]['hooks'][0]['command'] ?? '')->toContain('scripts/hooks/finish-check.php" stopped')
        ->and($hooks['SubagentStop'][0]['hooks'][0]['command'] ?? '')->toContain('scripts/hooks/finish-check.php" reviewed');
});

test('an old ledger line of a milestone round is read without an error and counts for no lane', function () {
    $repo = ledgerFinishedPlan();
    $head = trim((new Process(['git', 'rev-parse', 'HEAD'], $repo))->mustRun()->getOutput());

    foreach (['reviewer-opus' => 'claude-opus-5-5', 'reviewer-haiku' => 'claude-haiku-5-5'] as $reviewer => $model) {
        foreach (['business', 'input', 'tests'] as $lane) {
            file_put_contents("{$repo}/.git/ezdev/review-ledger.jsonl", json_encode(['time' => microtime(true), 'session' => sha1('conversation'), 'reviewer' => $reviewer, 'model' => $model, 'lane' => $lane, 'round' => 1, 'milestone' => true, 'p0' => 0, 'p1' => 0, 'user' => 0, 'head' => $head, 'committed' => true, 'fingerprint' => 'f', 'plans' => ['send-quotes.md']])."\n", FILE_APPEND);
        }
    }

    $output = json_decode(ledgerHook($repo, 'stop'), true);

    expect($output['reason'] ?? '')->toContain('business (no round yet); input (no round yet); tests (no round yet)')
        ->and($output['systemMessage'] ?? '')->not->toContain('failed');
});

/**
 * The whole answer of the finish check when the AI is about to stop.
 *
 * @return array<string, mixed>
 */
function ledgerStopOutput(string $repo, string $message = 'Done.'): array
{
    return json_decode(ledgerHook($repo, 'stop', ['last_assistant_message' => $message]), true) ?? [];
}

test('a stop while a reviewer run of this conversation has no stop record lets the AI stop, tells the user, and counts no send-back', function () {
    $repo = ledgerFinishedPlan();
    ledgerStart($repo, 'reviewer-opus', 'a1');

    foreach (range(1, 4) as $stop) {
        $output = ledgerStopOutput($repo);

        expect($output)->not->toHaveKey('decision')
            ->and($output['systemMessage'])->toContain('1 reviewer run of this conversation')->toContain('finish the review rounds of docs/exec-plans/active/send-quotes.md');
    }

    ledgerHandback($repo, 'reviewer-opus', 'a1', 'RESULT lane=business round=1 P0=0 P1=0');
    ledgerStopped($repo, 'reviewer-opus', 'a1');

    foreach (range(1, 3) as $stop) {
        expect(ledgerStopOutput($repo))->toHaveKey('decision', 'block');
    }
});

test('a reviewer run of another conversation, or a start record hours old, does not let the AI stop', function (bool $old) {
    $repo = ledgerFinishedPlan();

    if ($old) {
        ledgerStart($repo, 'reviewer-opus', 'a1');
        $file = "{$repo}/.git/ezdev/review-run-".sha1('a1').'.json';
        file_put_contents($file, json_encode([...json_decode((string) file_get_contents($file), true), 'started' => microtime(true) - 3 * 3600]));
    } else {
        ledgerHook($repo, 'reviewing', ['session_id' => 'another', 'hook_event_name' => 'SubagentStart', 'agent_type' => 'reviewer-opus', 'agent_id' => 'a1']);
    }

    expect(ledgerStopOutput($repo))->toHaveKey('decision', 'block');
})->with(['hours old' => [true], 'another conversation' => [false]]);

test('a start record older than 30 minutes does not let a stop through, and a younger one does', function (int $minutes, bool $through) {
    $repo = ledgerFinishedPlan();
    ledgerStart($repo, 'reviewer-opus', 'a1');
    $file = "{$repo}/.git/ezdev/review-run-".sha1('a1').'.json';
    file_put_contents($file, json_encode([...json_decode((string) file_get_contents($file), true), 'started' => microtime(true) - $minutes * 60]));

    $output = ledgerStopOutput($repo);

    if ($through) {
        expect($output)->not->toHaveKey('decision')
            ->and($output['systemMessage'])->toContain('1 reviewer run of this conversation');
    } else {
        expect($output)->toHaveKey('decision', 'block');
    }
})->with(['31 minutes old' => [31, false], '29 minutes old' => [29, true]]);

test('a reviewer run the builder stops with TaskStop leaves no start record, and the stop after it is checked in full', function () {
    $repo = ledgerFinishedPlan();
    ledgerStart($repo, 'reviewer-haiku', 'a1');
    ledgerStart($repo, 'reviewer-opus', 'a2');

    expect(ledgerHook($repo, 'stopped', ['hook_event_name' => 'PostToolUse', 'tool_name' => 'TaskStop', 'tool_input' => ['task_id' => 'a1'], 'tool_response' => ['task_id' => 'a1', 'task_type' => 'local_agent']]))->toBe('')
        ->and(is_file("{$repo}/.git/ezdev/review-run-".sha1('a1').'.json'))->toBeFalse()
        ->and(is_file("{$repo}/.git/ezdev/review-run-".sha1('a2').'.json'))->toBeTrue()
        ->and(ledgerLines($repo))->toBe([]);

    ledgerHandback($repo, 'reviewer-opus', 'a2', 'RESULT lane=business round=1 P0=0 P1=0');
    ledgerStopped($repo, 'reviewer-opus', 'a2');

    expect(ledgerStopOutput($repo))->toHaveKey('decision', 'block');
});

/**
 * A reviewer run whose transcript starts with the builder's request, as Claude Code writes it.
 */
function ledgerRequestedReview(string $repo, string $reviewer, string $agent, string $request, string $result, bool $blocks = false): string
{
    $transcript = "{$repo}/../".basename($repo)."-{$agent}.jsonl";
    $content = $blocks ? [['type' => 'text', 'text' => $request]] : $request;
    file_put_contents($transcript, json_encode(['parentUuid' => null, 'isSidechain' => true, 'agentId' => $agent, 'type' => 'user', 'message' => ['role' => 'user', 'content' => $content]])."\n"
        .json_encode(['type' => 'assistant', 'message' => ['model' => $reviewer === 'reviewer-opus' ? 'claude-opus-5-5' : 'claude-haiku-5-5', 'role' => 'assistant']])."\n");
    register_shutdown_function(fn () => @unlink($transcript));

    return ledgerStopped($repo, $reviewer, $agent, ['agent_transcript_path' => $transcript, 'last_assistant_message' => $result]);
}

test('the ledger line of a round names the plan its request names, also one already in completed/', function (bool $blocks) {
    $repo = ledgerRepo();
    ledgerWrite($repo, 'docs/exec-plans/active/print-quotes.md', ledgerPlan('- [ ] M1 Print'));
    ledgerRequestedReview($repo, 'reviewer-opus', 'a1', 'Lane: business. Round: 3. Plan: docs/exec-plans/completed/send-quotes.md.', 'RESULT lane=business round=3 P0=0 P1=0', $blocks);

    expect(ledgerLines($repo)[0]['plans'])->toBe(['send-quotes.md']);
})->with(['text' => [false], 'text blocks' => [true]]);

test('with a request that names no plan, the ledger line names the plans in active/', function () {
    $repo = ledgerRepo();
    ledgerWrite($repo, 'docs/exec-plans/active/print-quotes.md', ledgerPlan('- [ ] M1 Print'));
    ledgerRequestedReview($repo, 'reviewer-opus', 'a1', 'Lane: business. Round: 3.', 'RESULT lane=business round=3 P0=0 P1=0');
    ledgerStopped($repo, 'reviewer-haiku', 'a2', ['agent_transcript_path' => "{$repo}/../missing.jsonl", 'last_assistant_message' => 'RESULT lane=business round=3 P0=0 P1=0']);

    expect(array_column(ledgerLines($repo), 'plans'))->toBe([['print-quotes.md'], ['print-quotes.md']]);
});

test('rounds run after the plan was moved to completed/ count for that plan', function () {
    $repo = ledgerFinishedPlan();
    ledgerHook($repo, 'start', ['session_id' => 'next']);
    mkdir("{$repo}/docs/exec-plans/completed");
    (new Process(['git', 'mv', 'docs/exec-plans/active/send-quotes.md', 'docs/exec-plans/completed/send-quotes.md'], $repo))->mustRun();
    ledgerCommit($repo);
    ledgerPass($repo);

    expect(ledgerStop($repo, 'next'))->toContain('business (no round yet)');

    foreach (['reviewer-opus' => 'o', 'reviewer-haiku' => 'h'] as $reviewer => $id) {
        foreach (['business', 'input', 'tests', 'behaviour'] as $lane) {
            ledgerRequestedReview($repo, $reviewer, "{$id}-{$lane}", "Lane: {$lane}. Round: 1. Plan: docs/exec-plans/completed/send-quotes.md.", "RESULT lane={$lane} round=1 P0=0 P1=0".($lane === 'behaviour' ? ' user=0 unwalked=0' : ''));
        }
    }

    expect(ledgerStop($repo, 'next'))->not->toContain('needs a round in which');
});

test('the review of a plan already in completed/ does not ask to move it', function () {
    $repo = ledgerFinishedPlan();
    ledgerHook($repo, 'start', ['session_id' => 'next']);
    mkdir("{$repo}/docs/exec-plans/completed");
    (new Process(['git', 'mv', 'docs/exec-plans/active/send-quotes.md', 'docs/exec-plans/completed/send-quotes.md'], $repo))->mustRun();
    ledgerCommit($repo);
    ledgerPass($repo);

    expect(ledgerStop($repo, 'next'))->toContain('business (no round yet)')
        ->toContain('php scripts/hooks/finish-check.php review docs/exec-plans/completed/send-quotes.md')
        ->not->toContain('Move the plan to completed/');
});

test('the finish check asks for the move only when every lane is done', function () {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo, ['business', 'input', 'tests']);

    expect(ledgerStop($repo))->toContain('behaviour (no round yet)')
        ->not->toContain('move the plan to docs/exec-plans/completed/');

    ledgerCleanRounds($repo, ['behaviour']);

    expect(ledgerStop($repo))->toContain('Fill in Outcomes & Retrospective and move the plan to docs/exec-plans/completed/')
        ->not->toContain('needs a round in which');
});

test('the read-only mode exits 2 and prints no lane when it cannot read the plan', function (string $path) {
    $repo = ledgerFinishedPlan();
    $process = new Process(['php', 'scripts/hooks/finish-check.php', 'review', $path], $repo);
    $process->run();

    expect($process->getExitCode())->toBe(2)
        ->and($process->getOutput())->toBe('')
        ->and($process->getErrorOutput())->toContain('The finish check could not read the review')->toContain($path);
})->with(['a missing plan' => ['docs/exec-plans/active/print-quotes.md'], 'a path outside the plans' => ['docs/workflow.md'], 'no path' => ['']]);

test('the read-only mode prints each lane of a plan as done or not done and changes no file', function () {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo, ['business', 'input']);
    ledgerReview($repo, 'reviewer-opus', 'tests', 1, p1: 2);
    ledgerReview($repo, 'reviewer-haiku', 'tests', 1);
    $before = array_map(fn (string $file): string => $file.':'.md5_file($file), [...glob("{$repo}/.git/ezdev/*") ?: [], ...glob("{$repo}/docs/exec-plans/*/*") ?: []]);

    $process = new Process(['php', 'scripts/hooks/finish-check.php', 'review', 'docs/exec-plans/active/send-quotes.md'], $repo);
    $process->run();
    $after = array_map(fn (string $file): string => $file.':'.md5_file($file), [...glob("{$repo}/.git/ezdev/*") ?: [], ...glob("{$repo}/docs/exec-plans/*/*") ?: []]);

    expect($process->getExitCode())->toBe(1)
        ->and($process->getOutput())->toContain("business: done\ninput: done\ntests: not done (round 1 reports 0 P0, 2 P1 from claude-opus-5-5, 1 round)\nbehaviour: not done (no round yet, 0 rounds)")
        ->and($process->getOutput())->toContain('Do not move the plan yet')
        ->and($after)->toBe($before);

    ledgerCleanRounds($repo, ['tests', 'behaviour'], 2);
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain("tests: done\nbehaviour: done\nEvery lane is done");
});

test('a stop while every open lane holds only findings for the user lets the AI stop without a send-back', function () {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo, ['input', 'tests']);
    ledgerReview($repo, 'reviewer-opus', 'business', 1, p1: 1, user: 1);
    ledgerReview($repo, 'reviewer-haiku', 'business', 1, p0: 1, p1: 1, user: 2);

    foreach (range(1, 4) as $stop) {
        $output = ledgerStopOutput($repo);

        expect($output)->not->toHaveKey('decision')
            ->and($output['systemMessage'])->toContain("the user's answer")->toContain('business')->toContain('finish the review rounds of docs/exec-plans/active/send-quotes.md');
    }
});

test('one finding not for the user sends the AI back as before, whatever its last message says', function (string $message) {
    $repo = ledgerFinishedPlan();
    ledgerCleanRounds($repo, ['input', 'tests']);
    ledgerReview($repo, 'reviewer-opus', 'business', 1, p1: 1, user: 1);
    ledgerReview($repo, 'reviewer-haiku', 'business', 1, p1: 2, user: 1);

    expect(ledgerStopOutput($repo, $message))->toHaveKey('decision', 'block');
})->with([
    'a plain stop' => ['Done.'],
    'the AI says it waits' => ["I am waiting for the user's answer and for the reviewers, which are still running.\nShall I go on?"],
]);

test('a lane with no round keeps the send-back even beside findings for the user', function () {
    $repo = ledgerFinishedPlan();
    ledgerReview($repo, 'reviewer-opus', 'business', 1, p1: 1, user: 1);

    expect(ledgerStopOutput($repo))->toHaveKey('decision', 'block');
});

test('a stop that waits for nothing keeps the three send-backs', function () {
    $repo = ledgerFinishedPlan();

    foreach (range(1, 3) as $stop) {
        expect(ledgerStopOutput($repo, 'I wait for the reviewers.'))->toHaveKey('decision', 'block');
    }

    expect(ledgerStopOutput($repo))->not->toHaveKey('decision')
        ->and(ledgerStopOutput($repo)['systemMessage'] ?? '')->toContain('sent these steps back 3 times');
});

test('a change to the reference feature touches the lanes its path inside the feature would touch, and its Markdown none', function (string $path, array $lanes) {
    $repo = ledgerRepo();
    ledgerWrite($repo, 'docs/exec-plans/active/send-quotes.md', ledgerPlan("- [x] M1 Actions\n- [x] M2 Pages"));
    ledgerCommit($repo);
    ledgerWrite($repo, $path, "<?php\n// changed\n");
    ledgerCommit($repo);

    $process = new Process(['php', 'scripts/hooks/finish-check.php', 'review', 'docs/exec-plans/active/send-quotes.md'], $repo);
    $process->run();
    preg_match_all('/^(\w+): not done/m', $process->getOutput(), $named);

    expect($named[1])->toBe($lanes)
        ->and($process->getExitCode())->toBe($lanes === [] ? 0 : 1);

    if ($lanes === []) {
        expect($process->getOutput())->toContain('Every lane is done');
    }
})->with([
    'a page of the feature' => ['docs/reference/tasks/resources/js/pages/tasks/index.tsx', ['input', 'frontend', 'look', 'behaviour']],
    'a model of the feature' => ['docs/reference/tasks/app/Models/Task.php', ['business', 'input', 'tests', 'behaviour']],
    'the feature\'s README' => ['docs/reference/tasks/README.md', []],
    'another file in docs/' => ['docs/workflow.md', []],
    'Markdown inside the feature\'s app folder' => ['docs/reference/tasks/app/notes.md', []],
]);
