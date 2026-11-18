<?php

/*
| The finish check: principle 9 in docs/golden-principles.md.
|
| Claude Code (.claude/settings.json) runs this script when a conversation
| starts and each time the AI is about to stop; composer check runs it first
| and last:
|
|   php scripts/hooks/finish-check.php start      remember where this conversation started
|   php scripts/hooks/finish-check.php stop       compare with the start; send back what is missing
|   php scripts/hooks/finish-check.php checking   composer check starts: remember the code it checks
|   php scripts/hooks/finish-check.php checked    composer check passed: record that code as checked
|   milestone-checking / milestone-checked       separate partial validation, never a full pass
|   reviewing    a reviewer subagent started (SubagentStart): remember the untracked files
|   handback     a reviewer handed its report back (PostToolUse, SubagentHandback): keep it
|   reviewed     a reviewer subagent stopped (SubagentStop): write its ledger line
|   stopped      the AI stopped a subagent (PostToolUse, TaskStop): drop its start record
|   review <plan>  read-only: print each lane of the plan as done or not done
|   lanes        print the review lanes and the places that touch them, as JSON
|
| The review ledger: each run of a reviewer subagent (.claude/agents/reviewer-*.md)
| writes one line to the review ledger, review-ledger.jsonl in the state folder:
| the lane, round and P0 and P1 counts from the result line of
| docs/prompts/review.md, the reviewer, its model, the HEAD commit and whether all code was committed. There is no review
| before the final review: a finished plan needs, for each lane, a latest round
| with a clean run of each of the two models, with no code change since that
| touches the lane, however many rounds that takes (docs/workflow.md, step 4).
|
| A conversation that changed no file is never stopped. The state lives in
| .git/ezdev/ (git rev-parse --git-path ezdev), outside the repository's files.
|
| Both AI tools read stdout: nothing lets the AI stop, one JSON object sends it
| back or tells the user, and plain text at the start is added to the AI's
| context. Anything else there would break that, so warnings go to stderr and
| every error ends as a message to the user.
*/

// AGENTS.md "Red lines": the user approves a change to these.
const PROTECTED_PATHS = [
    'AGENTS.md', 'CLAUDE.md', 'ARCHITECTURE.md', 'DESIGN.md', 'docs/golden-principles.md', 'docs/rules/', 'docs/design/', 'docs/prompts/',
    'docs/workflow.md', 'docs/PLANS.md', 'docs/reference/',
    'phpstan.neon', 'vite.config.ts', 'tsconfig.json', 'pint.json', 'phpunit.xml', '.npmrc', 'artisan', '.gitignore',
    'lint/', 'tests/Structure/', 'tests/Support/', 'tests/Unit/Hooks/', 'scripts/',
    'tests/bootstrap.php', 'tests/Pest.php', 'tests/TestCase.php', 'tests/js/setup.ts',
    '.claude/', '.github/',
    'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
];

// A lock file is approved together with its manifest.
const LOCK_FILES = ['composer.lock' => 'composer.json', 'package-lock.json' => 'package.json'];

const MAX_BLOCKS = 3;

// Where docs/PLANS.md puts plans.
const PLAN_PATH = '#^docs/exec-plans/(active|completed)/[^/]+\.md$#';

// Without these a plan says nothing the code could follow.
const PLAN_ESSENTIALS = ['Purpose', 'Agreed decisions', 'Milestones'];

// tests/Structure/RunFinishedTest.php writes it, as the last test of the run: a
// composer check whose tests stopped early, or ran only in part, records no pass.
const TESTS_FINISHED = 'storage/framework/testing/ezdev-tests-finished';

// The review lanes of docs/golden-principles.md ("Review lanes"): a change touches a
// lane when it changes a file in one of its places.
const REVIEW_LANES = [
    'business' => ['app/', 'database/', 'routes/'],
    'input' => ['app/', 'database/', 'routes/', 'resources/js/', 'composer.json', 'package.json'],
    'frontend' => ['resources/js/', 'app/Http/Resources/', 'app/Http/Requests/'],
    'tests' => ['tests/', 'app/', 'routes/', 'resources/js/hooks/', 'resources/js/lib/'],
    'look' => ['resources/js/', 'resources/css/', 'DESIGN.md', 'docs/design/'],
    'behaviour' => ['resources/js/', 'resources/css/', 'resources/views/', 'lang/', 'app/'],
];

// The reference feature (docs/reference/tasks/): its files other than Markdown
// are code and touch the lanes of their path inside the feature.
const REFERENCE_FEATURE = 'docs/reference/tasks/';

// The final review reviews these lanes last, when the other lanes are done
// (docs/golden-principles.md, under "Review lanes").
const SCREEN_LANES = ['look', 'behaviour'];

// The last line of a reviewer's answer (docs/prompts/review.md). The rounds of a
// plan are numbered 1, 2, 3. `user` counts the findings marked `(for the user)`
// (docs/workflow.md, step 3); `unwalked`, in lane `behaviour` only, the items of
// the walk it did not walk (docs/workflow.md, step 4).
const REVIEW_RESULT = '/^`?RESULT lane=(\w+) round=(\d+) P0=(\d+) P1=(\d+)(?: user=(\d+))?(?: unwalked=(\d+))?`?$/';

// The lane whose runs walk the plan's flows and report each item of the walk.
const WALK_LANE = 'behaviour';

// An item of a plan's walk list, under Validation (docs/PLANS.md): `W1 <role>: <flow>`.
const WALK_ITEM = '/^\s*(?:[-*+]\s+)?(W\d+)\s+[^:\n]+:/m';

// A line of a behaviour report for an item it walked: `W1 walked`.
const WALKED_ITEM = '/^\W*(W\d+)\s+walked\b/mi';

const REVIEW_LEDGER = 'review-ledger.jsonl';

// What a reviewer run leaves for its SubagentStop: the untracked files at its start
// and the report it handed back. One file per run, named after the subagent's id.
const REVIEW_RUN = 'review-run-';

// A reviewer run's start record older than this no longer counts as a run still
// going: a run that ended without its SubagentStop must not free every stop after it
// (docs/workflow.md, step 4). A run that has reported nothing by then is stopped
// and started again.
const REVIEW_RUN_MAX_SECONDS = 30 * 60;

// The plan a reviewer's request names (docs/prompts/review.md): `Plan: <path>`.
const REQUEST_PLAN = '#\bPlan:\s*`?(?:\S*/)?([^/\s`]+\.md)\b#';

const JSON_OUTPUT = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

// The modes Claude Code runs as hooks; they read the hook's JSON on stdin.
const HOOK_MODES = ['start', 'stop', 'reviewing', 'handback', 'reviewed', 'stopped'];

require_once __DIR__.'/../check-run.php';

ini_set('display_errors', 'stderr');

$mode = $argv[1] ?? '';

try {
    echo finishCheck($mode, $argv[2] ?? '');
} catch (Throwable $error) {
    // A broken check must neither trap the AI nor go unnoticed.
    if (in_array($mode, HOOK_MODES, true)) {
        echo json_encode(['systemMessage' => 'The finish check failed: '.$error->getMessage()], JSON_OUTPUT);
        exit(0);
    }

    if ($mode === 'review') {
        fwrite(STDERR, 'The finish check could not read the review: '.$error->getMessage().PHP_EOL);
        exit(2);
    }

    // composer check must not end green on a pass that was not recorded.
    fwrite(STDERR, 'The finish check did not record this composer check as passed: '.$error->getMessage().PHP_EOL);
    exit(1);
}

