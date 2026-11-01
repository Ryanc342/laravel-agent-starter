<?php

/*
| The finish check: principle 9 in docs/golden-principles.md.
|
| Claude Code (.claude/settings.json) and Codex (.codex/hooks.json) run this
| script when a conversation starts and each time the AI is about to stop:
|
|   php scripts/hooks/finish-check.php start     remember where this conversation started
|   php scripts/hooks/finish-check.php stop      compare with the start; send back what is missing
|   php scripts/hooks/finish-check.php checked   called by composer check when everything passed
|
| A conversation that changed no file is never stopped. The state lives in
| .git/ezdev/, outside the repository's files.
*/

// AGENTS.md "Red lines": the user approves a change to these.
const PROTECTED_PATHS = [
    'AGENTS.md', 'CLAUDE.md', 'ARCHITECTURE.md', 'docs/golden-principles.md', 'docs/rules/', 'docs/prompts/',
    'phpstan.neon', 'vite.config.ts', 'tsconfig.json', 'pint.json', 'phpunit.xml',
    'lint/', 'tests/Structure/', 'tests/Support/', 'tests/Unit/Hooks/', 'scripts/',
    '.claude/', '.codex/', '.agents/', '.github/',
    'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
];

// A lock file is approved together with its manifest.
const LOCK_FILES = ['composer.lock' => 'composer.json', 'package-lock.json' => 'package.json'];

const MAX_BLOCKS = 3;

ini_set('display_errors', 'stderr');

$mode = $argv[1] ?? '';
// Claude Code and Codex send the conversation as JSON on stdin; composer check sends nothing.
$input = in_array($mode, ['start', 'stop'], true) ? json_decode((string) stream_get_contents(STDIN), true) : [];
$input = is_array($input) ? $input : [];

if (isset($input['cwd']) && is_string($input['cwd']) && is_dir($input['cwd'])) {
    chdir($input['cwd']);
}

$top = git(['rev-parse', '--show-toplevel']);

if ($top === null) {
    exit(0);
}

chdir($top);
$stateDir = git(['rev-parse', '--path-format=absolute', '--git-path', 'ezdev']) ?? "{$top}/.git/ezdev";
@mkdir($stateDir, 0777, true);

if ($mode === 'checked') {
    file_put_contents("{$stateDir}/checked", fingerprint());
    exit(0);
}

$session = is_string($input['session_id'] ?? null) ? $input['session_id'] : 'default';
$stateFile = "{$stateDir}/session-".sha1($session).'.json';
$state = is_file($stateFile) ? json_decode((string) file_get_contents($stateFile), true) : null;

if (! is_array($state) || git(['cat-file', '-e', ($state['base'] ?? '').'^{commit}']) === null) {
    // First call of this conversation (or its start was never recorded): start here.
    $dirty = [];

    foreach (changedSince(null) as $path) {
        $dirty[$path] = contentHash($path);
    }

    $state = [
        'base' => git(['rev-parse', 'HEAD']) ?? '',
        'dirty' => $dirty,
        'debt' => debtRows((string) @file_get_contents('docs/exec-plans/tech-debt.md')),
        'blocks' => [],
    ];
    file_put_contents($stateFile, json_encode($state));

    exit(0);
}

if ($mode !== 'stop') {
    exit(0);
}

// A file that was already changed when the conversation started counts only if it changed again.
$changed = array_values(array_filter(
    changedSince($state['base']),
    fn (string $path): bool => ! array_key_exists($path, $state['dirty']) || $state['dirty'][$path] !== contentHash($path),
));

if ($changed === []) {
    exit(0);
}

$reasons = [];
$code = array_values(array_filter($changed, fn (string $path): bool => ! str_starts_with($path, 'docs/') && ! str_ends_with($path, '.md')));
$plans = plansInPlay($changed);

$fingerprint = fingerprint();

// 1. Code needs a plan (a one-line change with no open decision may skip it).
if ($code !== [] && $plans === [] && ! isOneLineChange($state['base'], $code)) {
    $reasons['plan:'.$fingerprint] = 'You changed code ('.summary($code).') but there is no plan in docs/exec-plans/active/. Write the plan (docs/workflow.md, step 2) and wait for the user\'s "start". A request to fix tech debt needs one too.';
}

