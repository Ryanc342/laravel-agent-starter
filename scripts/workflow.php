<?php

/*
| The workflow tool (docs/workflow.md). It keeps the steps of the open plan and
| tells the AI the next one:
|
|   draft -> approved -> built -> checked -> reviewed -> reported
|
|   php scripts/workflow.php status        where the plan is, and the next step
|   php scripts/workflow.php review        prepare the next review round
|   php scripts/workflow.php report        write the report, move the plan out of active/
|   php scripts/workflow.php cleanup       write a draft cleanup plan from the existing debt
|   php scripts/workflow.php upgrade       write a draft upgrade plan: outdated packages,
|                                          security advisories, template merge conflicts
|   php scripts/workflow.php hook <event>  Claude Code's hooks (.claude/settings.json), JSON on stdin
|   php scripts/workflow.php revoke | fingerprint | passed <fingerprint>
|                                          composer check (scripts/check-process.php)
|
| A plan is the one file in docs/exec-plans/active/, written as docs/PLANS.md
| says: one plan per folder (git worktree). The steps:
| - draft: the plan is written; a question may still be open.
| - approved: the user's message is 开始 and every question has an answer. The
|   tool records the plan's text; an edit of the plan afterwards voids the
|   approval, except under "## Waits for you".
| - built: every decision (D1...) and every screen (S1...) has a test that names
|   it (<plan>.D1), unless the decision says "(no test: <reason>)".
| - checked: since the approval, a composer check passed on the code as it is now.
| - reviewed: every card the plan's change touches, and the global pack, had a
|   review run with no finding, and their files did not change since.
| - reported: the report is written and the plan moved to completed/.
|
| The hooks: before the approval no file of code can be written (PreToolUse),
| and a command that changed code is told to put it back (PostToolUse). When
| the AI is about to stop after this conversation changed code (Stop), it is
| sent back once while a step is missing; right after a send-back the user gets
| one line instead. A conversation that changed no code is left alone. A new
| conversation is told where the plan is (SessionStart). A reviewer's last line,
| RESULT pack=<id> findings=<n>, is recorded with the rule gaps and the older
| code it lists (SubagentStop).
|
| Code is every file outside docs/ and *.md. The state lives in .git/ezdev/
| (git rev-parse --git-path ezdev), outside the repository's files.
|
| Claude Code reads stdout: nothing, or one JSON object. A hook always answers
| with exit 0, so an error ends as a message to the user, never as a silent pass.
*/

const ACTIVE = 'docs/exec-plans/active/';

const COMPLETED = 'docs/exec-plans/completed/';

// The template's own plans go here, where a project made from it never gets them.
const TEMPLATE_PLANS = 'template/plans/';

const DEBT = 'docs/exec-plans/tech-debt.md';

const OVERVIEW = '.claude/rules/skeleton.md';

const CARDS = '.claude/rules/skeleton/';

// Where `review` writes what the reviewers read (ignored by git).
const REVIEW_FILES = 'storage/framework/testing/ezdev-review/';

// Where Pest keeps the screenshot baselines of the browser tests (PAGE-8).
const BASELINES = 'tests/.pest/snapshots/Browser/';

// Where the browser tests save the screenshots a plan's screens take; the next
// run empties it, so the report copies them to REPORT_SHOTS (both ignored by git).
const SCREENSHOTS = 'tests/Browser/Screenshots/';

const REPORT_SHOTS = 'storage/app/private/reports/';

// The template's own tools: tested, never reviewed.
const NOT_REVIEWED = ['scripts/', 'template/'];

// The files that make a check: a change to one is told to the user.
const CHECK_FILES = ['phpstan.neon', 'vite.config.ts', 'tsconfig.json', 'phpunit.xml', 'pint.json', 'lint/', 'tests/Structure/', 'template/phpunit.xml', 'template/tests/Lint/'];

// The user's start: 开始 (also 开始吧, 好开始, 好的开始, ok开始) or start, with any spaces and punctuation.
const START = '/^\s*(?:好的?|ok|okay)?\s*(?:开始|start)\s*(?:吧)?[\s\p{P}]*$/iu';

// A plan that syncs a template release into a project names the template's
// commit: a file that is as it is there is not reviewed.
const TEMPLATE_COMMIT = '/^\s*(?:[-*+]\s+)?Template:\s*`?([0-9a-f]{7,40})`?/mi';

// At this many existing-debt items the report brings a draft cleanup plan.
const DEBT_LIMIT = 10;

// A review pack holds at most this many rules.
const PACK_SIZE = 10;

const JSON_OUTPUT = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

ini_set('display_errors', 'stderr');

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $command = $argv[1] ?? 'status';
    $argument = $argv[2] ?? '';

    try {
        echo workflow($command, $argument);
    } catch (Throwable $error) {
        if ($command === 'hook') {
            echo json(hookFailure($argument, $error));
            exit(0);
        }

        fwrite(STDERR, ($error instanceof Refused ? '' : 'The workflow tool could not run: ').failureText($error).PHP_EOL);
        exit(1);
    }
}

/** A step that cannot be taken now; its message says why and what to do. */
final class Refused extends RuntimeException {}

function workflow(string $command, string $argument): string
{
    // Claude Code sends the hook's input as JSON on stdin; the other commands read nothing.
    $input = $command === 'hook' ? hookInput() : [];

    if (isset($input['cwd']) && ! (is_string($input['cwd']) && is_dir($input['cwd']) && chdir($input['cwd']))) {
        throw new RuntimeException('the hook input names no folder to work in');
    }

    // Outside a git repository, or before its first commit, there is nothing to
    // keep; any other answer git refuses is an error the user is told.
    [$exit, $top, $error] = gitRun(['rev-parse', '--show-toplevel']);

    if ($exit !== 0) {
        return str_contains($error, 'not a git repository') ? '' : throw new RuntimeException("git rev-parse failed: {$error}");
    }

    [$exit, , $error] = gitRun(['rev-parse', '--verify', '--quiet', 'HEAD']);

    if ($exit === 1 && $error === '') {
        return '';
    }

    if ($exit !== 0) {
        throw new RuntimeException("git rev-parse HEAD failed: {$error}");
    }

    if (! chdir($top)) {
        throw new RuntimeException("cannot work in {$top}");
    }

    $state = new State(mustGit(['rev-parse', '--path-format=absolute', '--git-path', 'ezdev']));

    return match ($command) {
        'status' => statusLine($state)."\n",
        'review' => reviewRound($state),
        'report' => report($state),
        'cleanup' => cleanupDraft(),
        'upgrade' => upgradeDraft(),
        'hook' => hook($state, $argument, $input),
        'revoke' => $state->revoke(),
        'fingerprint' => fingerprint()."\n",
        'passed' => $state->passed($argument),
        default => throw new RuntimeException("unknown command {$command}"),
    };
}

/**
 * @return array<string, mixed>
 */
function hookInput(): array
{
    $text = stream_get_contents(STDIN);
    $input = $text === false ? throw new RuntimeException('cannot read the hook input on stdin') : json_decode($text, true, flags: JSON_THROW_ON_ERROR);

    return is_array($input) ? $input : throw new RuntimeException('the hook input is not a JSON object');
}

/**
 * A hook's answer when it could not do its job: a refusal blocks what it is
 * about (a 开始, a file write); any other error is told to the user and blocks
 * nothing, so a broken tool neither traps the AI nor goes unnoticed.
 *
 * @return array<string, mixed>
 */
function hookFailure(string $event, Throwable $error): array
{
    $message = failureText($error);

    if (! $error instanceof Refused) {
        return ['systemMessage' => "The workflow tool could not run: {$message}"];
    }

    return match ($event) {
        'user-prompt' => ['decision' => 'block', 'reason' => $message],
        'pre-tool' => ['hookSpecificOutput' => ['hookEventName' => 'PreToolUse', 'permissionDecision' => 'deny', 'permissionDecisionReason' => $message]],
        'session-start' => ['hookSpecificOutput' => ['hookEventName' => 'SessionStart', 'additionalContext' => "Workflow: {$message}"], 'systemMessage' => $message],
        default => ['systemMessage' => $message],
    };
}