exit(($mode === 'review' && ! ($GLOBALS['reviewDone'] ?? false)) ? 1 : 0);

/**
 * Runs one mode and returns what goes to stdout.
 */
function finishCheck(string $mode, string $argument = ''): string
{
    $hook = in_array($mode, HOOK_MODES, true);
    // Claude Code sends the conversation as JSON on stdin; composer check sends nothing.
    $input = $hook ? json_decode((string) stream_get_contents(STDIN), true) : [];
    $input = is_array($input) ? $input : [];

    if (is_string($input['cwd'] ?? null) && is_dir($input['cwd'])) {
        chdir($input['cwd']);
    }

    // The lanes are fixed: printing them needs no repository.
    if ($mode === 'lanes') {
        return json_encode(REVIEW_LANES, JSON_OUTPUT | JSON_THROW_ON_ERROR);
    }

    $top = git(['rev-parse', '--show-toplevel']);

    // Outside a git repository, or before its first commit, there is no start to compare with.
    if ($top === null || ($hook && git(['rev-parse', '--verify', '--quiet', 'HEAD']) === null)) {
        return '';
    }

    chdir($top);
    $stateDir = git(['rev-parse', '--path-format=absolute', '--git-path', 'ezdev']) ?? "{$top}/.git/ezdev";

    if (! is_dir($stateDir) && ! mkdir($stateDir, 0777, true)) {
        throw new RuntimeException("cannot create {$stateDir}");
    }

    $session = is_string($input['session_id'] ?? null) ? $input['session_id'] : 'default';
    $lastMessage = is_string($input['last_assistant_message'] ?? null) ? $input['last_assistant_message'] : '';

    return match ($mode) {
        'start' => start($stateDir, $session),
        'stop' => stop($stateDir, $session, $lastMessage),
        'checking' => checking($stateDir),
        'checked' => checked($stateDir),
        'milestone-checking' => checking($stateDir, true),
        'milestone-checked' => checked($stateDir, true),
        'reviewing' => reviewing($stateDir, $session, $input),
        'handback' => handback($stateDir, $input),
        'reviewed' => reviewed($stateDir, $session, $input),
        'stopped' => stopped($stateDir, $input),
        'review' => reviewStatus($stateDir, $argument),
        default => '',
    };
}

/**
 * A conversation starts: record its start once, and pass on the steps an earlier
 * conversation stopped without.
 */
function start(string $stateDir, string $session): string
{
    $file = sessionFile($stateDir, $session);

    if (readSession($file) === null) {
        writeFile($file, json_encode(newSession(), JSON_THROW_ON_ERROR));
    }

    return takeOpenSteps($stateDir);
}

/**
 * The AI is about to stop: send back what is missing. Each reason goes back at most
 * MAX_BLOCKS times, so a stop never loops forever; after that the user and the next
 * conversation are told about it instead.
 */
function stop(string $stateDir, string $session, string $lastMessage): string
{
    $file = sessionFile($stateDir, $session);
    $state = readSession($file);

    if ($state === null) {
        writeFile($file, json_encode(newSession(), JSON_THROW_ON_ERROR));

        // Changes made before this point cannot be told apart from the user's own, so they do not count.
        return json_encode(['systemMessage' => 'The finish check had no start record for this conversation, so it counts changes from now on.'], JSON_OUTPUT | JSON_THROW_ON_ERROR);
    }

    $send = [];
    $skipped = [];
    $released = [];
    $waits = [];
    $missing = missingSteps($state, $stateDir, $lastMessage, $waits);
    $running = runningReviews($stateDir, sha1($session));

    if ($running > 0) {
        $waits = [$running.' reviewer run'.($running === 1 ? '' : 's').' of this conversation that '.($running === 1 ? 'is' : 'are').' still running'];
    }

    // Waiting for something the check can see (docs/workflow.md, step 3): the AI
    // stops, no send-back is counted, and the steps stay open for the next stop.
    if ($missing !== [] && $waits !== []) {
        writeFile($file, json_encode($state, JSON_THROW_ON_ERROR));
        recordOpenSteps($stateDir, sha1($session), array_column($missing, 1));

        return json_encode(['systemMessage' => 'The finish check let the AI stop without sending it back: the session waits for '.implode(' and ', $waits).'. Still open: '.implode('; ', array_column($missing, 0)).'. The next stop is checked again.'], JSON_OUTPUT | JSON_THROW_ON_ERROR);
    }

    foreach ($missing as $key => [$step, $reason]) {
        $state['blocks'][$key] = ($state['blocks'][$key] ?? 0) + 1;

        if ($state['blocks'][$key] <= MAX_BLOCKS) {
            $send[] = $reason;
        } else {
            $skipped[] = $step;
            $released[] = $reason;
        }
    }

    writeFile($file, json_encode($state, JSON_THROW_ON_ERROR));
    recordOpenSteps($stateDir, sha1($session), $released);

    return stopOutput($send, $skipped);
}

/**
 * The steps missing at this stop, each under a key that stays the same while its
 * cause does (MAX_BLOCKS counts per key): [short step for the user, reason for the AI].
 *
 * $waits gets what the session waits for when every review step left is the
 * user's answer to findings marked for the user (docs/workflow.md, step 3).
 *
 * @param  array<string, mixed>  $state
 * @param  list<string>  $waits
 * @return array<string, array{string, string}>
 */
