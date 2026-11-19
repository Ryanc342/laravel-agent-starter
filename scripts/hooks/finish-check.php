<?php

/*
| The finish check (docs/workflow.md).
|
| Claude Code (.claude/settings.json) runs this script as hooks; composer check
| runs it first and last:
|
|   php scripts/hooks/finish-check.php start      SessionStart: remember where this conversation starts; says nothing
|   php scripts/hooks/finish-check.php approve    UserPromptSubmit: the user's "开始" snapshots the active plans
|   php scripts/hooks/finish-check.php stop       Stop: the four checks; send back what is missing
|   php scripts/hooks/finish-check.php reviewed   SubagentStop of a reviewer: write its ledger line
|   php scripts/hooks/finish-check.php checking   composer check starts: remember the code it checks
|   php scripts/hooks/finish-check.php checked    composer check passed: record that code as checked
|   milestone-checking / milestone-checked       the same for composer check:milestone, a partial pass
|   review <plan>  read-only: print each lane of the plan as done or not done
|   lanes          print the review lanes and the places that touch them, as JSON
|   protected      print the protected files, as JSON
|
| The four checks at a stop, on the files this conversation changed:
|
| 1. Changed code has a plan: an active plan changed in this conversation or
|    approved (a snapshot), with Purpose, Agreed decisions and Milestones filled
|    in. A one-line change may skip it.
| 2. Changed code passed the checks on this code: a composer check:milestone pass
|    while the plan has milestones left, a full composer check at the end.
| 3. A finished plan has every lane it touches done (the review ledger, below).
| 4. A changed protected file is named in "Approved changes" of the snapshot.
|
| Each reminder is one line and goes back at most MAX_BLOCKS times per
| conversation; a code change does not reset its count. After that the AI may
| stop, and the user gets one message, at most one per conversation.
|
| Final acceptance. A plan is finished in this conversation when every milestone
| of an active plan is ticked, or when it moved from active/ to completed/ in this
| conversation. A plan that came into completed/ any other way (a sync from the
| template, an earlier conversation's archive) is history. A finished plan needs a
| full pass on the current code, also when this conversation changed only Markdown.
|
| Checks share one lock per worktree (scripts/check-run.php), held until the
| runner and its live descendants exit; a second, overlapping check says a check
| is already running and cannot replace that run's state. A new attempt revokes
| the passes before it, and only that attempt, finishing with its own
| end-of-suite proof (tests/Structure/RunFinishedTest.php), records a new pass.
| An interrupted run, or a pass recorded the old way (a fingerprint only), needs
| a new check. Use the Composer entry points, not the checking / checked modes.
|
| The approval snapshot. When the user's message, without spaces and punctuation
| and in lower case, is one of APPROVAL_WORDS, every plan in
| docs/exec-plans/active/ is copied to approved/<plan file name> in the state
| folder, with the commit it was taken at in approved/<plan file name>.commit. A
| new "开始" takes a new one. The checks read "Approved changes" and the walk list
| from the snapshot, so what the AI writes into the plan afterwards does not count.
|
| The review ledger. Each run of a reviewer subagent (.claude/agents/reviewer-*.md)
| writes one line to review-ledger.jsonl in the state folder: the lane, round and
| P0 and P1 counts from the result line of docs/prompts/review.md, the reviewer,
| its model, the HEAD commit and whether all code was committed. The reviewers run
| in the foreground (CLAUDE_CODE_DISABLE_BACKGROUND_TASKS in .claude/settings.json),
| so nothing changes the code while they read it. A finished plan needs, for each
| lane, a latest round with a clean run of each of the two models, with no code
| change since that touches the lane, however many rounds that takes. A plan that
| syncs the template into a project names the template's commit
| (`Template: <commit>`); a file that is as it is there is not reviewed.
|
| A conversation that changed no file is never stopped. The state lives in
| .git/ezdev/ (git rev-parse --git-path ezdev), outside the repository's files.
|
| Claude Code reads stdout: nothing lets the AI stop, one JSON object sends it
| back or tells the user. Anything else there would break that, so warnings go
| to stderr and every error ends as a message to the user.
*/