/**
 * An error and every error before it that it replaced (an error in a `finally`
 * keeps the pending one as its previous), so none goes unseen.
 */
function failureText(Throwable $error): string
{
    $messages = [];

    for ($current = $error; $current !== null; $current = $current->getPrevious()) {
        $messages[] = $current->getMessage();
    }

    return implode('; before that: ', $messages);
}

// ---------------------------------------------------------------- the plan

/**
 * The plan in docs/exec-plans/active/, or null. Two plans in one folder are refused.
 */
function activePlan(): ?Plan
{
    $names = is_dir(ACTIVE) ? (scandir(ACTIVE) ?: throw new RuntimeException('cannot list '.ACTIVE)) : [];
    $plans = array_values(array_filter($names, fn (string $name): bool => str_ends_with($name, '.md')));

    if (count($plans) > 1) {
        throw new Refused(ACTIVE.' holds '.count($plans).' plans ('.implode(', ', $plans).'): one plan per folder. Move the others to a worktree of their own (scripts/worktree-up <name>).');
    }

    return $plans === [] ? null : new Plan(ACTIVE.$plans[0]);
}

final class Plan
{
    public readonly string $text;

    public readonly string $slug;

    public function __construct(public readonly string $path)
    {
        $this->text = readText($path);
        $this->slug = pathinfo($path, PATHINFO_FILENAME);
    }

    /** The plan as the user approves it: all of it but what waits for the user. */
    public function approvedHash(): string
    {
        return sha1(trim(preg_replace('/^##[ \t]+Waits for you[ \t]*$.*?(?=^##[ \t]|\z)/msi', '', $this->text) ?? throw new RuntimeException("cannot read the sections of {$this->path}")));
    }

    /**
     * What the plan's format misses (docs/PLANS.md), one item each.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        $problems = [];

        foreach (['Purpose', 'Questions', 'Decisions', 'Screens'] as $section) {
            if ($this->section($section) === null) {
                $problems[] = "it has no section ## {$section}";
            }
        }

        if ($this->decisions() === []) {
            $problems[] = 'it has no decision: write each as a line "D1 <decision>" under ## Decisions';
        }

        return $problems;
    }

    /**
     * The questions whose block has no "Answer:" with text after it.
     *
     * @return list<string>
     */
    public function openQuestions(): array
    {
        return array_keys(array_filter($this->items('Questions', 'Q'), fn (string $block): bool => preg_match('/^[ \t]*(?:[-*][ \t]+)?\**Answer(?::\**|\**:)[ \t]*[^\s*]/mi', $block) !== 1));
    }

    /** @return array<string, string> D1 => its text */
    public function decisions(): array
    {
        return $this->items('Decisions', 'D');
    }

    /** @return array<string, string> S1 => its text */
    public function screens(): array
    {
        return $this->items('Screens', 'S');
    }

    /**
     * The decisions that say "(no test: <reason>)": the reviewers check them.
     *
     * @return array<string, string>
     */
    public function untested(): array
    {
        return array_filter($this->decisions(), fn (string $text): bool => preg_match('/\(no test:\s*[^)\s][^)]*\)/i', $text) === 1);
    }

    /** A section's text, without its heading; null when the plan has no such section. */
    public function section(string $title): ?string
    {
        return preg_match('/^##[ \t]+'.preg_quote($title, '/').'[ \t]*$(.*?)(?=^##[ \t]|\z)/msi', $this->text, $match) === 1 ? $match[1] : null;
    }

    /**
     * The numbered items of a section: a line that starts with the letter and a
     * number (D1, - D1, **D1**) opens one, which runs to the next.
     *
     * @return array<string, string>
     */
    private function items(string $section, string $letter): array
    {
        $opens = '^[ \t]*(?:[-*][ \t]+)?\**'.$letter.'\d+\b';
        preg_match_all("/{$opens}\**[:.]?[ \t]*(.*?)(?={$opens}|\z)/ms", $this->section($section) ?? '', $matches, PREG_SET_ORDER);
        $items = [];

        foreach ($matches as $match) {
            preg_match('/'.$letter.'\d+/', $match[0], $id);
            $items[$id[0]] = trim($match[1]);
        }

        return $items;
    }
}

// ---------------------------------------------------------------- the state

final class State
{
    /** @var array<string, mixed> */
    private array $data;

    public function __construct(public readonly string $dir)
    {
        if (! is_dir($dir) && ! mkdir($dir, 0777, true)) {
            throw new RuntimeException("cannot create {$dir}");
        }

        $file = $this->file('workflow.json');

        try {
            $data = is_file($file) ? json_decode(readText($file), true, flags: JSON_THROW_ON_ERROR) : [];
        } catch (JsonException $error) {
            throw new RuntimeException("the state file {$file} cannot be read: {$error->getMessage()}", previous: $error);
        }

        $this->data = is_array($data) ? $data : throw new RuntimeException("the state file {$file} holds no state");
    }