function missingSteps(array $state, string $stateDir, string $lastMessage, array &$waits = []): array
{
    $changed = changedInConversation($state);

    if ($changed === []) {
        return [];
    }

    $missing = [];
    $code = array_values(array_filter($changed, 'isCodePath'));
    // Only a plan written or updated in this conversation belongs to its work: one left over in active/ does not.
    $plans = array_values(array_filter($changed, fn (string $path): bool => preg_match(PLAN_PATH, $path) === 1 && is_file($path)));
    $fingerprint = fingerprint();
    $checked = CheckRun::passed($stateDir, $fingerprint);
    $finalPlans = finalPlanPaths($plans, $state, $stateDir, $checked);

    // 1. Code needs a plan (a one-line change with no open decision may skip it).
    if ($code !== [] && $plans === [] && ! isOneLineChange($state['base'], $code)) {
        $missing['plan:'.$fingerprint] = ['write the plan', 'You changed code ('.summary($code).') but there is no plan in docs/exec-plans/active/ that changed in this conversation. Write the plan (docs/workflow.md, step 2) and wait for the user\'s "start"; while you work on a plan, tick its milestones and add Progress lines. A request to fix tech debt needs one too.'];
    }

    // 2. Each plan of this conversation is filled in, finished when its work is, and complete once moved.
    foreach ($plans as $plan) {
        $missing = [...$missing, ...planSteps($plan, $checked)];
    }

    // 3. A fixed tech-debt line updates docs/QUALITY.md.
    $removed = array_values(array_diff($state['debt'] ?? [], debtRows((string) @file_get_contents('docs/exec-plans/tech-debt.md'))));

    if ($removed !== [] && ! in_array('docs/QUALITY.md', $changed, true)) {
        $missing['quality:'.sha1(implode("\n", $removed))] = ['update docs/QUALITY.md', 'You removed '.count($removed).' line(s) from docs/exec-plans/tech-debt.md but docs/QUALITY.md did not change. Update the grade and notes of that domain.'];
    }

    // 4. A protected file or dependency needs the user's approval, added to a plan's Decision
    // Log in this conversation: a line from an earlier conversation approved an earlier change.
    $approvals = newDecisions($state, $plans);
    $unapproved = array_values(array_filter(
        $changed,
        fn (string $path): bool => isProtected($path) && array_filter($approvals, fn (string $line): bool => str_contains($line, LOCK_FILES[$path] ?? $path)) === [],
    ));

    // Stopping to ask the user about exactly these files is the step itself.
    if ($unapproved !== [] && ! isAskingAbout($lastMessage, $unapproved)) {
        $missing['protected:'.implode(',', $unapproved)] = ['get your approval for '.implode(', ', $unapproved), 'You changed '.implode(', ', $unapproved).' (protected files or dependencies, AGENTS.md "Red lines"). Ask the user first, then add their approval to the plan\'s Decision Log as a new line naming each file. Without approval, undo the change.'];
    }

    // 5. Only an unfinished active plan may use a fresh, separate milestone pass.
    if (($code !== [] || $finalPlans !== []) && ! $checked) {
        $intermediate = $finalPlans === [] && count($plans) === 1 && isIntermediatePlan($plans[0]);
        $milestoneChecked = CheckRun::passed($stateDir, $fingerprint, true);

        if ($intermediate && ! $milestoneChecked) {
            $missing['milestone:'.$fingerprint] = ['pass milestone checks', 'Run composer check:milestone -- <affected test paths> for full static checks and affected tests, and record the scope and result in Progress. Run composer check now if shared infrastructure changed or the affected scope cannot be bounded.'];
        } elseif (! $intermediate) {
            $reason = $finalPlans === [] ? 'The code changed since the last passing composer check.' : 'Final acceptance of '.summary($finalPlans).' requires a full pass on the current code, even when this conversation only changed the plan.';
            $missing['check:'.$fingerprint] = ['pass composer check', $reason.' Run composer check and fix every failure.'];
        }
    }

    // 6. Reviews (docs/workflow.md, step 4), where the reviewer subagents are defined:
    // the final review only.
    if (reviewerModels() !== []) {
        $ledger = readLedger($stateDir);
        $forUser = [];
        $actionable = false;

        foreach ($finalPlans as $plan) {
            [$reason, $userLanes] = finalReview($plan, touchedLanes(planChanges($plan, $changed)), $ledger);

            if ($reason !== null) {
                // The plan moves last, when every lane is done (docs/workflow.md, step 5).
                unset($missing["finish:{$plan}"]);
                $missing['final-review:'.$plan.':'.sha1($reason)] = ['finish the review rounds of '.$plan, $reason];
                if ($userLanes === []) {
                    $actionable = true;
                } else {
                    $forUser[] = $plan.' ('.implode(', ', $userLanes).')';
                }
            }
        }

        if ($forUser !== [] && ! $actionable) {
            $waits[] = "the user's answer to the findings marked for the user in ".implode(' and ', $forUser);
        }
    }

    return $missing;
}

/**
 * The final review of one finished plan (docs/workflow.md, step 4). A lane is done when its latest round
 * holds a clean run of each of the two reviewer models, on committed code, and no
 * code in the lane's places changed since; docs/ and *.md void nothing. There is
 * no cap on rounds: the reason names how many rounds each open lane has had.
 * Returns the reason that sends the AI back, or null; and, when the next step of
 * every lane that can move is the user's answer (its latest round holds only
 * findings marked for the user, docs/workflow.md step 3), those lanes, else [].
 *
 * @param  list<string>  $lanes
 * @param  list<array<string, mixed>>  $ledger
 * @return array{?string, list<string>}
 */
function finalReview(string $plan, array $lanes, array $ledger): array
{
    $runs = finalRuns($ledger, $plan);
    $open = [];
    $rounds = [];

    foreach ($lanes as $lane) {
        $laneRuns = array_values(array_filter($runs, fn (array $run): bool => $run['lane'] === $lane));
        $problem = laneProblem($lane, $laneRuns);

        if ($problem !== false) {
            $open[$lane] = $problem;
            $rounds[] = $lane.' '.count(array_unique(array_column($laneRuns, 'round')));
        }
    }

    if ($open === []) {
        return [null, []];
    }

    $othersOpen = array_diff_key($open, array_flip(SCREEN_LANES)) !== [];
    $userLanes = [];

    foreach (array_keys($open) as $lane) {
        $laneRuns = array_values(array_filter($runs, fn (array $run): bool => $run['lane'] === $lane));

        // A screen lane with no round waits for the other lanes, not for the AI.
        if ($laneRuns === [] && in_array($lane, SCREEN_LANES, true) && $othersOpen) {
            continue;
        }

        if (! onlyForUser($laneRuns)) {
            $userLanes = [];
            break;
        }

        $userLanes[] = $lane;
    }
    $list = implode('; ', array_map(
        fn (string $lane, ?string $problem): string => $lane.' ('.($problem ?? (in_array($lane, SCREEN_LANES, true) && $othersOpen ? 'starts when the other lanes are done' : 'no round yet')).')',
        array_keys($open),
        $open,
    ));

    return ['Before '.$plan.' is finished, each lane it touches needs a round in which reviewer-opus and reviewer-haiku both report no P0 and no P1, on committed code that has not changed since in the lane\'s places; a later round replaces an earlier one (docs/workflow.md, step 4). Not yet: '.$list.'. Rounds so far: '.implode(', ', $rounds).'. Fix the findings, then run the next round: for each lane not done, both reviewers at the same time on the same commit, with the same round number, and look and behaviour last, when the other lanes are done (docs/prompts/review.md). '.(str_starts_with($plan, 'docs/exec-plans/active/') ? 'Move the plan to completed/ only when `php scripts/hooks/finish-check.php review '.$plan.'` prints every lane as done.' : 'The plan stays unfinished until `php scripts/hooks/finish-check.php review '.$plan.'` prints every lane as done.'), $userLanes];
}

/**
 * The ledger lines of the plan's rounds. An old line of a milestone round counts
 * for no lane: there is no review between milestones (docs/workflow.md, step 4).
 *
 * @param  list<array<string, mixed>>  $ledger
 * @return list<array<string, mixed>>
 */
function finalRuns(array $ledger, string $plan): array
{
    return array_values(array_filter($ledger, fn (array $run): bool => $run['lane'] !== null && ($run['milestone'] ?? false) !== true && in_array(basename($plan), $run['plans'], true)));
}

/**
 * Whether the latest round of a lane has findings, and the reviewer marked every
 * one of them for the user (docs/prompts/review.md, step 9).
 *
 * @param  list<array<string, mixed>>  $runs
 */
function onlyForUser(array $runs): bool
{
    if ($runs === []) {
        return false;
    }

    $round = max(array_column($runs, 'round'));
    $findings = array_filter($runs, fn (array $run): bool => $run['round'] === $round && $run['p0'] + $run['p1'] > 0);

    return $findings !== [] && array_filter($findings, fn (array $run): bool => ($run['user'] ?? 0) < $run['p0'] + $run['p1']) === [];
}

/**
 * Read-only: each lane the plan touches, as done or not done with the reason and
 * its number of rounds (docs/workflow.md, step 5). The builder runs it before
 * it moves the plan; the exit code is 0 only when every lane is done.
 */