// AGENTS.md "Red lines": the template's own files, path by path. A file a project
// adds next to them (a helper in tests/Support/ or scripts/, a pattern of its own)
// is not protected.
const PROTECTED_FILES = [
    '' => ['AGENTS.md', 'CLAUDE.md', 'ARCHITECTURE.md', 'DESIGN.md', 'phpstan.neon', 'vite.config.ts', 'tsconfig.json', 'pint.json', 'phpunit.xml', '.npmrc', '.gitignore', 'artisan'],
    'docs/' => ['golden-principles.md', 'workflow.md', 'PLANS.md'],
    'docs/rules/' => ['actions.md', 'controllers.md', 'form-requests.md', 'frontend.md', 'models.md', 'tests.md', 'translations.md', 'visual-design.md'],
    'docs/design/' => ['components.md', 'foundations.md'],
    'docs/design/patterns/' => [
        'approval.md', 'auth.md', 'bulk-actions.md', 'calendar.md', 'checkout.md', 'contact.md', 'customer-area.md', 'dashboard.md', 'detail.md',
        'error-page.md', 'feed.md', 'form.md', 'help.md', 'import-export.md', 'kanban.md', 'landing.md', 'list-detail.md', 'list.md', 'pricing.md',
        'print.md', 'product.md', 'report.md', 'search-results.md', 'settings.md', 'states.md', 'wizard.md',
    ],
    'docs/prompts/' => ['cleanup.md', 'review.md', 'upgrade.md'],
    'docs/reference/tasks/' => ['README.md'],
    'docs/reference/tasks/app/Actions/Tasks/' => ['CompleteOverdueTasks.php', 'CompleteTask.php', 'CompleteTasks.php', 'CreateTask.php', 'DeleteTask.php', 'DeleteTasks.php', 'ImportTasks.php', 'UpdateTask.php'],
    'docs/reference/tasks/app/Concerns/' => ['ReadsTaskFilters.php', 'TaskValidationRules.php'],
    'docs/reference/tasks/app/Enums/' => ['TaskSort.php', 'TaskStatus.php'],
    'docs/reference/tasks/app/Http/Controllers/' => [
        'BulkTaskCompletionController.php', 'BulkTaskDeletionController.php', 'OverdueTaskCompletionController.php', 'TaskCompletionController.php', 'TaskController.php',
        'TaskExportController.php', 'TaskImportController.php', 'TaskImportTemplateController.php', 'TaskPdfController.php', 'TaskPrintController.php',
    ],
    'docs/reference/tasks/app/Http/Requests/Tasks/' => ['BulkTaskRequest.php', 'ImportTaskRequest.php', 'IndexTaskRequest.php', 'StoreTaskRequest.php', 'UpdateTaskRequest.php'],
    'docs/reference/tasks/app/Http/Resources/' => ['TaskResource.php'],
    'docs/reference/tasks/app/Models/' => ['Task.php'],
    'docs/reference/tasks/app/Policies/' => ['TaskPolicy.php'],
    'docs/reference/tasks/app/Support/' => ['TaskCsv.php'],
    'docs/reference/tasks/database/factories/' => ['TaskFactory.php'],
    'docs/reference/tasks/database/migrations/' => ['2026_10_24_000000_create_tasks_table.php'],
    'docs/reference/tasks/resources/js/components/tasks/' => ['task-actions.tsx', 'task-bulk-actions.tsx', 'task-fields.tsx', 'task-form-dialog.tsx', 'task-import-dialog.tsx', 'task-table.tsx'],
    'docs/reference/tasks/resources/js/pages/tasks/' => ['index.tsx', 'print.tsx', 'show.tsx'],
    'docs/reference/tasks/resources/js/types/' => ['task.ts'],
    'docs/reference/tasks/resources/views/tasks/' => ['pdf.blade.php'],
    'docs/reference/tasks/routes/' => ['tasks.php'],
    'docs/reference/tasks/tests/Feature/Tasks/' => [
        'CompleteOverdueTasksTest.php', 'CompleteTaskTest.php', 'CompleteTasksTest.php', 'CreateTaskTest.php', 'DeleteTaskTest.php', 'DeleteTasksTest.php',
        'ImportTasksTest.php', 'TaskBulkActionsTest.php', 'TaskButtonsTest.php', 'TaskControllerTest.php', 'TaskExportTest.php', 'TaskImportTest.php',
        'TaskListQueryTest.php', 'TaskPrintTest.php', 'TaskRulesTest.php', 'UpdateTaskTest.php',
    ],
    'lint/oxlint/' => ['ezdev.js'],
    'lint/phpstan/' => ['DisallowedTypeResolver.php'],
    'tests/' => ['bootstrap.php', 'Pest.php', 'TestCase.php'],
    'tests/js/' => ['setup.ts'],
    'tests/Structure/' => ['CheckSettings.php', 'CodeTest.php', 'ConfigTest.php', 'DesignDocChecks.php', 'DesignDocsTest.php', 'FoldersTest.php', 'PlacementChecks.php', 'ProjectFiles.php', 'RunFinishedTest.php'],
    'tests/Support/' => ['MilestoneWorkspace.php', 'TestsRowLocks.php', 'VirtualPasskey.php'],
    'tests/Unit/Hooks/' => ['CheckRunTest.php', 'FinishCheckTest.php', 'MilestoneCheckTest.php', 'PlanAcceptanceTest.php', 'PlaywrightMcpTest.php', 'ReviewLedgerTest.php', 'ReviewerCommandsTest.php', 'ScreenRoundTest.php'],
    'scripts/' => ['check-installer', 'check-milestone.php', 'check-process.php', 'check-reference', 'check-run.php', 'check.php', 'playwright-mcp', 'screen-round', 'screen-round.php', 'worktree-up'],
    'scripts/hooks/' => ['finish-check.php', 'reviewer-commands.php'],
    '.claude/' => ['settings.json'],
    '.claude/agents/' => ['explorer.md', 'reviewer-haiku.md', 'reviewer-opus.md'],
    '.claude/skills/wayfinder-development/' => ['SKILL.md'],
    '.github/' => ['dependabot.yml'],
    '.github/workflows/' => ['tests.yml'],
];