    public function file(string $name): string
    {
        return "{$this->dir}/{$name}";
    }

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        $this->save();
    }

    /**
     * The state of one plan: a new plan starts over; only the fingerprint of
     * the last report stays.
     */
    public function forPlan(Plan $plan): void
    {
        if ($this->get('plan') !== $plan->path) {
            $this->data = ['plan' => $plan->path, 'reported' => $this->get('reported')];
            $this->save();
        }
    }

    /** The plan is reported: its state goes, the fingerprint of the code it left stays. */
    public function finish(string $fingerprint): void
    {
        $this->data = ['plan' => null, 'reported' => $fingerprint];
        $this->save();
    }

    public function isApproved(Plan $plan): bool
    {
        $approval = $this->get('approval');

        return $this->get('plan') === $plan->path && is_array($approval) && ($approval['hash'] ?? null) === $plan->approvedHash();
    }

    /** The commit the approved plan's change is compared with. */
    public function base(): string
    {
        $approval = $this->get('approval');

        return is_array($approval) && is_string($approval['base'] ?? null) ? $approval['base'] : throw new RuntimeException('the plan has no approval');
    }

    /** A check starts: until it passes, no earlier pass counts. */
    public function revoke(): string
    {
        removeFile($this->file('passed'));

        return '';
    }

    /**
     * A check passed on the code whose fingerprint it took when it started:
     * record it, unless the code changed while the check ran.
     */
    public function passed(string $started): string
    {
        if (preg_match('/^[0-9a-f]{40}$/', $started) !== 1) {
            throw new RuntimeException('the check did not take the fingerprint of the code it started on.');
        }

        if ($started !== fingerprint()) {
            throw new RuntimeException('the code changed while the check ran. Run it again.');
        }

        writeFile($this->file('passed'), "{$started}\n");
        $plan = $this->get('plan');

        if (is_string($plan) && is_file($plan) && $this->isApproved(new Plan($plan))) {
            $this->set('checkedSinceApproval', true);
        }

        return '';
    }

    public function passedOn(): ?string
    {
        return is_file($this->file('passed')) ? trim(readText($this->file('passed'))) : null;
    }

    private function save(): void
    {
        writeFile($this->file('workflow.json'), json_encode($this->data, JSON_OUTPUT | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    }
}

/**
 * Where the plan is: the last step it reached, and the next step.
 *
 * @return array{string, string}
 */
function step(State $state): array
{
    $plan = activePlan();

    if ($plan === null) {
        return $state->get('reported') === fingerprint()
            ? ['reported', 'The last plan is reported. A new change needs a new plan in '.ACTIVE.' (docs/PLANS.md).']
            : ['none', 'No plan is open. Before any code changes, write the plan in '.ACTIVE.'<name>.md (docs/PLANS.md), with your questions and recommendations in it, and give it to the user.'];
    }

    $state->forPlan($plan);

    if (! $state->isApproved($plan)) {
        $problems = $plan->problems();
        $open = $plan->openQuestions();

        return ['draft', match (true) {
            $problems !== [] => "Plan {$plan->path}: ".implode('; ', $problems).' (docs/PLANS.md).',
            $open !== [] => "Plan {$plan->path} waits for the answers to ".implode(', ', $open).'. Ask the user in one message, write each answer under its question, and wait for 开始.',
            default => "Plan {$plan->path} is ready. Give it to the user; code changes only after the user says 开始.",
        }];
    }

    $missing = missingTests($plan);

    if ($missing !== []) {
        $first = "{$plan->slug}.{$missing[0]}";

        return ['approved', 'Build the plan. '.implode(', ', $missing).' '.(count($missing) === 1 ? 'needs' : 'each need')." a test that names it ({$first}), or the decision says \"(no test: <reason>)\". While you build, run only the test you are on: php artisan test --compact --group={$first}, or npx vp test run -t {$first}. Then run composer check."];
    }

    if ($state->get('checkedSinceApproval') !== true || $state->passedOn() !== fingerprint()) {
        return ['built', 'Run composer check and fix every failure.'];
    }

    $stray = strayBaselines($state, $plan);

    if ($stray !== []) {
        return ['checked', strayMessage($stray)];
    }

    if (! isReviewed($state, $plan)) {
        $round = $state->get('round');
        $waiting = is_array($round) && $round['fingerprint'] === fingerprint() ? missingResults($state, $round) : [];

        return ['checked', $waiting !== []
            ? "Wait for the reviewer runs of round {$round['n']} (".implode(', ', $waiting).'); then fix every finding, run composer check, and run php scripts/workflow.php review.'
            : 'Run php scripts/workflow.php review and start the reviewer runs it prints.'];
    }

    return ['reviewed', 'Write what waits for the user under ## Waits for you in the plan ("Nothing." when nothing does), then run php scripts/workflow.php report and give the user the report in their language.'];
}

function statusLine(State $state): string
{
    [$name, $next] = step($state);

    return in_array($name, ['none', 'reported'], true) ? $next : "At step {$name}. Next: {$next}";
}

/**
 * The screenshot baselines the change touched that belong to no screen of the
 * plan. A baseline belongs to a screen when its name holds the screen's test
 * name (<plan>.S1) or the page the screen names ("S1 <page>: ..."), as the
 * browser tour names it; Pest writes both with "_" for every other character.
 *
 * @return list<string>
 */
function strayBaselines(State $state, Plan $plan): array
{
    $names = [];

    foreach ($plan->screens() as $id => $text) {
        $names[] = baselineName("{$plan->slug}.{$id}");

        if (preg_match('/^([^:\n]+):/', $text, $page) === 1) {
            $names[] = baselineName(trim($page[1]));
        }
    }

    return array_values(array_filter(changedSince($state->base()), function (string $path) use ($names): bool {
        if (! str_starts_with($path, BASELINES)) {
            return false;
        }

        $file = '_'.strtolower(basename($path)).'_';

        foreach ($names as $name) {
            if (str_contains($file, "_{$name}_")) {
                return false;
            }
        }

        return true;
    }));
}

function baselineName(string $text): string
{
    return trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $text) ?? ''), '_');
}

/**
 * @param  list<string>  $stray
 */
function strayMessage(array $stray): string
{
    return 'The screenshot baselines of pages the plan does not list changed: '.summary($stray).'. Put them back as they were (git checkout -- <file>; remove a new one) and find what changed those pages; if the change is wanted, the page goes under ## Screens, which the user approves with 开始.';
}

/**
 * The decisions and screens no test names yet: a PHP test in the group
 * <plan>.D1, or a JavaScript test whose name holds <plan>.D1.
 *
 * @return list<string>
 */
function missingTests(Plan $plan): array
{
    $tests = testText();
    $missing = [];

    foreach ([...array_keys(array_diff_key($plan->decisions(), $plan->untested())), ...array_keys($plan->screens())] as $id) {
        $name = preg_quote("{$plan->slug}.{$id}", '/');

        if (preg_match("/->group\([^)]*['\"]{$name}['\"]|\b(?:it|test|describe)(?:\.\w+)?\(\s*['\"`][^'\"`]*(?<![\w.-]){$name}(?![\w.])/", $tests) !== 1) {
            $missing[] = $id;
        }
    }

    return $missing;
}

/** The text of every test file, PHP and TypeScript. */
function testText(): string
{
    $text = '';

    foreach (['tests', 'template/tests'] as $folder) {
        if (! is_dir($folder)) {
            continue;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file instanceof SplFileInfo && preg_match('/\.(php|ts|tsx)$/', $file->getFilename()) === 1) {
                $text .= readText($file->getPathname())."\n";
            }
        }
    }

    return $text;
}

// ---------------------------------------------------------------- hooks

/**
 * @param  array<string, mixed>  $input
 */
function hook(State $state, string $event, array $input): string
{
    return match ($event) {
        'session-start' => json(['hookSpecificOutput' => ['hookEventName' => 'SessionStart', 'additionalContext' => 'Workflow: '.statusLine($state)]]),
        'user-prompt' => userPrompt($state, $input),
        'pre-tool' => preTool($state, $input),
        'post-tool' => postTool($state, $input),
        'stop' => stop($state, $input),
        'subagent-stop' => subagentStop($state, $input),
        default => throw new RuntimeException("unknown hook {$event}"),
    };
}

/**
 * The user's message. 开始 approves the plan as it is now. With a question open
 * it is held back (hookFailure) and the user is told which.
 *
 * @param  array<string, mixed>  $input
 */
function userPrompt(State $state, array $input): string
{
    $prompt = is_string($input['prompt'] ?? null) ? $input['prompt'] : throw new RuntimeException('the hook input holds no prompt');

    if (preg_match(START, $prompt) !== 1) {
        return '';
    }

    $plan = activePlan() ?? throw new Refused('There is no plan to start: '.ACTIVE.' is empty.');
    $state->forPlan($plan);

    if ($state->isApproved($plan)) {
        return '';
    }

    $problems = $plan->problems();

    if ($problems !== []) {
        throw new Refused("The plan cannot start yet ({$plan->path}): ".implode('; ', $problems).'.');
    }

    $open = $plan->openQuestions();

    if ($open !== []) {
        throw new Refused('The plan cannot start yet: '.implode(', ', $open).' '.(count($open) === 1 ? 'has' : 'have').' no answer. Answer '.(count($open) === 1 ? 'it' : 'them').' first.');
    }

    // A plan approved again after an edit keeps the commit its change started from.
    $previous = $state->get('approval');
    $state->set('approval', ['hash' => $plan->approvedHash(), 'base' => is_array($previous) ? $previous['base'] : mustGit(['rev-parse', 'HEAD'])]);
    $state->set('checkedSinceApproval', false);
    $state->set('round', null);
    $state->set('clean', []);

    return json(['hookSpecificOutput' => ['hookEventName' => 'UserPromptSubmit', 'additionalContext' => "The user approved plan {$plan->path}. ".step($state)[1]]]);
}

/**
 * Before a tool runs. Before the approval no file of code is written. A
 * command cannot be judged before it runs, so the files as they are now are
 * kept for postTool().
 *
 * @param  array<string, mixed>  $input
 */