function reviewStatus(string $stateDir, string $plan): string
{
    if (preg_match(PLAN_PATH, $plan) !== 1 || ! is_file($plan)) {
        throw new RuntimeException("{$plan} is not a plan in docs/exec-plans/active/ or completed/");
    }

    $runs = finalRuns(readLedger($stateDir), $plan);
    $lines = [];
    $done = true;

    foreach (touchedLanes(planChanges($plan, changedSince(null))) as $lane) {
        $laneRuns = array_values(array_filter($runs, fn (array $run): bool => $run['lane'] === $lane));
        $problem = laneProblem($lane, $laneRuns);
        $rounds = count(array_unique(array_column($laneRuns, 'round')));
        $done = $done && $problem === false;
        $lines[] = $problem === false ? "{$lane}: done" : "{$lane}: not done (".($problem ?? 'no round yet').", {$rounds} round".($rounds === 1 ? '' : 's').')';
    }

    $GLOBALS['reviewDone'] = $done;
    $lines[] = $done ? 'Every lane is done: the plan may move to docs/exec-plans/completed/.' : 'Do not move the plan yet: run the next round for the lanes not done (docs/workflow.md, step 4).';

    return implode("\n", $lines)."\n";
}

/**
 * The AI stopped a subagent with TaskStop: no SubagentStop runs for it
 * (docs/workflow.md, step 4), so its start record goes now and the next stop is checked
 * in full. A run stopped this way wrote no ledger line: it counts as not run.
 *
 * @param  array<string, mixed>  $input
 */
function stopped(string $stateDir, array $input): string
{
    $task = is_array($input['tool_input'] ?? null) ? ($input['tool_input']['task_id'] ?? null) : null;

    if (($input['tool_name'] ?? null) === 'TaskStop' && is_string($task) && $task !== '') {
        @unlink(reviewRunFile($stateDir, $task));
    }

    return '';
}

/**
 * The reviewer runs of this conversation that started and have not stopped yet
 * (a start record of reviewing() that reviewed() has not removed).
 */
function runningReviews(string $stateDir, string $sessionKey): int
{
    $running = 0;

    foreach (glob("{$stateDir}/".REVIEW_RUN.'*.json') ?: [] as $file) {
        $run = json_decode((string) @file_get_contents($file), true);

        if (is_array($run) && ($run['session'] ?? null) === $sessionKey && is_numeric($run['started'] ?? null) && microtime(true) - (float) $run['started'] < REVIEW_RUN_MAX_SECONDS) {
            $running++;
        }
    }

    return $running;
}

/**
 * Why a lane is not done, from its final runs: null when it has no round yet, the
 * reason otherwise; false when it is done.
 *
 * @param  list<array<string, mixed>>  $runs
 */
function laneProblem(string $lane, array $runs): string|false|null
{
    if ($runs === []) {
        return null;
    }

    $round = max(array_column($runs, 'round'));
    $latest = array_values(array_filter($runs, fn (array $run): bool => $run['round'] === $round));
    $findings = array_values(array_filter($latest, fn (array $run): bool => $run['p0'] + $run['p1'] > 0));
    // A walk that left items out counts as not run; its findings still count
    // (docs/workflow.md, step 4). An old line without the field walked all.
    $models = array_values(array_unique(array_column(array_filter($latest, fn (array $run): bool => ($run['unwalked'] ?? 0) === 0), 'model')));
    // A model that walked every item in a later run of the round needs no note.
    $partial = array_values(array_filter($latest, fn (array $run): bool => ($run['unwalked'] ?? 0) > 0 && ! in_array($run['model'], $models, true)));
    $left = implode('', array_map(fn (array $run): string => "; a walk of {$run['model']} left {$run['unwalked']} item".($run['unwalked'] === 1 ? '' : 's').' out', $partial));

    return match (true) {
        $findings !== [] => "round {$round} reports ".implode(' and ', array_map(fn (array $run): string => "{$run['p0']} P0, {$run['p1']} P1 from {$run['model']}", $findings)).$left,
        $models === [] => "round {$round} has no run that walked every item{$left}",
        count($models) < 2 => "round {$round} has a run of only {$models[0]}{$left}",
        array_filter($latest, fn (array $run): bool => ! $run['committed']) !== [] => "round {$round} reviewed uncommitted code",
        array_filter($latest, fn (array $run): bool => laneChangedSince($run['head'], $lane)) !== [] => "the code changed after round {$round}",
        default => false,
    };
}

/**
 * A reviewer subagent started: remember the untracked files that were already
 * there, so that only what the run itself saves is held against it, and whether
 * the code was all committed (docs/workflow.md, step 4).
 *
 * @param  array<string, mixed>  $input
 */
function reviewing(string $stateDir, string $session, array $input): string
{
    $agent = reviewerRun($input);

    if ($agent !== null) {
        writeReviewRun($stateDir, $agent, [
            // Which conversation waits for this run, and since when (docs/workflow.md, step 3).
            'session' => sha1($session),
            'started' => microtime(true),
            'untracked' => untrackedFiles(),
            'clean' => array_filter(changedSince(null), 'isCodePath') === [],
        ]);
    }

    return '';
}

/**
 * A reviewer handed its report back with the SubagentHandback tool. The report is
 * the tool's input; the subagent's last message then holds only closing text, and
 * its transcript may not be written yet when it stops.
 *
 * @param  array<string, mixed>  $input
 */
function handback(string $stateDir, array $input): string
{
    $agent = reviewerRun($input);
    $message = is_array($input['tool_input'] ?? null) ? ($input['tool_input']['message'] ?? null) : null;

    if ($agent !== null && ($input['tool_name'] ?? null) === 'SubagentHandback' && is_string($message)) {
        writeReviewRun($stateDir, $agent, [...readReviewRun($stateDir, $agent), 'handback' => $message]);
    }

    return '';
}

/**
 * A reviewer subagent stopped: write its ledger line, once per run. The result line
 * is read from the last message, else from the report the run handed back. A
 * reviewer that left it out is sent back once to add it; a run without one counts
 * as not run. The untracked files that appeared during the run are its leftovers
 * (docs/workflow.md, step 4): they void nothing, and the reviewer is sent back once
 * to delete them; files that were there before it started are never named.
 *
 * @param  array<string, mixed>  $input
 */