// The manifests are protected in their dependency sections only.
const DEPENDENCY_SECTIONS = [
    'composer.json' => ['require', 'require-dev'],
    'package.json' => ['dependencies', 'devDependencies', 'peerDependencies', 'optionalDependencies', 'overrides'],
];

// A lock file holds only dependencies; it is approved together with its manifest.
const LOCK_FILES = ['composer.lock' => 'composer.json', 'package-lock.json' => 'package.json'];

// What the user says to approve a plan, without spaces and punctuation, in lower case.
const APPROVAL_WORDS = ['开始', '开始吧', '好开始', '好开始吧', '好的开始', 'ok开始', 'start'];

const MAX_BLOCKS = 3;

// Where docs/PLANS.md puts plans.
const PLAN_PATH = '#^docs/exec-plans/(active|completed)/[^/]+\.md$#';

const ACTIVE = 'docs/exec-plans/active/';

const COMPLETED = 'docs/exec-plans/completed/';

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
// plan are numbered 1, 2, 3; `unwalked`, in lane `behaviour` only, counts the
// items of the walk it did not walk.
const REVIEW_RESULT = '/^`?RESULT lane=(\w+) round=(\d+) P0=(\d+) P1=(\d+)(?: unwalked=(\d+))?`?$/';

// The lane whose runs walk the plan's flows and report each item of the walk.
const WALK_LANE = 'behaviour';

// An item of a plan's walk list, under Validation (docs/PLANS.md): `W1 <role>: <flow>`.
const WALK_ITEM = '/^\s*(?:[-*+]\s+)?(W\d+)\s+[^:\n]+:/m';

// A line of a behaviour report for an item it walked: `W1 walked`.
const WALKED_ITEM = '/^\W*(W\d+)\s+walked\b/mi';

const REVIEW_LEDGER = 'review-ledger.jsonl';

// A plan that syncs the template into a project names the template's commit
// (docs/prompts/upgrade.md): a file identical to the template's there is not reviewed.
const TEMPLATE_COMMIT = '/^\s*(?:[-*+]\s+)?Template:\s*`?([0-9a-f]{7,40})`?/mi';

// The plan a reviewer's request names (docs/prompts/review.md): `Plan: <path>`.
const REQUEST_PLAN = '#\bPlan:\s*`?(?:\S*/)?([^/\s`]+\.md)\b#';

const JSON_OUTPUT = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

// The modes Claude Code runs as hooks; they read the hook's JSON on stdin.
const HOOK_MODES = ['start', 'approve', 'stop', 'reviewed'];

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

    // The lanes and the protected files are fixed: printing them needs no repository.
    if ($mode === 'lanes') {
        return json_encode(REVIEW_LANES, JSON_OUTPUT | JSON_THROW_ON_ERROR);
    }

    if ($mode === 'protected') {
        return json_encode(protectedFiles(), JSON_OUTPUT | JSON_THROW_ON_ERROR);
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

    return match ($mode) {
        'start' => start($stateDir, $session),
        'approve' => approve($stateDir, is_string($input['prompt'] ?? null) ? $input['prompt'] : ''),
        'stop' => stop($stateDir, $session),
        'checking' => checking($stateDir),
        'checked' => checked($stateDir),
        'milestone-checking' => checking($stateDir, true),
        'milestone-checked' => checked($stateDir, true),
        'reviewed' => reviewed($stateDir, $session, $input),
        'review' => reviewStatus($stateDir, $argument),
        default => '',
    };
}

/**
 * A conversation starts: record its start once. It says nothing.
 */
function start(string $stateDir, string $session): string
{
    $file = sessionFile($stateDir, $session);

    if (readSession($file) === null) {
        writeFile($file, json_encode(newSession(), JSON_THROW_ON_ERROR));
    }

    return '';
}