function preTool(State $state, array $input): string
{
    // Two plans in one folder: commands and docs stay open, so the AI can move one away.
    try {
        $plan = activePlan();
        $refusal = null;
    } catch (Refused $refused) {
        $plan = null;
        $refusal = $refused->getMessage();
    }

    if ($plan !== null) {
        $state->forPlan($plan);

        if ($state->isApproved($plan)) {
            return '';
        }
    }

    if (($input['tool_name'] ?? '') === 'Bash') {
        writeFile(commandFile($state, $input), json_encode(fingerprintFiles(), JSON_OUTPUT | JSON_THROW_ON_ERROR));

        return '';
    }

    $tool = is_array($input['tool_input'] ?? null) ? $input['tool_input'] : [];
    $path = projectPath((string) ($tool['file_path'] ?? $tool['notebook_path'] ?? ''));

    if ($path === null || ! isCodePath($path)) {
        return '';
    }

    throw new Refused($refusal ?? ($plan === null
        ? "No plan is approved, so {$path} cannot change yet. Write the plan in ".ACTIVE.' (docs/PLANS.md) and wait for the user\'s 开始.'
        : "Plan {$plan->path} is not approved, so {$path} cannot change yet. ".step($state)[1].' An edit of an approved plan voids its approval.'));
}

/**
 * After a command: before the approval, code it changed is told to be put back.
 *
 * @param  array<string, mixed>  $input
 */
function postTool(State $state, array $input): string
{
    $file = commandFile($state, $input);

    if (($input['tool_name'] ?? '') !== 'Bash' || ! is_file($file)) {
        return '';
    }

    $before = json_decode(readText($file), true, flags: JSON_THROW_ON_ERROR);
    removeFile($file);
    $after = fingerprintFiles();
    $changed = array_keys(array_diff_assoc($after, is_array($before) ? $before : []) + array_diff_key(is_array($before) ? $before : [], $after));
    $code = array_values(array_filter($changed, 'isCodePath'));

    return $code === [] ? '' : json([
        'decision' => 'block',
        'reason' => 'That command changed code before the plan was approved: '.summary($code).'. Put these files back as they were (git checkout -- <file>; remove a new file), then go on with the plan.',
    ]);
}

/**
 * The file that keeps the files as they were before one command.
 *
 * @param  array<string, mixed>  $input
 */
function commandFile(State $state, array $input): string
{
    $id = is_string($input['tool_use_id'] ?? null) && $input['tool_use_id'] !== '' ? $input['tool_use_id'] : throw new RuntimeException('the hook input names no tool use');

    return $state->file('command-'.sha1($id).'.json');
}

/**
 * The AI is about to stop. When this conversation changed code and a step is
 * missing, it is sent back once; right after a send-back the user gets one
 * line instead. A changed check file gives the user one line, once per new set
 * of files.
 *
 * @param  array<string, mixed>  $input
 */
function stop(State $state, array $input): string
{
    $changed = changedInConversation(conversationStart($input['transcript_path'] ?? null));
    $code = array_values(array_filter($changed, 'isCodePath'));
    $output = [];
    $lines = [];

    // Code as the last report left it needs no step.
    if ($code !== [] && $state->get('reported') !== fingerprint()) {
        try {
            [$name, $next] = step($state);
            $why = in_array($name, ['none', 'draft'], true)
                ? 'This conversation changed code ('.summary($code).") with no approved plan. {$next}"
                : "This conversation changed code and the plan is at step {$name}. Next: {$next}";
        } catch (Refused $refused) {
            $why = $refused->getMessage();
        }

        if (($input['stop_hook_active'] ?? false) === true) {
            $lines[] = "The AI stopped before the work was done. {$why}";
        } else {
            $output = ['decision' => 'block', 'reason' => $why];
        }
    }

    $checks = array_values(array_filter($changed, 'isCheckFile'));
    $session = is_string($input['session_id'] ?? null) && $input['session_id'] !== ''
        ? $input['session_id']
        : throw new RuntimeException('the Stop input names no session');
    $told = $state->file('told-'.sha1($session).'.json');
    $before = is_file($told) ? json_decode(readText($told), true, flags: JSON_THROW_ON_ERROR) : [];

    if (! is_array($before)) {
        throw new RuntimeException("{$told} holds no list of check files");
    }

    if ($checks !== [] && array_diff($checks, $before) !== []) {
        $lines[] = 'This conversation changed a check: '.implode(', ', $checks).'.';
        writeFile($told, json_encode($checks, JSON_THROW_ON_ERROR));
    }

    if ($lines !== []) {
        $output['systemMessage'] = implode("\n", $lines);
    }

    return $output === [] ? '' : json($output);
}

/**
 * A reviewer finished. Its last line, RESULT pack=<id> findings=<n>, is its
 * result for a pack of the current round; the rule gaps and the older code it
 * lists go to the report. A run without that line counts as not run.
 *
 * @param  array<string, mixed>  $input
 */
function subagentStop(State $state, array $input): string
{
    $message = is_string($input['last_assistant_message'] ?? null) ? trim($input['last_assistant_message']) : '';
    $round = $state->get('round');

    if (preg_match('/(?:^|\n)RESULT pack=(\S+) findings=(\d+)\z/', $message, $match) !== 1 || ! is_array($round) || ! array_key_exists($match[1], $round['packs'])) {
        return json(['systemMessage' => 'A reviewer ended without a RESULT line for a pack of the current review round: its run counts as not run.']);
    }

    writeFile(resultFile($state, $match[1]), json_encode([
        'findings' => (int) $match[2],
        'fingerprint' => fingerprint(),
        'gaps' => listUnder($message, 'Rule gaps'),
        'older' => listUnder($message, 'Older code'),
    ], JSON_OUTPUT | JSON_THROW_ON_ERROR));

    return '';
}

/**
 * The "- " lines under "<title>:" in a reviewer's answer.
 *
 * @return list<string>
 */
function listUnder(string $message, string $title): array
{
    if (preg_match('/^[ \t]*'.preg_quote($title, '/').':[ \t]*\n((?:[ \t]*[-*][ \t]+.+(?:\n|\z))+)/mi', $message, $match) !== 1) {
        return [];
    }

    $lines = array_map(fn (string $line): string => trim(preg_replace('/^[ \t]*[-*][ \t]+/', '', $line) ?? ''), explode("\n", $match[1]));

    return array_values(array_filter($lines, fn (string $line): bool => $line !== '' && preg_match('/^none\.?$/i', $line) !== 1));
}

// ---------------------------------------------------------------- review

/**
 * The cards (.claude/rules/skeleton/): each card's paths and its reviewer
 * rules, its own and the ones it lists under "Also applies here".
 *
 * @return array<string, array{paths: list<string>, rules: list<string>}>
 */
function cards(): array
{
    $cards = [];

    foreach (glob(CARDS.'*.md') ?: [] as $file) {
        $text = readText($file);

        if (preg_match('/\A---\n(.*?)\n---\n/s', $text, $front) !== 1) {
            throw new RuntimeException("{$file} has no paths");
        }

        preg_match_all('/^[ \t]+-[ \t]+"([^"]+)"$/m', $front[1], $paths);
        preg_match_all('/^- \*\*([A-Z>]+-\d+)\*\* .*`reviewer`/m', $text, $rules);
        $cards[basename($file, '.md')] = ['paths' => $paths[1], 'rules' => $rules[1]];
    }

    return $cards !== [] ? $cards : throw new RuntimeException('there are no cards in '.CARDS);
}

/**
 * Every reviewer rule as its own card or the overview states it, with its Check line.
 *
 * @return array<string, string>
 */
function reviewerRules(): array
{
    $rules = [];

    foreach ([OVERVIEW, ...(glob(CARDS.'*.md') ?: [])] as $file) {
        preg_match_all('/^- \*\*([A-Z>]+-\d+)\*\* (?!\(from )(.*`reviewer`.*)\n[ \t]+- (Check: .*)$/m', readText($file), $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $rules[$match[1]] = "{$match[2]}\n{$match[3]}";
        }
    }

    return $rules;
}