function reviewed(string $stateDir, string $session, array $input): string
{
    $agent = is_string($input['agent_type'] ?? null) ? $input['agent_type'] : '';
    $reviewers = reviewerModels();

    if (! isset($reviewers[$agent])) {
        return '';
    }

    $id = reviewerRun($input);
    $run = $id === null ? [] : readReviewRun($stateDir, $id);
    $again = ($input['stop_hook_active'] ?? false) === true;
    $report = is_string($input['last_assistant_message'] ?? null) ? $input['last_assistant_message'] : '';
    $result = reviewResult($report);

    if ($result === null) {
        $report = is_string($run['handback'] ?? null) ? $run['handback'] : '';
        $result = reviewResult($report);
    }

    if ($result === null && ! $again) {
        return json_encode(['decision' => 'block', 'reason' => 'End your answer with the result line of docs/prompts/review.md as its last line: RESULT lane=<lane> round=<n> P0=<count> P1=<count> user=<count>, with one of the lanes '.implode(', ', array_keys(REVIEW_LANES)).', and in lane '.WALK_LANE.' unwalked=<count> after it. Without it this run counts as not run.'], JSON_OUTPUT | JSON_THROW_ON_ERROR);
    }

    if (($result['lane'] ?? null) === WALK_LANE && $result['unwalked'] === null && ! $again) {
        return json_encode(['decision' => 'block', 'reason' => 'In lane '.WALK_LANE.' the result line ends with the number of items of the walk you did not walk: RESULT lane='.WALK_LANE.' round=<n> P0=<count> P1=<count> user=<count> unwalked=<count>. Above it, one line per item: "W1 walked", or "W1 not walked: <reason>" (docs/prompts/review.md, step 7). Add them; a run with an item not walked counts as not run.'], JSON_OUTPUT | JSON_THROW_ON_ERROR);
    }

    $plans = requestPlans($input['agent_transcript_path'] ?? null) ?? array_map('basename', glob('docs/exec-plans/active/*.md') ?: []);

    // Without a start record nothing tells the reviewer's files from the user's own.
    $saved = is_array($run['untracked'] ?? null) ? array_values(array_diff(untrackedFiles(), $run['untracked'])) : [];

    if (($run['recorded'] ?? false) !== true) {
        $line = [
            'time' => microtime(true),
            'session' => sha1($session),
            'reviewer' => $agent,
            'model' => transcriptModel($input['agent_transcript_path'] ?? null) ?? $reviewers[$agent],
            'lane' => $result['lane'] ?? null,
            'round' => $result['round'] ?? 0,
            'p0' => $result['p0'] ?? 0,
            'p1' => $result['p1'] ?? 0,
            'user' => $result['user'] ?? 0,
            ...(($result['lane'] ?? null) === WALK_LANE ? ['unwalked' => unwalkedItems($result['unwalked'], $report, $plans)] : []),
            'head' => git(['rev-parse', 'HEAD']) ?? '',
            // The commit is what was reviewed only when no code was left uncommitted,
            // at the run's start and at its end; the files this run saved are its
            // leftovers, not code it reviewed.
            'committed' => ($run['clean'] ?? true) === true && array_filter(array_diff(changedSince(null), $saved), 'isCodePath') === [],
            'fingerprint' => fingerprint(),
            // The plan the request names, also one already in completed/; without a
            // readable request, the plans in active/ (docs/workflow.md, step 5).
            'plans' => $plans,
        ];

        if (@file_put_contents("{$stateDir}/".REVIEW_LEDGER, json_encode($line, JSON_OUTPUT | JSON_THROW_ON_ERROR)."\n", FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('cannot write the review ledger');
        }
    }

    if ($saved !== [] && ! $again && $id !== null) {
        writeReviewRun($stateDir, $id, [...$run, 'recorded' => true]);

        return json_encode(['decision' => 'block', 'reason' => 'Delete the files you saved during this review: '.implode(', ', $saved).'. Take screenshots without a filename (docs/prompts/review.md, step 7), and delete nothing else. Then stop again; your review is already recorded.'], JSON_OUTPUT | JSON_THROW_ON_ERROR);
    }

    if ($id !== null) {
        @unlink(reviewRunFile($stateDir, $id));
    }

    return '';
}

/**
 * The subagent id of a reviewer run, or null when the hook is not for a reviewer.
 *
 * @param  array<string, mixed>  $input
 */
function reviewerRun(array $input): ?string
{
    $agent = $input['agent_id'] ?? null;

    return is_string($input['agent_type'] ?? null) && isset(reviewerModels()[$input['agent_type']]) && is_string($agent) && $agent !== '' ? $agent : null;
}

function reviewRunFile(string $stateDir, string $agent): string
{
    return "{$stateDir}/".REVIEW_RUN.sha1($agent).'.json';
}

/**
 * @return array<string, mixed>
 */
function readReviewRun(string $stateDir, string $agent): array
{
    $run = json_decode((string) @file_get_contents(reviewRunFile($stateDir, $agent)), true);

    return is_array($run) ? $run : [];
}

/**
 * @param  array<string, mixed>  $run
 */
function writeReviewRun(string $stateDir, string $agent, array $run): void
{
    writeFile(reviewRunFile($stateDir, $agent), json_encode($run, JSON_OUTPUT | JSON_THROW_ON_ERROR));
}

/**
 * The untracked files git does not ignore.
 *
 * @return list<string>
 */
function untrackedFiles(): array
{
    return array_values(array_filter(explode("\n", git(['ls-files', '--others', '--exclude-standard']) ?? '')));
}

/**
 * The lane, round and counts of a reviewer's last line, or null when it has none.
 *
 * @return array{lane: string, round: int, p0: int, p1: int, user: int, unwalked: ?int}|null
 */
function reviewResult(string $message): ?array
{
    $lines = array_values(array_filter(array_map('trim', explode("\n", $message)), fn (string $line): bool => $line !== ''));

    if ($lines === [] || preg_match(REVIEW_RESULT, $lines[count($lines) - 1], $match) !== 1 || ! isset(REVIEW_LANES[$match[1]])) {
        return null;
    }

    return ['lane' => $match[1], 'round' => (int) $match[2], 'p0' => (int) $match[3], 'p1' => (int) $match[4], 'user' => min((int) ($match[5] ?? 0), (int) $match[3] + (int) $match[4]), 'unwalked' => ($match[6] ?? '') === '' ? null : (int) $match[6]];
}

/**
 * How many items a behaviour run did not walk (docs/workflow.md, step 4): the
 * count of its result line, or more when the plan has a walk list and the report
 * has no `W<k> walked` line for an item of it. Without a count and without a list,
 * nothing shows that the walk was complete, so it counts as one item not walked.
 *
 * @param  list<string>  $plans
 */
function unwalkedItems(?int $declared, string $report, array $plans): int
{
    $items = [];

    foreach ($plans as $name) {
        foreach (["docs/exec-plans/active/{$name}", "docs/exec-plans/completed/{$name}"] as $path) {
            if ($items === [] && is_file($path)) {
                preg_match_all(WALK_ITEM, sections((string) file_get_contents($path))['Validation'] ?? '', $match);
                $items = array_values(array_unique($match[1]));
            }
        }
    }

    preg_match_all(WALKED_ITEM, $report, $walked);
    $missing = count(array_diff($items, array_map('strtoupper', $walked[1])));

    return max($declared ?? ($items === [] ? 1 : 0), $missing);
}

/**
 * The reviewer subagents (.claude/agents/reviewer-*.md) and the model each names.
 *
 * @return array<string, string>
 */
function reviewerModels(): array
{
    $reviewers = [];

    foreach (glob('.claude/agents/reviewer-*.md') ?: [] as $file) {
        $text = (string) file_get_contents($file);

        if (preg_match('/^name:\s*(\S+)\s*$/m', $text, $name) === 1 && preg_match('/^model:\s*(\S+)\s*$/m', $text, $model) === 1) {
            $reviewers[$name[1]] = $model[1];
        }
    }

    return $reviewers;
}

/**
 * The plan named by the request that started a subagent: the first message of its
 * transcript, by file name (docs/workflow.md, step 5); null when it names none.
 *
 * @return list<string>|null
 */
function requestPlans(mixed $path): ?array
{
    $handle = is_string($path) && is_file($path) ? @fopen($path, 'r') : false;

    if ($handle === false) {
        return null;
    }

    $request = null;

    while ($request === null && ($line = fgets($handle)) !== false) {
        $entry = json_decode($line, true);
        $message = is_array($entry) && is_array($entry['message'] ?? null) ? $entry['message'] : [];

        if (($message['role'] ?? null) === 'user') {
            $content = $message['content'] ?? '';
            $request = is_string($content) ? $content : implode("\n", array_map(fn (mixed $block): string => is_array($block) && is_string($block['text'] ?? null) ? $block['text'] : '', is_array($content) ? $content : []));
        }
    }

    fclose($handle);

    return $request !== null && preg_match(REQUEST_PLAN, $request, $match) === 1 ? [$match[1]] : null;
}

/**
 * The model of the subagent's last answer in its transcript, or null. Only the
 * answers count: a tool definition may name another model.
 */
function transcriptModel(mixed $path): ?string
{
    if (! is_string($path) || ! is_file($path)) {
        return null;
    }

    $model = null;

    foreach (explode("\n", (string) file_get_contents($path)) as $line) {
        $entry = json_decode($line, true);
        $message = is_array($entry) && is_array($entry['message'] ?? null) ? $entry['message'] : [];

        if (($message['role'] ?? null) === 'assistant' && is_string($message['model'] ?? null) && str_starts_with($message['model'], 'claude-')) {
            $model = $message['model'];
        }
    }

    return $model;
}

/**
 * The ledger's lines, oldest first; a line that is not a ledger line is left out.
 * An older line may carry `milestone` (a milestone round, which docs/workflow.md
 * step 4 no longer has).
 *
 * @return list<array{session: string, reviewer: string, model: string, lane: ?string, round: int, milestone?: bool, p0: int, p1: int, user?: int, unwalked?: int, head: string, committed: bool, fingerprint: string, plans: list<string>}>
 */
function readLedger(string $stateDir): array
{
    $runs = [];

    foreach (explode("\n", (string) @file_get_contents("{$stateDir}/".REVIEW_LEDGER)) as $line) {
        $run = json_decode($line, true);

        if (is_array($run) && is_bool($run['milestone'] ?? false) && is_bool($run['committed'] ?? null) && is_string($run['head'] ?? null) && is_string($run['session'] ?? null) && is_string($run['model'] ?? null) && is_string($run['fingerprint'] ?? null)
            && (is_string($run['lane'] ?? null) || ($run['lane'] ?? null) === null) && is_int($run['round'] ?? null) && is_int($run['p0'] ?? null) && is_int($run['p1'] ?? null) && is_array($run['plans'] ?? null)) {
            $runs[] = $run;
        }
    }

    return $runs;
}

/**
 * Whether code (a file outside docs/ and *.md) in one of the lane's places differs
 * from the commit, in the commits since or in the working tree. A commit that no
 * longer exists counts as changed for every lane.
 */
function laneChangedSince(string $commit, string $lane): bool
{
    static $lanes = [];

    if (! isset($lanes[$commit])) {
        $lanes[$commit] = $commit === '' || git(['cat-file', '-e', "{$commit}^{commit}"]) === null
            ? array_keys(REVIEW_LANES)
            : touchedLanes(array_values(array_filter(changedSince($commit), 'isCodePath')));
    }

    return in_array($lane, $lanes[$commit], true);
}

/**
 * Code is every file outside docs/ and *.md, and the reference feature's files
 * other than Markdown (docs/workflow.md, step 4): the files fingerprint() covers.
 */
function isCodePath(string $path): bool
{
    return ! str_ends_with($path, '.md') && (! str_starts_with($path, 'docs/') || str_starts_with($path, REFERENCE_FEATURE));
}

/**
 * The lanes a list of changed files touches, in the order of REVIEW_LANES.
 *
 * @param  list<string>  $paths
 * @return list<string>
 */
function touchedLanes(array $paths): array
{
    // A file of the reference feature touches what it would touch in the app.
    $paths = array_map(fn (string $path): string => isCodePath($path) && str_starts_with($path, REFERENCE_FEATURE) ? substr($path, strlen(REFERENCE_FEATURE)) : $path, $paths);

    return array_keys(array_filter(REVIEW_LANES, fn (array $places): bool => array_filter(
        $paths,
        fn (string $path): bool => array_filter($places, fn (string $place): bool => $path === $place || (str_ends_with($place, '/') && str_starts_with($path, $place))) !== [],
    ) !== []));
}

/**
 * Every file the plan's work changed: since the commit before the plan was first
 * added, or, for a plan not yet committed, in this conversation.
 *
 * @param  list<string>  $changed
 * @return list<string>
 */
function planChanges(string $plan, array $changed): array
{
    $name = basename($plan);
    $added = array_filter(explode("\n", git(['log', '--format=%H', '--diff-filter=A', '--', "docs/exec-plans/active/{$name}", "docs/exec-plans/completed/{$name}"]) ?? ''));

    if ($added === []) {
        return $changed;
    }

    // The empty tree, when the plan came with the first commit.
    $before = git(['rev-parse', '--verify', '--quiet', end($added).'^']) ?? '4b825dc642cb6eb9a060e54bf8d69288fbee4904';

    return array_values(array_unique([...changedSince($before), ...$changed]));
}

/**
 * Closing work needs real full validation across sessions. Remember a refused
 * archive so the next session cannot mistake it for an accepted historical plan.
 *
 * @param  list<string>  $plans
 * @param  array<string, mixed>  $state
 * @return list<string>
 */
function finalPlanPaths(array $plans, array $state, string $stateDir, bool $checked): array
{
    $file = "{$stateDir}/pending-plans.json";
    $pending = $before = readJson($file);
    $final = [];
    foreach ($plans as $plan) {
        $name = basename($plan);
        $active = str_starts_with($plan, 'docs/exec-plans/active/');
        $historical = isset($state['completedPlans'])
            ? in_array($plan, $state['completedPlans'], true)
            : (array_key_exists($plan, $state['dirty']) || git(['cat-file', '-e', "{$state['base']}:{$plan}"]) !== null);
        $finishing = $active ? allMilestonesTicked((string) file_get_contents($plan)) : (! $historical || isset($pending[$name]));
        if ($finishing) {
            $final[] = $plan;
        }
        if ($finishing && ! $checked) {
            $pending[$name] = true;
        } elseif ($checked || $active) {
            unset($pending[$name]);
        }
    }
    if ($pending !== $before) {
        CheckRun::write($file, $pending);
    }

    return $final;
}

/**
 * What one plan of this conversation is missing.
 *
 * @return array<string, array{string, string}>
 */
function planSteps(string $plan, bool $checked): array
{
    $markdown = (string) file_get_contents($plan);
    $steps = [];
    $empty = emptySections($markdown);

    // Code follows what the plan agreed, so a plan that still holds the template agrees nothing.
    if ($empty !== []) {
        $steps["empty:{$plan}"] = ["fill in {$plan}", "{$plan} is not filled in (empty: ".implode(', ', $empty).'). Fill the plan in (docs/workflow.md, step 2, and docs/PLANS.md): what the user gets, every answer from the grilling, the milestones.'];
    }

    // Every milestone done and checked: what is left is the review and the report.
    if (str_starts_with($plan, 'docs/exec-plans/active/') && $checked && allMilestonesTicked($markdown)) {
        $steps["finish:{$plan}"] = ["finish {$plan} and move it to completed/", "Every milestone in {$plan} is ticked and composer check passed. Fill in Outcomes & Retrospective and move the plan to docs/exec-plans/completed/ (docs/workflow.md, steps 4 and 5: the review rounds come first)."];
    }

    // A completed plan records the check results, the review rounds and the outcome.
    $missing = str_starts_with($plan, 'docs/exec-plans/completed/') ? missingRecords($markdown) : [];

    if ($missing !== []) {
        $steps["completed:{$plan}"] = ["complete the records in {$plan}", "{$plan} is complete but has ".implode(', ', $missing).'. Fill them in (docs/workflow.md, steps 3 to 5).'];
    }

    return $steps;
}

/**
 * What the AI tool reads on stdout when the AI is about to stop; nothing lets it stop.
 *
 * @param  list<string>  $send  reasons that send the AI back
 * @param  list<string>  $skipped  steps the AI stops without, for the user
 */
function stopOutput(array $send, array $skipped): string
{
    $output = [];

    if ($send !== []) {
        $lines = array_map(fn (string $reason, int $i): string => ($i + 1).'. '.$reason, $send, array_keys($send));
        $output = ['decision' => 'block', 'reason' => "Before you stop (docs/golden-principles.md, principle 9):\n".implode("\n", $lines)];
    }

    if ($skipped !== []) {
        $output['systemMessage'] = 'The finish check sent these steps back '.MAX_BLOCKS.' times and let the AI stop without them: '.implode('; ', $skipped).'. The next conversation is told about them.';
    }

    return $output === [] ? '' : json_encode($output, JSON_OUTPUT | JSON_THROW_ON_ERROR);
}

/**
 * Keeps the steps this conversation stops without in open.json, shared by every
 * conversation in this worktree, until the next one starts. A step done later in
 * the same conversation drops out at its next stop.
 *
 * @param  list<string>  $reasons
 */
function recordOpenSteps(string $stateDir, string $sessionKey, array $reasons): void
{
    $file = "{$stateDir}/open.json";
    $open = readJson($file);

    if ($reasons === [] && ! isset($open[$sessionKey])) {
        return;
    }

    unset($open[$sessionKey]);

    if ($reasons !== []) {
        $open[$sessionKey] = $reasons;
    }

    writeFile($file, json_encode($open, JSON_THROW_ON_ERROR));
}

/**
 * The steps earlier conversations stopped without, as context for the AI, once.
 */
function takeOpenSteps(string $stateDir): string
{
    $file = "{$stateDir}/open.json";
    $reasons = [];

    foreach (readJson($file) as $sessionReasons) {
        $reasons = [...$reasons, ...array_filter(is_array($sessionReasons) ? $sessionReasons : [], 'is_string')];
    }

    if (is_file($file) && ! unlink($file)) {
        throw new RuntimeException("cannot remove {$file}");
    }

    if ($reasons === []) {
        return '';
    }

    $lines = array_map(fn (string $reason): string => "- {$reason}", array_values(array_unique($reasons)));

    return "The previous conversation stopped with these steps missing (docs/golden-principles.md, principle 9):\n".implode("\n", $lines)."\nTell the user about them before you start on anything else.\n";
}

/**
 * composer check starts: remember the code it is about to check, and when.
 */
function checking(string $stateDir, bool $milestone = false): string
{
    $prefix = $milestone ? 'milestone-' : '';
    $id = CheckRun::owner($stateDir);
    writeFile("{$stateDir}/{$prefix}checking.json", json_encode(['id' => $id, 'fingerprint' => fingerprint(), 'time' => microtime(true)], JSON_THROW_ON_ERROR));

    return '';
}

/**
 * composer check passed: record the code as checked. Only the code it started on
 * counts (an edit during the run was not checked), and only when the tests ran to
 * their end.
 */
function checked(string $stateDir, bool $milestone = false): string
{
    $prefix = $milestone ? 'milestone-' : '';
    $command = $milestone ? 'composer check:milestone' : 'composer check';
    $start = readJson("{$stateDir}/{$prefix}checking.json");
    $fingerprint = fingerprint();

    if (! is_string($start['fingerprint'] ?? null) || ! is_numeric($start['time'] ?? null)) {
        throw new RuntimeException("{$command} did not record its start (scripts/hooks/finish-check.php {$prefix}checking is its first step).");
    }

    $id = CheckRun::owner($stateDir);
    if (($start['id'] ?? null) !== $id) {
        throw new RuntimeException('The validation start belongs to another run. Run the check again.');
    }

    if ($start['fingerprint'] !== $fingerprint) {
        throw new RuntimeException("the code changed while {$command} ran. Run {$command} again.");
    }

    $finished = readJson(TESTS_FINISHED);
    if (! $milestone && (($finished['id'] ?? null) !== $id || ! is_numeric($finished['time'] ?? null) || (float) $finished['time'] <= (float) $start['time'])) {
        throw new RuntimeException('the tests did not run to their end (tests/Structure/RunFinishedTest.php, the last test, did not run in this check). Run the whole composer check.');
    }

    CheckRun::write("{$stateDir}/{$prefix}checked", ['version' => 2, 'id' => $id, 'fingerprint' => $fingerprint]);

    return '';
}

function sessionFile(string $stateDir, string $session): string
{
    return "{$stateDir}/session-".sha1($session).'.json';
}

/**
 * The start of this conversation, or null when it was never recorded or its commit
 * no longer exists (history rewritten).
 *
 * @return array<string, mixed>|null
 */
function readSession(string $file): ?array
{
    $state = readJson($file);
    $base = $state['base'] ?? null;

    return is_string($base) && $base !== '' && git(['cat-file', '-e', "{$base}^{commit}"]) !== null ? $state : null;
}

/**
 * Where a conversation starts: the commit, the files already changed (with their
 * content), the Decision Logs of plans not yet committed, and the tech-debt lines.
 *
 * @return array<string, mixed>
 */
function newSession(): array
{
    $dirty = [];
    $logs = [];

    foreach (changedSince(null) as $path) {
        $dirty[$path] = contentHash($path);

        // The start commit does not have these lines yet, so they are remembered here.
        if (preg_match(PLAN_PATH, $path) === 1 && is_file($path)) {
            $logs[$path] = decisionLines((string) file_get_contents($path));
        }
    }

    return [
        'base' => git(['rev-parse', 'HEAD']) ?? '',
        'dirty' => $dirty,
        'logs' => $logs,
        'completedPlans' => glob('docs/exec-plans/completed/*.md') ?: [],
        'debt' => debtRows((string) @file_get_contents('docs/exec-plans/tech-debt.md')),
        'blocks' => [],
    ];
}

/**
 * @return array<array-key, mixed>
 */
function readJson(string $file): array
{
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

    return is_array($data) ? $data : [];
}

/**
 * Writes a state file; a state that cannot be saved is an error, not a silent pass.
 */
function writeFile(string $file, string $content): void
{
    if (@file_put_contents($file, $content) === false) {
        throw new RuntimeException(error_get_last()['message'] ?? "cannot write {$file}");
    }
}

/**
 * Runs git and returns its trimmed output, or null when it fails.
 *
 * @param  list<string>  $args
 * @param  array<string, string>  $env
 */
function git(array $args, array $env = []): ?string
{
    $process = proc_open(
        ['git', '-c', 'core.quotePath=false', ...$args],
        [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']],
        $pipes,
        null,
        $env === [] ? null : [...getenv(), ...$env],
    );

    if (! is_resource($process)) {
        return null;
    }

    $output = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    return proc_close($process) === 0 ? trim($output) : null;
}

/**
 * Files that differ from $base (or from HEAD) in the working tree, untracked files included.
 *
 * @return list<string>
 */
function changedSince(?string $base): array
{
    $tracked = git(['diff', '--name-only', '--no-renames', $base ?? 'HEAD']) ?? '';
    $untracked = git(['ls-files', '--others', '--exclude-standard']) ?? '';
    $paths = array_filter(array_merge(explode("\n", $tracked), explode("\n", $untracked)));

    return array_values(array_unique($paths));
}

/**
 * Files changed in this conversation. A file that was already changed when the
 * conversation started counts only if it changed again.
 *
 * @param  array<string, mixed>  $state
 * @return list<string>
 */
function changedInConversation(array $state): array
{
    return array_values(array_filter(
        changedSince($state['base']),
        fn (string $path): bool => ! array_key_exists($path, $state['dirty']) || $state['dirty'][$path] !== contentHash($path),
    ));
}

/**
 * @param  list<string>  $code
 */
function isOneLineChange(string $base, array $code): bool
{
    if (count($code) !== 1 || git(['cat-file', '-e', "{$base}:{$code[0]}"]) === null) {
        return false;
    }

    [$added, $deleted] = explode("\t", git(['diff', '--numstat', '--no-renames', $base, '--', $code[0]]) ?? '') + ['', ''];

    // One line added, or one line replaced (a blank line next to it may come along).
    return is_numeric($added) && is_numeric($deleted) && (int) $added <= 2 && (int) $deleted <= 1;
}

/**
 * @return list<string>
 */
function debtRows(string $markdown): array
{
    return array_values(array_filter(array_map('trim', explode("\n", $markdown)), fn (string $line): bool => preg_match('/^\|\s*20\d\d-/', $line) === 1));
}

function isProtected(string $path): bool
{
    foreach (PROTECTED_PATHS as $protected) {
        if ($path === $protected || (str_ends_with($protected, '/') && str_starts_with($path, $protected))) {
            return true;
        }
    }

    return false;
}

/**
 * Decision Log lines written in this conversation: lines the plan did not have at
 * the start, in active/ or completed/ (moving a plan makes none of its lines new).
 *
 * @param  array<string, mixed>  $state
 * @param  list<string>  $plans
 * @return list<string>
 */
function newDecisions(array $state, array $plans): array
{
    $new = [];

    foreach ($plans as $plan) {
        $name = substr($plan, (int) strrpos($plan, '/') + 1);
        $before = [];

        foreach (["docs/exec-plans/active/{$name}", "docs/exec-plans/completed/{$name}"] as $path) {
            // A plan not yet committed at the start was remembered then; otherwise the start commit has it.
            $before = [...$before, ...($state['logs'][$path] ?? decisionLines((string) git(['show', "{$state['base']}:{$path}"])))];
        }

        $new = [...$new, ...array_diff(decisionLines((string) file_get_contents($plan)), $before)];
    }

    return $new;
}

/**
 * @return list<string>
 */
function decisionLines(string $plan): array
{
    return array_values(array_filter(array_map('trim', explode("\n", sections($plan)['Decision Log'] ?? '')), fn (string $line): bool => $line !== ''));
}

/**
 * Whether the AI stops to ask the user about these files: its message ends with a
 * question and names each of them. A question asked earlier in a longer message
 * does not wait for the answer.
 *
 * @param  list<string>  $paths
 */
function isAskingAbout(string $message, array $paths): bool
{
    $lines = array_values(array_filter(array_map('trim', explode("\n", $message)), fn (string $line): bool => $line !== ''));
    $last = $lines === [] ? '' : $lines[count($lines) - 1];

    return (str_ends_with($last, '?') || str_ends_with($last, '？'))
        && array_filter($paths, fn (string $path): bool => ! str_contains($message, $path)) === [];
}

/**
 * The PLAN_ESSENTIALS sections that hold nothing but the template of docs/PLANS.md
 * or its <placeholders>.
 *
 * @return list<string>
 */
function emptySections(string $plan): array
{
    preg_match('/```markdown\n(.*?)```/s', (string) @file_get_contents('docs/PLANS.md'), $template);
    $templateLines = array_map('trim', explode("\n", $template[1] ?? ''));
    $sections = sections($plan);

    return array_values(array_filter(PLAN_ESSENTIALS, function (string $name) use ($sections, $templateLines): bool {
        foreach (explode("\n", $sections[$name] ?? '') as $line) {
            $line = trim($line);
            // A list number, checkbox or milestone id with nothing after it is no content either.
            $text = preg_replace(['/<[^>]*>/', '/^([-*+]|\d+[.)])\s*(\[[ xX]\])?\s*(M\d+\b)?/'], '', $line) ?? '';

            if (! in_array($line, $templateLines, true) && preg_match('/[\p{L}\p{N}]/u', $text) === 1) {
                return false;
            }
        }

        return true;
    }));
}

/**
 * Whether the plan's milestones are all ticked: at least one `- [x]` and no `- [ ]`.
 */
function allMilestonesTicked(string $plan): bool
{
    $milestones = sections($plan)['Milestones'] ?? '';

    return preg_match('/^\s*[-*+]\s+\[[xX]\]/m', $milestones) === 1 && preg_match('/^\s*[-*+]\s+\[ \]/m', $milestones) === 0;
}

/** A valid active plan with at least one completed and one remaining milestone. */
function isIntermediatePlan(string $path): bool
{
    $plan = (string) file_get_contents($path);
    $milestones = sections($plan)['Milestones'] ?? '';

    return str_starts_with($path, 'docs/exec-plans/active/') && emptySections($plan) === []
        && preg_match('/^\s*[-*+]\s+\[[xX]\]/m', $milestones) === 1
        && preg_match('/^\s*[-*+]\s+\[ \]/m', $milestones) === 1;
}

/**
 * What a completed plan is missing.
 *
 * @return list<string>
 */
function missingRecords(string $plan): array
{
    $sections = sections($plan);

    $progress = $sections['Progress'] ?? '';
    $missing = [];

    if (preg_match('/\bcomposer check(?![:\w-])/i', $progress) !== 1) {
        $missing[] = 'no composer check result in Progress';
    }

    if (stripos($progress, 'review') === false) {
        $missing[] = 'no review round in Progress';
    }

    if (mb_strlen(preg_replace('/<[^>]*>/', '', $sections['Outcomes & Retrospective'] ?? '') ?? '') < 20) {
        $missing[] = 'an empty Outcomes & Retrospective';
    }

    return $missing;
}

/**
 * A plan's sections by heading.
 *
 * @return array<string, string>
 */
function sections(string $plan): array
{
    $sections = [];

    foreach (preg_split('/^## /m', $plan) ?: [] as $section) {
        $lines = explode("\n", $section, 2);
        $sections[trim($lines[0])] = trim($lines[1] ?? '');
    }

    return $sections;
}

function contentHash(string $path): string
{
    return is_file($path) ? (git(['hash-object', '--', $path]) ?? '') : 'deleted';
}

/**
 * A hash of every file outside docs/ and *.md, as it is in the working tree now.
 */
function fingerprint(): string
{
    $index = tempnam(sys_get_temp_dir(), 'ezdev-index');
    @unlink($index);
    $env = ['GIT_INDEX_FILE' => $index];

    git(['read-tree', 'HEAD'], $env);
    git(['add', '-A'], $env);
    $files = git(['ls-files', '-s'], $env) ?? '';
    @unlink($index);

    $code = array_filter(explode("\n", $files), fn (string $line): bool => isCodePath(explode("\t", $line, 2)[1] ?? ''));

    return sha1(implode("\n", $code));
}

/**
 * @param  list<string>  $paths
 */
function summary(array $paths): string
{
    $shown = array_slice($paths, 0, 3);
    $more = count($paths) - count($shown);

    return implode(', ', $shown).($more > 0 ? " and {$more} more" : '');
}