/**
 * The user sent a message: when it is the user's "开始", snapshot every active
 * plan with the commit it was taken at. A new "开始" replaces the snapshot.
 */
function approve(string $stateDir, string $prompt): string
{
    $word = mb_strtolower(preg_replace('/[\s\p{P}\p{S}]+/u', '', $prompt) ?? '');

    if (! in_array($word, APPROVAL_WORDS, true)) {
        return '';
    }

    $dir = "{$stateDir}/approved";

    if (! is_dir($dir) && ! mkdir($dir, 0777, true)) {
        throw new RuntimeException("cannot create {$dir}");
    }

    $head = git(['rev-parse', 'HEAD']) ?? '';

    foreach (glob(ACTIVE.'*.md') ?: [] as $plan) {
        writeFile("{$dir}/".basename($plan), (string) file_get_contents($plan));
        writeFile("{$dir}/".basename($plan).'.commit', "{$head}\n");
    }

    return '';
}

/**
 * The plan as the user approved it, or null when there is no snapshot.
 */
function snapshot(string $stateDir, string $name): ?string
{
    $file = "{$stateDir}/approved/{$name}";

    return is_file($file) ? (string) file_get_contents($file) : null;
}

/**
 * The AI is about to stop: send back what is missing. Each reminder goes back at
 * most MAX_BLOCKS times per conversation, so a stop never loops forever; after
 * that the AI stops, and the user is told once per conversation.
 */
function stop(string $stateDir, string $session): string
{
    $file = sessionFile($stateDir, $session);
    $state = readSession($file);

    if ($state === null) {
        // Changes made before this point cannot be told apart from the user's own, so they do not count.
        writeFile($file, json_encode([...newSession(), 'told' => true], JSON_THROW_ON_ERROR));

        return json_encode(['systemMessage' => 'The finish check had no start record for this conversation, so it counts changes from now on.'], JSON_OUTPUT | JSON_THROW_ON_ERROR);
    }

    // The active plans each stop sees, so that a plan moved to completed/ later is
    // known to have been active in this conversation.
    $state['seen'] = array_values(array_unique([...(is_array($state['seen'] ?? null) ? $state['seen'] : []), ...activePlanNames()]));
    $send = [];
    $released = [];

    foreach (missingSteps($state, $stateDir) as $key => [$step, $reason]) {
        $state['blocks'][$key] = ($state['blocks'][$key] ?? 0) + 1;

        if ($state['blocks'][$key] <= MAX_BLOCKS) {
            $send[] = $reason;
        } else {
            $released[] = $step;
        }
    }

    $tell = $released !== [] && ($state['told'] ?? false) !== true;

    if ($tell) {
        $state['told'] = true;
    }

    writeFile($file, json_encode($state, JSON_THROW_ON_ERROR));

    return stopOutput($send, $tell ? $released : []);
}

/**
 * The steps missing at this stop, each under a key that stays the same for the
 * whole conversation (MAX_BLOCKS counts per key): [short step for the user,
 * one-line reason for the AI].
 *
 * @param  array<string, mixed>  $state
 * @return array<string, array{string, string}>
 */