/**
 * The cards a change to $path touches: every card whose paths hold it.
 *
 * @param  array<string, array{paths: list<string>, rules: list<string>}>  $cards
 * @return list<string>
 */
function cardsOf(string $path, array $cards): array
{
    return array_keys(array_filter($cards, function (array $card) use ($path): bool {
        foreach ($card['paths'] as $glob) {
            $pattern = strtr(preg_quote($glob, '#'), ['\*\*' => '.*', '\*' => '[^/]*', '\?' => '[^/]']);

            if (preg_match("#^{$pattern}$#", $path) === 1) {
                return true;
            }
        }

        return false;
    }));
}

/**
 * Every code file the plan's change touched since the approval's commit, with
 * the hash of its content now ('deleted' for a deleted file). The template's
 * own tools are left out, and so is a file that is as it is in the template
 * commit a sync plan names.
 *
 * @return array<string, string>
 */
function planFiles(State $state, Plan $plan): array
{
    $template = preg_match(TEMPLATE_COMMIT, $plan->text, $match) === 1
        ? (gitAnswer(['rev-parse', '--verify', '--quiet', $match[1].'^{commit}']) ?? throw new RuntimeException("the plan's template commit {$match[1]} is not in this repository"))
        : null;
    $files = [];

    foreach (changedSince($state->base()) as $path) {
        if (! isCodePath($path) || array_filter(NOT_REVIEWED, fn (string $folder): bool => str_starts_with($path, $folder)) !== []) {
            continue;
        }

        $hash = contentHash($path);

        if ($template === null || (gitAnswer(['rev-parse', '--verify', '--quiet', "{$template}:{$path}"]) ?? 'deleted') !== $hash) {
            $files[$path] = $hash;
        }
    }

    ksort($files);

    return $files;
}

/**
 * The cards each file touches, and the global pack, which reads every file.
 *
 * @param  array<string, string>  $files
 * @param  array<string, array{paths: list<string>, rules: list<string>}>  $cards
 * @return array<string, list<string>> card => its files
 */
function touchedCards(array $files, array $cards): array
{
    $touched = [];

    foreach (array_keys($files) as $path) {
        foreach (cardsOf($path, $cards) as $card) {
            $touched[$card][] = $path;
        }

        $touched['global'][] = $path;
    }

    ksort($touched);

    return $touched;
}

/**
 * The cards whose review found nothing, each with the hashes of its files at
 * that review: the clean cards of earlier rounds, and of the latest round
 * when every one of its packs has a result.
 *
 * @return array<string, array<string, string>>
 */
function cleanCards(State $state): array
{
    $clean = $state->get('clean') ?? [];
    $round = $state->get('round');

    if (! is_array($round) || missingResults($state, $round) !== []) {
        return $clean;
    }

    $findings = [];

    foreach ($round['packs'] as $id => $cards) {
        $result = readResult($state, $id);

        foreach ($cards as $card) {
            $findings[$card] = ($findings[$card] ?? 0) + $result['findings'];
        }
    }

    foreach ($findings as $card => $count) {
        if ($count === 0) {
            $clean[$card] = array_intersect_key($round['files'], array_flip($round['touched'][$card]));
        } else {
            unset($clean[$card]);
        }
    }

    return $clean;
}

/**
 * Every card the plan's change touches, the global pack included, is clean,
 * and none of its files changed since its clean review.
 */
function isReviewed(State $state, Plan $plan): bool
{
    if (! is_array($state->get('round'))) {
        return false;
    }

    return openCards(planFiles($state, $plan), cleanCards($state), cards()) === [];
}

/**
 * The touched cards that are not clean for the files as they are now. A card
 * with no reviewer rule has nothing to review.
 *
 * @param  array<string, string>  $files
 * @param  array<string, array<string, string>>  $clean
 * @param  array<string, array{paths: list<string>, rules: list<string>}>  $cards
 * @return array<string, list<string>>
 */
function openCards(array $files, array $clean, array $cards): array
{
    return array_filter(touchedCards($files, $cards), function (array $paths, string $card) use ($files, $clean, $cards): bool {
        if ($card !== 'global' && $cards[$card]['rules'] === []) {
            return false;
        }

        foreach ($paths as $path) {
            if (($clean[$card][$path] ?? null) !== $files[$path]) {
                return true;
            }
        }

        return false;
    }, ARRAY_FILTER_USE_BOTH);
}

/**
 * Prepares the next review round: the diff of the plan's change and a copy of
 * the plan, and the packs for the cards that are not clean, at most PACK_SIZE
 * rules each, plus the global pack while it is not clean. Prints the runs to start.
 */
function reviewRound(State $state): string
{
    [$name, $next] = step($state);

    if ($name === 'reviewed') {
        return "The review is done: every card of the plan's change had a run with no finding. Next: {$next}\n";
    }

    if ($name !== 'checked') {
        throw new Refused("A review round needs a composer check that passed on the code as it is now. Next: {$next}");
    }

    $plan = activePlan() ?? throw new Refused('No plan is open.');
    $stray = strayBaselines($state, $plan);

    if ($stray !== []) {
        throw new Refused(strayMessage($stray));
    }

    $round = $state->get('round');

    // A round on the same code that still waits for runs is not started again.
    if (is_array($round) && $round['fingerprint'] === fingerprint() && missingResults($state, $round) !== []) {
        return "Round {$round['n']} still waits for these runs; start the ones that did not end with a RESULT line:\n".implode("\n", array_map(fn (string $id): string => 'reviewer-haiku: Pack: '.packFile($plan, $id), missingResults($state, $round)))."\n";
    }

    $cards = cards();
    $files = planFiles($state, $plan);
    $clean = cleanCards($state);
    $open = openCards($files, $clean, $cards);
    $all = reviewerRules();
    $n = (is_array($round) ? $round['n'] : 0) + 1;
    $diff = writeDiff($state, $plan, array_keys($files));
    $copy = REVIEW_FILES.$plan->slug.'/plan.md';
    writeFile($copy, $plan->text);

    // Each rule once, with the files of the open cards it is checked on.
    $rules = [];

    foreach (array_diff_key($open, ['global' => []]) as $card => $paths) {
        foreach ($cards[$card]['rules'] as $rule) {
            $rules[$rule]['cards'][] = $card;
            $rules[$rule]['files'] = array_values(array_unique([...($rules[$rule]['files'] ?? []), ...$paths]));
        }
    }

    ksort($rules, SORT_NATURAL);
    $packs = [];

    foreach (array_chunk($rules, PACK_SIZE, true) as $index => $chunk) {
        $id = "r{$n}-p".($index + 1);
        $text = '';

        foreach ($chunk as $rule => $where) {
            $text .= "\n## {$rule}\n".($all[$rule] ?? throw new RuntimeException("rule {$rule} has no card of its own"))."\nFiles: ".implode(', ', $where['files'])."\n";
        }

        $packs[$id] = ['cards' => array_values(array_unique(array_merge(...array_column($chunk, 'cards')))), 'text' => $text];
    }

    if (isset($open['global'])) {
        $text = '';

        foreach (globalRules($plan, $all) as $rule => $ruleText) {
            $text .= "\n## {$rule}\n{$ruleText}\nFiles: every file in the diff\n";
        }

        $packs["r{$n}-global"] = ['cards' => ['global'], 'text' => $text];
    }

    if ($packs === []) {
        $state->set('clean', $clean);
        $state->set('round', ['n' => $n, 'fingerprint' => fingerprint(), 'files' => $files, 'touched' => touchedCards($files, $cards), 'packs' => []]);

        return 'Nothing is left to review: no reviewed file changed since its clean run. Next: '.step($state)[1]."\n";
    }

    foreach ($packs as $id => $pack) {
        removeFile(resultFile($state, $id));
        writeFile(packFile($plan, $id), packText($id, $pack['text'], $copy, $diff));
    }

    $state->set('clean', $clean);
    $state->set('round', [
        'n' => $n,
        'fingerprint' => fingerprint(),
        'files' => $files,
        'touched' => touchedCards($files, $cards),
        'packs' => array_map(fn (array $pack): array => $pack['cards'], $packs),
    ]);

    return "Round {$n}: start these reviewer runs, all in one message, and wait for them:\n".implode("\n", array_map(fn (string $id): string => 'reviewer-haiku: Pack: '.packFile($plan, $id), array_keys($packs)))."\n";
}

