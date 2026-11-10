<?php

/*
| The finish check: principle 9 in docs/golden-principles.md.
|
| Claude Code (.claude/settings.json) and Codex (.codex/hooks.json) run this
| script when a conversation starts and each time the AI is about to stop;
| composer check runs it first and last:
|
|   php scripts/hooks/finish-check.php start      remember where this conversation started
|   php scripts/hooks/finish-check.php stop       compare with the start; send back what is missing
|   php scripts/hooks/finish-check.php checking   composer check starts: remember the code it checks
|   php scripts/hooks/finish-check.php checked    composer check passed: record that code as checked
|   milestone-checking / milestone-checked       separate partial validation, never a full pass
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
    'AGENTS.md', 'CLAUDE.md', 'ARCHITECTURE.md', 'DESIGN.md', 'docs/golden-principles.md', 'docs/rules/', 'docs/prompts/',
    'docs/workflow.md', 'docs/PLANS.md', 'docs/reference/',
    'phpstan.neon', 'vite.config.ts', 'tsconfig.json', 'pint.json', 'phpunit.xml', '.npmrc', 'artisan', '.gitignore',
    'lint/', 'tests/Structure/', 'tests/Support/', 'tests/Unit/Hooks/', 'scripts/',
    'tests/bootstrap.php', 'tests/Pest.php', 'tests/TestCase.php', 'tests/js/setup.ts',
    '.claude/', '.codex/', '.agents/', '.github/',
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

const JSON_OUTPUT = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

require_once __DIR__.'/../check-run.php';

ini_set('display_errors', 'stderr');

$mode = $argv[1] ?? '';

try {
    echo finishCheck($mode);
} catch (Throwable $error) {
    // A broken check must neither trap the AI nor go unnoticed.
    if (in_array($mode, ['start', 'stop'], true)) {
        echo json_encode(['systemMessage' => 'The finish check failed: '.$error->getMessage()], JSON_OUTPUT);
        exit(0);
    }

    // composer check must not end green on a pass that was not recorded.
    fwrite(STDERR, 'The finish check did not record this composer check as passed: '.$error->getMessage().PHP_EOL);
    exit(1);
}

exit(0);

/**
 * Runs one mode and returns what goes to stdout.
 */
function finishCheck(string $mode): string
{
    $hook = in_array($mode, ['start', 'stop'], true);
    // Claude Code and Codex send the conversation as JSON on stdin; composer check sends nothing.
    $input = $hook ? json_decode((string) stream_get_contents(STDIN), true) : [];
    $input = is_array($input) ? $input : [];

    if (is_string($input['cwd'] ?? null) && is_dir($input['cwd'])) {
        chdir($input['cwd']);
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

    foreach (missingSteps($state, $stateDir, $lastMessage) as $key => [$step, $reason]) {
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
 * @param  array<string, mixed>  $state
 * @return array<string, array{string, string}>
 */
function missingSteps(array $state, string $stateDir, string $lastMessage): array
{
    $changed = changedInConversation($state);

    if ($changed === []) {
        return [];
    }

    $missing = [];
    $code = array_values(array_filter($changed, fn (string $path): bool => ! str_starts_with($path, 'docs/') && ! str_ends_with($path, '.md')));
    // Only a plan written or updated in this conversation belongs to its work: one left over in active/ does not.
    $plans = array_values(array_filter($changed, fn (string $path): bool => preg_match(PLAN_PATH, $path) === 1 && is_file($path)));
    $fingerprint = fingerprint();
    $checked = CheckRun::passed($stateDir, $fingerprint);

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
    if ($code !== [] && ! $checked) {
        $intermediate = count($plans) === 1 && isIntermediatePlan($plans[0]);
        $milestoneChecked = CheckRun::passed($stateDir, $fingerprint, true);

        if ($intermediate && ! $milestoneChecked) {
            $missing['milestone:'.$fingerprint] = ['pass milestone checks', 'Run composer check:milestone -- <affected test paths> for full static checks and affected tests, and record the scope and result in Progress. Run composer check now if shared infrastructure changed or the affected scope cannot be bounded.'];
        } elseif (! $intermediate) {
            $missing['check:'.$fingerprint] = ['pass composer check', 'The code changed since the last passing composer check. Run composer check and fix every failure.'];
        }
    }

    return $missing;
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

    if (! $milestone && (float) (is_file(TESTS_FINISHED) ? file_get_contents(TESTS_FINISHED) : 0) <= (float) $start['time']) {
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

    $code = array_filter(explode("\n", $files), function (string $line): bool {
        $path = explode("\t", $line, 2)[1] ?? '';

        return ! str_starts_with($path, 'docs/') && ! str_ends_with($path, '.md');
    });

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