function missingSteps(array $state, string $stateDir): array
{
    $changed = changedInConversation($state);

    if ($changed === []) {
        return [];
    }

    $missing = [];
    $code = array_values(array_filter($changed, 'isCodePath'));
    $plans = conversationPlans($state, $stateDir, $changed);
    $final = array_values(array_filter($plans, fn (string $plan): bool => ! str_starts_with($plan, ACTIVE) || allMilestonesTicked((string) file_get_contents($plan))));
    $fingerprint = fingerprint();

    // 1. Changed code has a plan (a one-line change with no open decision may skip it).
    $filled = array_filter($plans, fn (string $plan): bool => emptySections((string) file_get_contents($plan)) === []);

    if ($code !== [] && $filled === [] && ! isOneLineChange($state['base'], $code)) {
        $empty = array_map(fn (string $plan): string => $plan.' is not filled in (empty: '.implode(', ', emptySections((string) file_get_contents($plan))).')', $plans);
        $missing['plan'] = ['write the plan', $plans === []
            ? 'You changed code ('.summary($code).') but there is no plan in docs/exec-plans/active/ for it: write the plan (docs/PLANS.md) and wait for the user\'s "开始".'
            : implode('; ', $empty).': fill in what the user gets, every agreed decision and the milestones (docs/PLANS.md).'];
    }

    // 2. Changed code passed the checks on this code; a finished plan needs a full pass.
    if (($code !== [] || $final !== []) && ! CheckRun::passed($stateDir, $fingerprint)) {
        $intermediate = $final === [] && count($plans) === 1 && isIntermediatePlan($plans[0]);

        if (! $intermediate) {
            $missing['check'] = ['pass composer check', ($final === [] ? 'The code changed since the last passing composer check.' : summary($final).' is finished, so the current code needs a full pass.').' Run composer check and fix every failure.'];
        } elseif (! CheckRun::passed($stateDir, $fingerprint, true)) {
            $missing['check'] = ['pass composer check:milestone', 'The code changed since the last passing milestone check. Run composer check:milestone -- <affected test paths> and fix every failure.'];
        }
    }

    // 3. A finished plan has every lane it touches done, where the reviewer subagents are defined.
    if (reviewerModels() !== []) {
        $ledger = readLedger($stateDir);

        foreach ($final as $plan) {
            $reason = finalReview($plan, touchedLanes(planChanges($stateDir, $plan, $changed)), $ledger);

            if ($reason !== null) {
                $missing['review:'.basename($plan)] = ['finish the review rounds of '.$plan, $reason];
            }
        }
    }

    // 4. A changed protected file is in "Approved changes" of the snapshot.
    $approved = implode("\n", array_map(fn (string $plan): string => sections(snapshot($stateDir, basename($plan)) ?? '')['Approved changes'] ?? '', $plans));
    $unapproved = array_values(array_filter($changed, fn (string $path): bool => isProtectedChange($path, $state['base']) && ! isApproved($path, $approved)));

    if ($unapproved !== []) {
        $missing['protected'] = ['revert '.implode(', ', $unapproved), 'Revert '.implode(', ', $unapproved).': protected (AGENTS.md "Red lines") and not in "Approved changes" of the plan as the user approved it; name them in the report.'];
    }

    return $missing;
}

/**
 * The plans of this conversation: every active plan changed in it or approved
 * (a snapshot), and every plan that moved from active/ to completed/ in it.
 * A plan that came into completed/ any other way is history.
 *
 * @param  array<string, mixed>  $state
 * @param  list<string>  $changed
 * @return list<string>
 */
function conversationPlans(array $state, string $stateDir, array $changed): array
{
    $plans = [];

    foreach (glob(ACTIVE.'*.md') ?: [] as $plan) {
        if (in_array($plan, $changed, true) || snapshot($stateDir, basename($plan)) !== null) {
            $plans[] = $plan;
        }
    }

    foreach (glob(COMPLETED.'*.md') ?: [] as $plan) {
        if (in_array($plan, $changed, true) && movedInConversation(basename($plan), $state)) {
            $plans[] = $plan;
        }
    }

    return $plans;
}

/**
 * Whether the plan moved from active/ to completed/ in this conversation: it was
 * not in completed/ when the conversation started, is not in active/ now, and was
 * in active/ at the start, at an earlier stop or in a commit since the start.
 *
 * @param  array<string, mixed>  $state
 */
function movedInConversation(string $name, array $state): bool
{
    if (is_file(ACTIVE.$name)) {
        return false;
    }

    $completedAtStart = isset($state['completedPlans'])
        ? in_array(COMPLETED.$name, $state['completedPlans'], true)
        : git(['cat-file', '-e', "{$state['base']}:".COMPLETED.$name]) !== null;

    if ($completedAtStart) {
        return false;
    }

    // A start record written before 1.4.0 has no list of active plans.
    $activeAtStart = $state['active'] ?? [
        ...array_map('basename', array_filter(array_keys($state['dirty']), fn (string $path): bool => str_starts_with($path, ACTIVE))),
        ...array_map('basename', array_filter(explode("\n", git(['ls-tree', '--name-only', $state['base'], ACTIVE]) ?? ''))),
    ];

    return in_array($name, [...$activeAtStart, ...($state['seen'] ?? [])], true)
        || (git(['log', '--format=%H', "{$state['base']}..HEAD", '--', ACTIVE.$name]) ?? '') !== '';
}

/**
 * The names of the plans in docs/exec-plans/active/.
 *
 * @return list<string>
 */
function activePlanNames(): array
{
    return array_map('basename', glob(ACTIVE.'*.md') ?: []);
}

/**
 * The template's own protected files, path by path.
 *
 * @return list<string>
 */
function protectedFiles(): array
{
    $files = [];

    foreach (PROTECTED_FILES as $folder => $names) {
        foreach ($names as $name) {
            $files[] = $folder.$name;
        }
    }

    return $files;
}

/**
 * Whether the change to $path is protected: one of the template's own files, a
 * lock file, or a manifest whose dependency sections changed since $base.
 */