/**
 * The global pack: the rules for every block and the workflow, and the
 * decisions the plan says no test can prove.
 *
 * @param  array<string, string>  $all
 * @return array<string, string>
 */
function globalRules(Plan $plan, array $all): array
{
    $rules = array_filter($all, fn (string $id): bool => str_starts_with($id, 'ALL-') || str_starts_with($id, 'FLOW-'), ARRAY_FILTER_USE_KEY);

    foreach ($plan->untested() as $id => $text) {
        $rules["{$plan->slug}.{$id}"] = "The plan's decision {$id}, which the plan says no test can prove: {$text}\nCheck: Look at: the code the decision is about. Break: the code does not do what the decision says. Cite: the line and the decision.";
    }

    return $rules;
}

function packFile(Plan $plan, string $id): string
{
    return REVIEW_FILES."{$plan->slug}/{$id}.md";
}

function resultFile(State $state, string $id): string
{
    return $state->file('result-'.preg_replace('/[^\w-]/', '_', $id).'.json');
}

/**
 * @return array{findings: int, fingerprint: string, gaps: list<string>, older: list<string>}
 */
function readResult(State $state, string $id): array
{
    $result = json_decode(readText(resultFile($state, $id)), true, flags: JSON_THROW_ON_ERROR);

    return is_array($result) && is_int($result['findings'] ?? null) ? $result : throw new RuntimeException("the result of pack {$id} cannot be read");
}

/**
 * The packs of a round that have no result made on the code the round reviewed.
 *
 * @param  array<string, mixed>  $round
 * @return list<string>
 */
function missingResults(State $state, array $round): array
{
    return array_values(array_filter(array_keys($round['packs']), fn (string $id): bool => ! is_file(resultFile($state, $id)) || readResult($state, $id)['fingerprint'] !== $round['fingerprint']));
}

function packText(string $id, string $rules, string $plan, string $diff): string
{
    return <<<MD
        # Review pack {$id}

        You review one change against the rules below and nothing else. You did not write it. Read with Read, Grep and Glob; change nothing.

        - The approved plan: {$plan}
        - The change, as a unified diff: {$diff}. A line you cite is a line of the new version of its file.
        - The project itself, for the code around a change.

        Check every rule below on the lines the diff adds or changes in the files the rule names, and against the plan. Each rule has a Check line: what to look at, what breaks the rule, what does not, and what to cite. Report a finding only when the change breaks one of these rules: not a rule that is not listed, not a style you would prefer, not a risk. A problem in old code the diff does not change is not a finding: list it under Older code.

        # Rules
        {$rules}
        # Answer

        One line per finding: `RULE-ID | path:line | the code, quoted short | why it breaks the rule, in one sentence`.

        Then, when there are any:

        Rule gaps:
        - a case in the changed code that no rule settles

        Older code:
        - path:line: a problem in code from before the plan

        The last line, exactly: `RESULT pack={$id} findings=<the number of findings>`

        MD;
}

/**
 * Writes the diff of $paths since the approval's commit, new files included.
 *
 * @param  list<string>  $paths
 */
function writeDiff(State $state, Plan $plan, array $paths): string
{
    $untracked = gitPaths(['ls-files', '--others', '--exclude-standard', '-z']);
    $tracked = array_values(array_diff($paths, $untracked));
    $diff = $tracked === [] ? '' : mustGit(['diff', '--no-renames', $state->base(), '--', ...$tracked], trim: false);

    foreach (array_intersect($paths, $untracked) as $path) {
        // git diff --no-index exits 1 when the files differ (an empty new file too) and 0 when they do not.
        [$exit, $output, $error] = gitRun(['diff', '--no-index', '--', '/dev/null', $path], trim: false);
        $diff .= in_array($exit, [0, 1], true) ? $output : throw new RuntimeException(trim("git diff failed for {$path}: {$error}", ' :'));
    }

    $file = REVIEW_FILES."{$plan->slug}/change.diff";
    writeFile($file, $diff);

    return $file;
}

// ---------------------------------------------------------------- report

/**
 * Writes the report from the plan, its tests, the check and the review, at the
 * end of the plan, and moves the plan out of active/. The older code the
 * reviewers found goes to the debt list; at DEBT_LIMIT items a draft cleanup
 * plan follows.
 */
function report(State $state): string
{
    [$name, $next] = step($state);

    if ($name !== 'reviewed') {
        throw new Refused("The report needs a plan reviewed on the code as it is now. Next: {$next}");
    }

    $plan = activePlan() ?? throw new Refused('No plan is open.');
    $waits = trim($plan->section('Waits for you') ?? '');

    if ($waits === '') {
        throw new Refused('Write what waits for the user under ## Waits for you in the plan ("Nothing." when nothing does) first.');
    }

    $gaps = [];
    $older = [];
    $round = $state->get('round');

    foreach (glob($state->file('result-*.json')) ?: [] as $file) {
        $result = json_decode(readText($file), true, flags: JSON_THROW_ON_ERROR);
        $gaps = [...$gaps, ...($result['gaps'] ?? [])];
        $older = [...$older, ...($result['older'] ?? [])];
    }

    $new = array_values(array_diff(array_unique($older), debtItems()));

    if ($new !== []) {
        $list = is_file(DEBT) ? rtrim(readText(DEBT))."\n" : "# Existing debt\n\n";
        writeFile(DEBT, $list.implode('', array_map(fn (string $item): string => "- {$item} (found while reviewing {$plan->slug})\n", $new)));
    }

    $count = count(debtItems());
    $folder = is_dir(TEMPLATE_PLANS) ? TEMPLATE_PLANS : COMPLETED;
    $shots = REPORT_SHOTS.$plan->slug.'/';
    $lines = ['## Report', '', '### Decisions', ''];

    foreach ($plan->decisions() as $id => $text) {
        $lines[] = "- {$id} {$text}\n  ".(array_key_exists($id, $plan->untested()) ? 'Checked by the reviewers.' : "Proved by the tests named {$plan->slug}.{$id}, which passed in composer check.");
    }

    if ($plan->screens() !== []) {
        $lines = [...$lines, '', '### Screens', ''];

        foreach ($plan->screens() as $id => $text) {
            $taken = [];

            foreach (glob(SCREENSHOTS."{$plan->slug}.{$id}*.png") ?: [] as $shot) {
                copyFile($shot, $shots.basename($shot));
                $taken[] = $shots.basename($shot);
            }

            $lines[] = "- {$id} {$text}\n  Proved by the browser test named {$plan->slug}.{$id}".($taken === [] ? '; it took no screenshot.' : '; screenshots: '.implode(', ', $taken).'.');
        }
    }

    $lines = [...$lines, '', '### Check and review', '',
        '- composer check passed on the code as it is now.',
        "- The review took {$round['n']} ".($round['n'] === 1 ? 'round' : 'rounds').'; at the end no card had a finding.',
        '', '### Rule gaps', '',
        ...(array_unique($gaps) === [] ? ['- None.'] : array_map(fn (string $gap): string => "- {$gap}", array_values(array_unique($gaps)))),
        '', '### Waits for you', '', $waits,
        '', '### Existing debt', '',
        "- {$count} ".($count === 1 ? 'item' : 'items').' in '.DEBT.($new === [] ? '.' : ', '.count($new).' of them found in this review.'),
    ];

    if ($count >= DEBT_LIMIT) {
        $lines[] = '- That is '.DEBT_LIMIT.' or more: a draft cleanup plan waits in '.ACTIVE.'cleanup.md for your answers and your 开始.';
    }

    $done = $folder.basename($plan->path);
    writeFile($done, rtrim($plan->text)."\n\n".implode("\n", $lines)."\n");
    removeFile($plan->path);
    removeFolder(REVIEW_FILES.$plan->slug);

    foreach ([...(glob($state->file('result-*.json')) ?: []), ...(glob($state->file('command-*.json')) ?: [])] as $file) {
        removeFile($file);
    }

    $state->finish(fingerprint());

    if ($count >= DEBT_LIMIT) {
        cleanupDraft();
    }

    return "The report is at the end of {$done}. Give it to the user in their language.\n";
}