// 2. A fixed tech-debt line updates docs/QUALITY.md.
$removed = array_values(array_diff($state['debt'] ?? [], debtRows((string) @file_get_contents('docs/exec-plans/tech-debt.md'))));

if ($removed !== [] && ! in_array('docs/QUALITY.md', $changed, true)) {
    $reasons['quality:'.sha1(implode("\n", $removed))] = 'You removed '.count($removed).' line(s) from docs/exec-plans/tech-debt.md but docs/QUALITY.md did not change. Update the grade and notes of that domain.';
}

// 3. A protected file or dependency needs the user's approval, named in a plan's Decision Log.
$decisionLogs = implode("\n", array_map(fn (string $plan): string => sections((string) @file_get_contents($plan))['Decision Log'] ?? '', $plans));
$unapproved = array_values(array_filter(
    $changed,
    fn (string $path): bool => isProtected($path) && ! str_contains($decisionLogs, LOCK_FILES[$path] ?? $path),
));
$lastMessage = is_string($input['last_assistant_message'] ?? null) ? $input['last_assistant_message'] : '';
$askingAboutThem = $unapproved !== [] && array_filter($unapproved, fn (string $path): bool => ! str_contains($lastMessage, $path)) === [];

// Stopping to ask the user about exactly these files is the step itself.
if ($unapproved !== [] && ! $askingAboutThem) {
    $reasons['protected:'.implode(',', $unapproved)] = 'You changed '.implode(', ', $unapproved).' (protected files or dependencies, AGENTS.md "Red lines"). Ask the user first, then record their approval in the plan\'s Decision Log, naming each file. Without approval, undo the change.';
}

// 4. Code changed since the last passing composer check.
if ($code !== [] && trim((string) @file_get_contents("{$stateDir}/checked")) !== $fingerprint) {
    $reasons['check:'.$fingerprint] = 'The code changed since the last passing composer check. Run composer check and fix every failure.';
}

// 5. A completed plan records the check results, the review rounds and the outcome.
foreach ($plans as $plan) {
    if (! str_starts_with($plan, 'docs/exec-plans/completed/')) {
        continue;
    }

    $missing = missingRecords((string) @file_get_contents($plan));

    if ($missing !== []) {
        $reasons['completed:'.$plan] = "{$plan} is complete but has ".implode(', ', $missing).'. Fill them in (docs/workflow.md, steps 3 to 5).';
    }
}

// Each reason is sent back at most MAX_BLOCKS times, so a stop can never loop forever.
$send = [];

foreach ($reasons as $key => $reason) {
    $state['blocks'][$key] = ($state['blocks'][$key] ?? 0) + 1;

    if ($state['blocks'][$key] <= MAX_BLOCKS) {
        $send[] = $reason;
    }
}

file_put_contents($stateFile, json_encode($state));

if ($send !== []) {
    $lines = array_map(fn (string $reason, int $i): string => ($i + 1).'. '.$reason, $send, array_keys($send));

    echo json_encode([
        'decision' => 'block',
        'reason' => "Before you stop (docs/golden-principles.md, principle 9):\n".implode("\n", $lines),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

exit(0);

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
 * Plans that belong to this conversation: every plan in active/, and plans in
 * completed/ that changed in it.
 *
 * @param  list<string>  $changed
 * @return list<string>
 */
function plansInPlay(array $changed): array
{
    $active = glob('docs/exec-plans/active/*.md') ?: [];
    $completed = array_filter($changed, fn (string $path): bool => str_starts_with($path, 'docs/exec-plans/completed/') && str_ends_with($path, '.md') && is_file($path));

    return array_values(array_unique([...$active, ...$completed]));
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
 * What a completed plan is missing.
 *
 * @return list<string>
 */
function missingRecords(string $plan): array
{
    $sections = sections($plan);

    $progress = $sections['Progress'] ?? '';
    $missing = [];

    if (stripos($progress, 'composer check') === false) {
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