function isProtectedChange(string $path, string $base): bool
{
    if (isset(DEPENDENCY_SECTIONS[$path])) {
        $sections = fn (?string $json): array => array_intersect_key(is_array($decoded = json_decode((string) $json, true)) ? $decoded : [], array_flip(DEPENDENCY_SECTIONS[$path]));

        return $sections(git(['show', "{$base}:{$path}"])) != $sections(is_file($path) ? (string) file_get_contents($path) : null);
    }

    return isset(LOCK_FILES[$path]) || in_array($path, protectedFiles(), true);
}

/**
 * Whether "Approved changes" names the file, or a folder above it, as a whole
 * path: `tests/` inside `tests/Unit/Hooks/` names nothing. A lock file and its
 * manifest are approved together.
 */
function isApproved(string $path, string $approved): bool
{
    $manifest = LOCK_FILES[$path] ?? $path;
    $names = [$path, $manifest, array_search($manifest, LOCK_FILES, true) ?: $manifest];
    $parts = explode('/', $path);

    for ($i = 1; $i < count($parts); $i++) {
        $names[] = implode('/', array_slice($parts, 0, $i)).'/';
    }

    foreach (array_unique($names) as $name) {
        if (preg_match('#(?<![\w./-])'.preg_quote($name, '#').'(?![\w-]|\.\w)#u', $approved) === 1) {
            return true;
        }
    }

    return false;
}

/**
 * Why the final review of a finished plan is not done (docs/workflow.md, step 4),
 * or null when it is. A lane is done when its latest round holds a clean run of
 * each of the two reviewer models, on committed code, and no code in the lane's
 * places changed since; docs/ and *.md void nothing. There is no cap on rounds:
 * the reason names how many rounds each open lane has had.
 *
 * @param  list<string>  $lanes
 * @param  list<array<string, mixed>>  $ledger
 */
function finalReview(string $plan, array $lanes, array $ledger): ?string
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
        return null;
    }

    $othersOpen = array_diff_key($open, array_flip(SCREEN_LANES)) !== [];
    $list = implode('; ', array_map(
        fn (string $lane, ?string $problem): string => $lane.' ('.($problem ?? (in_array($lane, SCREEN_LANES, true) && $othersOpen ? 'starts when the other lanes are done' : 'no round yet')).')',
        array_keys($open),
        $open,
    ));

    return 'Before '.$plan.' is finished, each lane it touches needs a round in which reviewer-opus and reviewer-haiku both report no P0 and no P1, on committed code that has not changed since in the lane\'s places. Not yet: '.$list.'. Rounds so far: '.implode(', ', $rounds).'. Fix the findings and run the next round (docs/workflow.md, step 4). '
        .(str_starts_with($plan, ACTIVE) ? 'Move the plan to completed/ only when `php scripts/hooks/finish-check.php review '.$plan.'` prints every lane as done.' : 'The plan stays unfinished until `php scripts/hooks/finish-check.php review '.$plan.'` prints every lane as done.');
}

/**
 * The ledger lines of the plan's rounds. An old line of a milestone round counts
 * for no lane: there is no review between milestones.
 *
 * @param  list<array<string, mixed>>  $ledger
 * @return list<array<string, mixed>>
 */