/**
 * The items of the debt list: its "- " lines.
 *
 * @return list<string>
 */
function debtItems(): array
{
    if (! is_file(DEBT)) {
        return [];
    }

    preg_match_all('/^- (.+?)(?: \(found while reviewing [^)]+\))?$/m', readText(DEBT), $matches);

    return $matches[1];
}

// ---------------------------------------------------------------- cleanup and upgrade

/**
 * A draft cleanup plan: one question for each item of the debt list.
 */
function cleanupDraft(): string
{
    $questions = '';

    foreach (debtItems() as $index => $item) {
        $n = $index + 1;
        $questions .= "Q{$n} Fix this now? {$item}\nRecommended: <yes or later, and why>\nAnswer:\n\n";
    }

    noOpenPlan('cleanup');

    return writeDraft('cleanup', 'Fix the existing debt the user chooses, and nothing else. A fixed item leaves '.DEBT.'.', $questions, '');
}

/**
 * A draft upgrade plan: one question for each outdated package, each package
 * with security advisories, and each file a template release left with a
 * merge conflict.
 */
function upgradeDraft(): string
{
    noOpenPlan('upgrade');
    $questions = [];
    $outdated = jsonCommand(['composer', 'outdated', '--direct', '--format=json'], [0]);

    foreach ($outdated['installed'] ?? [] as $package) {
        $questions[] = "Upgrade {$package['name']} from {$package['version']} to {$package['latest']}?";
    }

    // npm outdated exits 1 when a package is outdated.
    foreach (jsonCommand(['npm', 'outdated', '--json'], [0, 1]) as $name => $package) {
        $questions[] = "Upgrade {$name} from ".($package['current'] ?? 'not installed')." to {$package['latest']}?";
    }

    // composer audit exits with a bit for advisories and one for abandoned packages.
    $audit = jsonCommand(['composer', 'audit', '--format=json'], [0, 1, 2, 3]);

    foreach ($audit['advisories'] ?? [] as $name => $advisories) {
        $questions[] = "Fix the security advisories of {$name}: ".implode('; ', array_map(fn (array $advisory): string => $advisory['title'] ?? $advisory['advisoryId'] ?? 'an advisory', $advisories)).'?';
    }

    // git grep exits 1 when no file holds a conflict.
    [$exit, $output, $error] = gitRun(['grep', '--untracked', '-l', '-z', '-F', '<<<<<<< before updating'], trim: false);

    if (! in_array($exit, [0, 1], true)) {
        throw new RuntimeException("git grep failed: {$error}");
    }

    foreach (array_filter(explode("\0", $output)) as $path) {
        $questions[] = "{$path} has a merge conflict from the template release: keep the project's lines, take the template's, or combine them?";
    }

    $text = '';

    foreach ($questions as $index => $question) {
        $text .= 'Q'.($index + 1)." {$question}\nRecommended: <your answer, and why>\nAnswer:\n\n";
    }

    $template = is_file('.copier-answers.yml') ? "Template: <the commit of the template release (git ls-remote <_src_path> <tag>)>\n" : '';

    return writeDraft('upgrade', 'Upgrade what the user chooses, and nothing else. A breaking change a package\'s upgrade notes name is a question of its own.', $text, $template);
}

/** A draft goes only into a folder with no open plan. */
function noOpenPlan(string $name): void
{
    $plan = activePlan();

    if ($plan !== null) {
        throw new Refused("{$plan->path} is open: finish it first, or write the {$name} plan in a worktree of its own (scripts/worktree-up {$name}).");
    }
}

function writeDraft(string $name, string $purpose, string $questions, string $decisions): string
{
    writeFile(ACTIVE."{$name}.md", '# '.ucfirst($name)."\n\n## Purpose\n\n{$purpose}\n\n## Questions\n\n".($questions === '' ? "None.\n\n" : $questions)."## Decisions\n\n{$decisions}D1 <one decision for each answer the user gave>\n\n## Screens\n\nNone.\n");

    return 'A draft '.$name.' plan is at '.ACTIVE."{$name}.md. Write your recommendations, ask the user its questions in one message, and wait for 开始.\n";
}

/**
 * Runs a command that answers in JSON and returns its answer.
 *
 * @param  list<string>  $command
 * @param  list<int>  $exits  the exit codes that mean it answered
 * @return array<mixed>
 */
function jsonCommand(array $command, array $exits): array
{
    [$exit, $output, $error] = run($command);
    $answer = in_array($exit, $exits, true) ? json_decode($output === '' ? '{}' : $output, true) : null;

    return is_array($answer) ? $answer : throw new RuntimeException(implode(' ', $command)." failed: {$error}");
}

// ---------------------------------------------------------------- the conversation

/**
 * When the conversation started: the first time stamp of its transcript, or
 * null when Claude Code names no transcript. A named transcript that is not
 * there, cannot be read or has no time stamp is an error.
 */
function conversationStart(mixed $transcript): ?float
{
    if ($transcript === null) {
        return null;
    }

    if (! is_string($transcript) || ! is_file($transcript)) {
        throw new RuntimeException('the transcript the Stop input names is not there');
    }

    $file = fopen($transcript, 'r') ?: throw new RuntimeException("cannot read the transcript {$transcript}");

    try {
        while (($line = fgets($file)) !== false) {
            if (trim($line) === '') {
                continue;
            }

            // An entry without a time stamp is skipped; a broken line or time is an error.
            $entry = json_decode($line, true, flags: JSON_THROW_ON_ERROR);

            if (! is_array($entry) || ! array_key_exists('timestamp', $entry)) {
                continue;
            }

            $time = is_string($entry['timestamp']) ? strtotime($entry['timestamp']) : false;

            return $time === false ? throw new RuntimeException("the transcript has a time stamp that cannot be read: {$transcript}") : (float) $time;
        }

        throw new RuntimeException(feof($file) ? "the transcript has no time stamp: {$transcript}" : "cannot read the transcript {$transcript}");
    } finally {
        fclose($file);
    }
}

/**
 * The files this conversation changed: touched by a commit made since $start,
 * or different from HEAD and written since $start (a deleted file: the nearest
 * folder above it that is still there, which the deletion wrote). Without a
 * start every file that differs from HEAD counts.
 *
 * @return list<string>
 */
function changedInConversation(?float $start): array
{
    $working = array_values(array_filter(changedSince('HEAD'), fn (string $path): bool => $start === null || modified(is_file($path) ? $path : nearestFolder($path)) >= floor($start)));

    if ($start === null) {
        return $working;
    }

    $committed = gitPaths(['log', '--since=@'.(int) floor($start), '--format=', '--name-only', '--no-renames', '-z']);

    return array_values(array_unique(array_filter([...$committed, ...$working])));
}

function modified(string $path): int
{
    clearstatcache(true, $path);
    $time = filemtime($path);

    return $time === false ? throw new RuntimeException("cannot read when {$path} was written") : $time;
}