function finalRuns(array $ledger, string $plan): array
{
    return array_values(array_filter($ledger, fn (array $run): bool => $run['lane'] !== null && ($run['milestone'] ?? false) !== true && in_array(basename($plan), $run['plans'], true)));
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

    foreach (touchedLanes(planChanges($stateDir, $plan, changedSince(null))) as $lane) {
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
 * A reviewer subagent stopped: write its ledger line. The result line is read
 * from its last message, else from the report it handed back with the
 * SubagentHandback tool (a subagent that ran in the background). A reviewer that
 * left it out is sent back once to add it; a run without one counts as not run.
 * The code counts as committed when no code was left uncommitted at its end: the
 * builder waits for its reviewers, so nothing changed the code during the run.
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

    $again = ($input['stop_hook_active'] ?? false) === true;
    $transcript = $input['agent_transcript_path'] ?? null;
    $report = is_string($input['last_assistant_message'] ?? null) ? $input['last_assistant_message'] : '';
    $result = reviewResult($report);

    // A report handed back with SubagentHandback is read from the transcript,
    // which Claude Code may write a moment after the subagent stops.
    for ($try = 0; $result === null && $try < 20; $try++) {
        if ($try > 0) {
            usleep(150_000);
        }

        $report = handedBack($transcript) ?? '';
        $result = reviewResult($report);
    }

    if ($result === null && ! $again) {
        return json_encode(['decision' => 'block', 'reason' => 'End your answer with the result line of docs/prompts/review.md as its last line: RESULT lane=<lane> round=<n> P0=<count> P1=<count>, with one of the lanes '.implode(', ', array_keys(REVIEW_LANES)).', and in lane '.WALK_LANE.' unwalked=<count> after it. Without it this run counts as not run.'], JSON_OUTPUT | JSON_THROW_ON_ERROR);
    }

    if (($result['lane'] ?? null) === WALK_LANE && $result['unwalked'] === null && ! $again) {
        return json_encode(['decision' => 'block', 'reason' => 'In lane '.WALK_LANE.' the result line ends with the number of items of the walk you did not walk: RESULT lane='.WALK_LANE.' round=<n> P0=<count> P1=<count> unwalked=<count>. Above it, one line per item: "W1 walked", or "W1 not walked: <reason>" (docs/prompts/review.md). Add them; a run with an item not walked counts as not run.'], JSON_OUTPUT | JSON_THROW_ON_ERROR);
    }

    $plans = requestPlans($transcript) ?? activePlanNames();
    $line = [
        'time' => microtime(true),
        'session' => sha1($session),
        'reviewer' => $agent,
        'model' => transcriptModel($transcript) ?? $reviewers[$agent],
        'lane' => $result['lane'] ?? null,
        'round' => $result['round'] ?? 0,
        'p0' => $result['p0'] ?? 0,
        'p1' => $result['p1'] ?? 0,
        ...(($result['lane'] ?? null) === WALK_LANE ? ['unwalked' => unwalkedItems($stateDir, $result['unwalked'], $report, $plans)] : []),
        'head' => git(['rev-parse', 'HEAD']) ?? '',
        'committed' => array_filter(changedSince(null), 'isCodePath') === [],
        'fingerprint' => fingerprint(),
        // The plan the request names, also one already in completed/; without a
        // readable request, the plans in active/ (docs/workflow.md, step 5).
        'plans' => $plans,
    ];

    if (@file_put_contents("{$stateDir}/".REVIEW_LEDGER, json_encode($line, JSON_OUTPUT | JSON_THROW_ON_ERROR)."\n", FILE_APPEND | LOCK_EX) === false) {
        throw new RuntimeException('cannot write the review ledger');
    }

    return '';
}

/**
 * The lane, round and counts of a reviewer's last line, or null when it has none.
 *
 * @return array{lane: string, round: int, p0: int, p1: int, unwalked: ?int}|null
 */
function reviewResult(string $message): ?array
{
    $lines = array_values(array_filter(array_map('trim', explode("\n", $message)), fn (string $line): bool => $line !== ''));

    if ($lines === [] || preg_match(REVIEW_RESULT, $lines[count($lines) - 1], $match) !== 1 || ! isset(REVIEW_LANES[$match[1]])) {
        return null;
    }

    return ['lane' => $match[1], 'round' => (int) $match[2], 'p0' => (int) $match[3], 'p1' => (int) $match[4], 'unwalked' => ($match[5] ?? '') === '' ? null : (int) $match[5]];
}

/**
 * The report the subagent handed back last with the SubagentHandback tool, from
 * its transcript, or null.
 */
function handedBack(mixed $path): ?string
{
    $report = null;

    foreach (transcriptEntries($path) as $entry) {
        foreach (is_array($entry['message']['content'] ?? null) ? $entry['message']['content'] : [] as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'tool_use' && ($block['name'] ?? null) === 'SubagentHandback' && is_string($block['input']['message'] ?? null)) {
                $report = $block['input']['message'];
            }
        }
    }

    return $report;
}

/**
 * How many items a behaviour run did not walk (docs/workflow.md, step 4): the
 * count of its result line, or more when the plan as approved has a walk list
 * and the report has no `W<k> walked` line for an item of it. Without a count and
 * without a list, nothing shows that the walk was complete, so it counts as one
 * item not walked.
 *
 * @param  list<string>  $plans
 */
function unwalkedItems(string $stateDir, ?int $declared, string $report, array $plans): int
{
    $items = [];

    foreach ($plans as $name) {
        foreach ([snapshot($stateDir, $name), @file_get_contents(ACTIVE.$name), @file_get_contents(COMPLETED.$name)] as $plan) {
            if ($items === [] && is_string($plan)) {
                preg_match_all(WALK_ITEM, sections($plan)['Validation'] ?? '', $match);
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
 * The entries of a subagent's transcript, one JSON object per line.
 *
 * @return list<array<string, mixed>>
 */
function transcriptEntries(mixed $path): array
{
    if (! is_string($path) || ! is_file($path)) {
        return [];
    }

    return array_values(array_filter(array_map(fn (string $line): mixed => json_decode($line, true), explode("\n", (string) file_get_contents($path))), 'is_array'));
}

/**
 * The plan named by the request that started a subagent: the first message of its
 * transcript, by file name (docs/workflow.md, step 5); null when it names none.
 *
 * @return list<string>|null
 */
function requestPlans(mixed $path): ?array
{
    foreach (transcriptEntries($path) as $entry) {
        $message = is_array($entry['message'] ?? null) ? $entry['message'] : [];

        if (($message['role'] ?? null) === 'user') {
            $content = $message['content'] ?? '';
            $request = is_string($content) ? $content : implode("\n", array_map(fn (mixed $block): string => is_array($block) && is_string($block['text'] ?? null) ? $block['text'] : '', is_array($content) ? $content : []));

            return preg_match(REQUEST_PLAN, $request, $match) === 1 ? [$match[1]] : null;
        }
    }

    return null;
}

/**
 * The model of the subagent's last answer in its transcript, or null. Only the
 * answers count: a tool definition may name another model.
 */
function transcriptModel(mixed $path): ?string
{
    $model = null;

    foreach (transcriptEntries($path) as $entry) {
        $message = is_array($entry['message'] ?? null) ? $entry['message'] : [];

        if (($message['role'] ?? null) === 'assistant' && is_string($message['model'] ?? null) && str_starts_with($message['model'], 'claude-')) {
            $model = $message['model'];
        }
    }

    return $model;
}

/**
 * The ledger's lines, oldest first; a line that is not a ledger line is left out.
 * An older line may carry `milestone` (a milestone round, which docs/workflow.md
 * no longer has) or `user` (findings marked for the user, which it no longer has).
 *
 * @return list<array{session: string, reviewer: string, model: string, lane: ?string, round: int, milestone?: bool, p0: int, p1: int, unwalked?: int, head: string, committed: bool, fingerprint: string, plans: list<string>}>
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
 * added, or, for a plan not yet committed, in this conversation. A file that is
 * as it is in the template commit a sync plan names is left out.
 *
 * @param  list<string>  $changed
 * @return list<string>
 */
function planChanges(string $stateDir, string $plan, array $changed): array
{
    $name = basename($plan);
    $added = array_filter(explode("\n", git(['log', '--format=%H', '--diff-filter=A', '--', ACTIVE.$name, COMPLETED.$name]) ?? ''));
    // The empty tree, when the plan came with the first commit.
    $before = $added === [] ? null : git(['rev-parse', '--verify', '--quiet', end($added).'^']) ?? '4b825dc642cb6eb9a060e54bf8d69288fbee4904';
    $paths = $before === null ? $changed : array_values(array_unique([...changedSince($before), ...$changed]));
    $template = preg_match(TEMPLATE_COMMIT, snapshot($stateDir, $name) ?? (string) file_get_contents($plan), $match) === 1
        ? git(['rev-parse', '--verify', '--quiet', $match[1].'^{commit}'])
        : null;

    if ($template === null) {
        return $paths;
    }

    return array_values(array_filter($paths, fn (string $path): bool => (git(['rev-parse', '--verify', '--quiet', "{$template}:{$path}"]) ?? 'deleted') !== contentHash($path)));
}

/**
 * What Claude Code reads on stdout when the AI is about to stop; nothing lets it stop.
 *
 * @param  list<string>  $send  reasons that send the AI back, one line each
 * @param  list<string>  $released  steps the AI stops without, for the user
 */
function stopOutput(array $send, array $released): string
{
    $output = [];

    if ($send !== []) {
        $output = ['decision' => 'block', 'reason' => "Before you stop:\n".implode("\n", array_map(fn (string $reason): string => "- {$reason}", $send))];
    }

    if ($released !== []) {
        $output['systemMessage'] = 'The finish check let the AI stop without: '.implode('; ', $released).'.';
    }

    return $output === [] ? '' : json_encode($output, JSON_OUTPUT | JSON_THROW_ON_ERROR);
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

    return is_string($base) && $base !== '' && git(['cat-file', '-e', "{$base}^{commit}"]) !== null && is_array($state['dirty'] ?? null) ? $state : null;
}

/**
 * Where a conversation starts: the commit, the files already changed (with their
 * content), and the plans in active/ and completed/.
 *
 * @return array<string, mixed>
 */
function newSession(): array
{
    $dirty = [];

    foreach (changedSince(null) as $path) {
        $dirty[$path] = contentHash($path);
    }

    return [
        'base' => git(['rev-parse', 'HEAD']) ?? '',
        'dirty' => $dirty,
        'active' => activePlanNames(),
        'completedPlans' => glob(COMPLETED.'*.md') ?: [],
        'seen' => [],
        'blocks' => [],
        'told' => false,
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

    return str_starts_with($path, ACTIVE) && emptySections($plan) === []
        && preg_match('/^\s*[-*+]\s+\[[xX]\]/m', $milestones) === 1
        && preg_match('/^\s*[-*+]\s+\[ \]/m', $milestones) === 1;
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