function nearestFolder(string $path): string
{
    do {
        $path = dirname($path);
    } while ($path !== '.' && ! is_dir($path));

    return $path;
}

// ---------------------------------------------------------------- files

/**
 * Code is every file outside docs/ and *.md.
 */
function isCodePath(string $path): bool
{
    return ! str_ends_with($path, '.md') && ! str_starts_with($path, 'docs/');
}

function isCheckFile(string $path): bool
{
    foreach (CHECK_FILES as $check) {
        if ($path === $check || (str_ends_with($check, '/') && str_starts_with($path, $check))) {
            return true;
        }
    }

    return false;
}

/**
 * A path from the project's root, as git names it; null for a path outside the project.
 */
function projectPath(string $path): ?string
{
    if ($path === '') {
        return null;
    }

    $root = getcwd() ?: throw new RuntimeException('cannot read the working folder');
    $absolute = str_starts_with($path, '/') ? $path : "{$root}/{$path}";

    // The file may not exist yet: the nearest folder above it that does gives the real path.
    $folder = dirname($absolute);
    $rest = basename($absolute);

    while (! is_dir($folder) && $folder !== dirname($folder)) {
        $rest = basename($folder)."/{$rest}";
        $folder = dirname($folder);
    }

    $real = realpath($folder);

    if ($real === false) {
        throw new RuntimeException("cannot resolve {$folder}");
    }

    $full = rtrim($real, '/')."/{$rest}";

    return str_starts_with($full, "{$root}/") ? substr($full, strlen($root) + 1) : null;
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

/** @param array<string, mixed> $value */
function json(array $value): string
{
    return json_encode($value, JSON_OUTPUT | JSON_THROW_ON_ERROR);
}

function readText(string $path): string
{
    $text = @file_get_contents($path);

    return $text === false ? throw new RuntimeException("cannot read {$path}") : $text;
}

/** Writes a file, making its folder; a file that cannot be written is an error, never a silent pass. */
function writeFile(string $file, string $content): void
{
    if (! is_dir(dirname($file)) && ! @mkdir(dirname($file), 0777, true)) {
        throw new RuntimeException('cannot create '.dirname($file));
    }

    if (@file_put_contents($file, $content) === false) {
        throw new RuntimeException(error_get_last()['message'] ?? "cannot write {$file}");
    }
}

function copyFile(string $from, string $to): void
{
    writeFile($to, readText($from));
}

function removeFile(string $path): void
{
    if (is_file($path) && ! @unlink($path)) {
        throw new RuntimeException("cannot remove {$path}");
    }
}

function removeFolder(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    foreach (array_diff(scandir($path) ?: throw new RuntimeException("cannot list {$path}"), ['.', '..']) as $name) {
        is_dir("{$path}/{$name}") && ! is_link("{$path}/{$name}") ? removeFolder("{$path}/{$name}") : removeFile("{$path}/{$name}");
    }

    if (! @rmdir($path)) {
        throw new RuntimeException("cannot remove {$path}");
    }
}

// ---------------------------------------------------------------- git

/**
 * Runs a command and returns its exit code, its output and its error. The
 * error goes to a file, so a long error cannot fill a pipe while the output is
 * read; messages are in English, whatever the user's language.
 *
 * @param  list<string>  $command
 * @param  array<string, string>  $env
 * @return array{int, string, string}
 */
function run(array $command, array $env = [], bool $trim = true): array
{
    $errors = tempnam(sys_get_temp_dir(), 'ezdev-run') ?: throw new RuntimeException('no temporary file for '.$command[0]);
    $failed = '';

    try {
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['file', $errors, 'w']], $pipes, null, [...getenv(), ...$env, 'LC_ALL' => 'C']);

        if (! is_resource($process)) {
            throw new RuntimeException("{$command[0]} could not start");
        }

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $exit = proc_close($process);

        if ($output === false) {
            throw new RuntimeException("cannot read the answer of {$command[0]}");
        }

        $answer = [$exit, $trim ? trim($output) : $output, trim(readText($errors))];
        // When removing the file fails below, the command's own failure is still told.
        $failed = $exit === 0 ? '' : "{$command[0]} failed: {$answer[2]}";

        return $answer;
    } finally {
        try {
            removeFile($errors);
        } catch (RuntimeException $error) {
            throw $failed === '' ? $error : new RuntimeException("{$error->getMessage()}; before that: {$failed}", previous: $error->getPrevious());
        }
    }
}

/**
 * @param  list<string>  $args
 * @param  array<string, string>  $env
 * @return array{int, string, string}
 */
function gitRun(array $args, array $env = [], bool $trim = true): array
{
    return run(['git', '-c', 'core.quotePath=false', ...$args], $env, $trim);
}

/**
 * Runs git where a failure must not pass as an empty answer.
 *
 * @param  list<string>  $args
 * @param  array<string, string>  $env
 */
function mustGit(array $args, array $env = [], bool $trim = true): string
{
    [$exit, $output, $error] = gitRun($args, $env, $trim);

    return $exit === 0 ? $output : throw new RuntimeException(trim("git {$args[0]} failed: {$error}", ' :'));
}

/**
 * The entries of a git command run with `-z`: separated by NUL and never
 * quoted, so a name holding a quote, a backslash or a newline is read as it is.
 *
 * @param  list<string>  $args
 * @param  array<string, string>  $env
 * @return list<string>
 */
function gitPaths(array $args, array $env = []): array
{
    return array_values(array_filter(explode("\0", mustGit($args, $env, false)), fn (string $entry): bool => $entry !== ''));
}

/**
 * Asks `git rev-parse --verify --quiet`: its answer, or null when the object
 * does not exist (exit 1, no message). Any other failure is an error.
 *
 * @param  list<string>  $args
 */
function gitAnswer(array $args): ?string
{
    [$exit, $output, $error] = gitRun($args);

    return match (true) {
        $exit === 0 => $output,
        $exit === 1 && $error === '' => null,
        default => throw new RuntimeException("git {$args[0]} failed: {$error}"),
    };
}

/**
 * Files that differ from $base in the working tree, untracked files included.
 *
 * @return list<string>
 */
function changedSince(string $base): array
{
    return array_values(array_unique([...gitPaths(['diff', '--name-only', '--no-renames', '-z', $base]), ...gitPaths(['ls-files', '--others', '--exclude-standard', '-z'])]));
}

function contentHash(string $path): string
{
    return is_file($path) ? mustGit(['hash-object', '--', $path]) : 'deleted';
}

/**
 * Every file as it is in the working tree now, with its mode and blob hash:
 * HEAD's files with every change and every untracked file added, in an index
 * of its own.
 *
 * @return array<string, string>
 */
function fingerprintFiles(): array
{
    $index = tempnam(sys_get_temp_dir(), 'ezdev-index') ?: throw new RuntimeException('no temporary file for the fingerprint index');

    // git makes the index itself, so the empty file goes first.
    removeFile($index);
    $env = ['GIT_INDEX_FILE' => $index];

    try {
        mustGit(['read-tree', 'HEAD'], $env);
        mustGit(['add', '-A'], $env);
        $lines = gitPaths(['ls-files', '-s', '-z'], $env);
    } finally {
        removeFile($index);
    }

    $files = [];

    foreach ($lines as $line) {
        [$meta, $path] = str_contains($line, "\t") ? explode("\t", $line, 2) : throw new RuntimeException("git ls-files gave a line without a path: {$line}");
        $files[$path] = $meta;
    }

    return $files;
}

/**
 * A hash of every code file (isCodePath), as it is in the working tree now.
 */
function fingerprint(): string
{
    $lines = [];

    foreach (array_filter(fingerprintFiles(), 'isCodePath', ARRAY_FILTER_USE_KEY) as $path => $meta) {
        $lines[] = "{$meta}\t{$path}";
    }

    return sha1(implode("\n", $lines));
}
