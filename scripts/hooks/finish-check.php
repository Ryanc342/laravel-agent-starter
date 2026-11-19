<?php

/*
| The finish check (docs/workflow.md).
|
| Claude Code (.claude/settings.json) runs it as the Stop hook; composer check
| and composer check:milestone (scripts/check-process.php) run it first and last:
|
|   php scripts/hooks/finish-check.php stop          Stop: send back changed code no check passed on
|   php scripts/hooks/finish-check.php fingerprint   print the fingerprint of the code as it is now
|   php scripts/hooks/finish-check.php passed <fp>   a check passed: record <fp> when the code is still that
|   php scripts/hooks/finish-check.php revoke        a check starts: forget the last pass
|   round <plan>   before a review round: write the plan's diff and a copy of the plan for the reviewers
|   lanes          print the review lanes and the places that touch them, as JSON
|
| The Stop hook checks one thing: this conversation changed code, and no
| composer check or composer check:milestone passed on the code as it is now.
| Then it sends the AI back once, never twice in a row: at the stop right after
| a send-back (stop_hook_active in the Stop input) the AI may stop, and Ryan
| gets one line saying the code has no passing check.
|
| Code is every file outside docs/ and *.md, and the reference feature's files
| other than Markdown. This conversation changed a file when a commit made since
| it started touches it, or when the file differs from HEAD and was written
| since it started. The conversation starts at the first entry of its transcript.
|
| When this conversation changed a check file (CHECK_FILES), the Stop hook also
| prints one line for Ryan, without blocking, once for each new set of files.
|
| A pass is a fingerprint of the code: a check revokes the last pass when it
| starts and records the code's fingerprint when it passed, if the code did
| not change while it ran. The state lives in .git/ezdev/ (git rev-parse
| --git-path ezdev), outside the repository's files.
|
| Claude Code reads stdout: nothing lets the AI stop, one JSON object sends it
| back or tells Ryan. Anything else there would break that, so warnings go to
| stderr and every error ends as a message to Ryan.
*/

// The files that make a check: a change to one is told to Ryan.
const CHECK_FILES = ['phpstan.neon', 'vite.config.ts', 'tsconfig.json', 'phpunit.xml', 'pint.json', 'lint/', 'tests/Structure/'];

// Where docs/PLANS.md puts plans.
const PLAN_PATH = '#^docs/exec-plans/(active|completed)/[^/]+\.md$#';

const ACTIVE = 'docs/exec-plans/active/';

const COMPLETED = 'docs/exec-plans/completed/';

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

// Where `round` writes the diff and the plan the reviewers read (ignored by git).
const REVIEW_FILES = 'storage/framework/testing/ezdev-review/';

// A plan that syncs the template into a project names the template's commit
// (docs/prompts/upgrade.md): a file identical to the template's there is not reviewed.
const TEMPLATE_COMMIT = '/^\s*(?:[-*+]\s+)?Template:\s*`?([0-9a-f]{7,40})`?/mi';

const PASSED = 'passed';

const JSON_OUTPUT = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

ini_set('display_errors', 'stderr');

$mode = $argv[1] ?? '';

try {
    echo finishCheck($mode, $argv[2] ?? '');
} catch (Throwable $error) {
    // A broken check must neither trap the AI nor go unnoticed.
    if ($mode === 'stop') {
        echo json_encode(['systemMessage' => 'The finish check failed: '.$error->getMessage()], JSON_OUTPUT);
        exit(0);
    }

    $what = match ($mode) {
        'round' => 'could not prepare the review round',
        'passed' => 'did not record this check as passed',
        default => 'failed',
    };
    fwrite(STDERR, "The finish check {$what}: ".$error->getMessage().PHP_EOL);
    exit(1);
}

/**
 * Runs one mode and returns what goes to stdout.
 */
function finishCheck(string $mode, string $argument = ''): string
{
    // Claude Code sends the conversation as JSON on stdin; the other modes read nothing.
    $input = $mode === 'stop' ? json_decode((string) stream_get_contents(STDIN), true) : [];
    $input = is_array($input) ? $input : [];

    if (is_string($input['cwd'] ?? null) && is_dir($input['cwd'])) {
        chdir($input['cwd']);
    }

    if ($mode === 'lanes') {
        return json_encode(REVIEW_LANES, JSON_OUTPUT | JSON_THROW_ON_ERROR);
    }

    $top = git(['rev-parse', '--show-toplevel']);

    // Outside a git repository, or before its first commit, there is nothing to compare with.
    if ($top === null || git(['rev-parse', '--verify', '--quiet', 'HEAD']) === null) {
        return '';
    }

    chdir($top);
    $stateDir = git(['rev-parse', '--path-format=absolute', '--git-path', 'ezdev']) ?? "{$top}/.git/ezdev";

    if (! is_dir($stateDir) && ! mkdir($stateDir, 0777, true)) {
        throw new RuntimeException("cannot create {$stateDir}");
    }

    return match ($mode) {
        'stop' => stop($stateDir, $input),
        'fingerprint' => fingerprint()."\n",
        'revoke' => revoke($stateDir),
        'passed' => passed($stateDir, $argument),
        'round' => reviewRound($argument),
        default => throw new RuntimeException("unknown mode {$mode}"),
    };
}

/**
 * The AI is about to stop. Changed code with no pass on it sends the AI back,
 * once; right after a send-back ($input['stop_hook_active']) Ryan gets one line
 * instead. A changed check file gives Ryan one line, once per new set of files.
 *
 * @param  array<string, mixed>  $input
 */
function stop(string $stateDir, array $input): string
{
    $changed = changedInConversation(conversationStart($input['transcript_path'] ?? null));
    $code = array_values(array_filter($changed, 'isCodePath'));
    $output = [];
    $lines = [];

    if ($code !== [] && ! hasPassed($stateDir)) {
        if (($input['stop_hook_active'] ?? false) === true) {
            $lines[] = 'The AI stopped without a passing composer check on the code it changed ('.summary($code).').';
        } else {
            $output = ['decision' => 'block', 'reason' => 'You changed code ('.summary($code).') and no composer check or composer check:milestone passed on it as it is now. Run composer check:milestone -- <affected test paths> (or composer check) and fix every failure.'];
        }
    }

    $checks = array_values(array_filter($changed, 'isCheckFile'));
    $session = is_string($input['session_id'] ?? null) ? $input['session_id'] : 'default';
    $told = "{$stateDir}/told-".sha1($session).'.json';
    $before = is_file($told) ? json_decode((string) file_get_contents($told), true) : [];

    if ($checks !== [] && array_diff($checks, is_array($before) ? $before : []) !== []) {
        $lines[] = 'This conversation changed a check: '.implode(', ', $checks).'.';
        writeFile($told, json_encode($checks, JSON_THROW_ON_ERROR));
    }

    if ($lines !== []) {
        $output['systemMessage'] = implode("\n", $lines);
    }

    return $output === [] ? '' : json_encode($output, JSON_OUTPUT | JSON_THROW_ON_ERROR);
}

/**
 * When the conversation started: the first time stamp of its transcript, or
 * null when there is none to read.
 */
function conversationStart(mixed $transcript): ?float
{
    if (! is_string($transcript) || ! is_file($transcript)) {
        return null;
    }

    $file = fopen($transcript, 'r');

    if ($file === false) {
        return null;
    }

    try {
        while (($line = fgets($file)) !== false) {
            $entry = json_decode($line, true);
            $time = is_array($entry) && is_string($entry['timestamp'] ?? null) ? strtotime($entry['timestamp']) : false;

            if ($time !== false) {
                return (float) $time;
            }
        }
    } finally {
        fclose($file);
    }

    return null;
}

/**
 * The files this conversation changed: touched by a commit made since $start,
 * or different from HEAD and written since $start. Without a start every file
 * that differs from HEAD counts.
 *
 * @return list<string>
 */
function changedInConversation(?float $start): array
{
    $working = array_values(array_filter(changedSince('HEAD'), fn (string $path): bool => $start === null || ! is_file($path) || filemtime($path) >= floor($start)));

    if ($start === null) {
        return $working;
    }

    $committed = explode("\n", git(['log', '--since=@'.(int) floor($start), '--format=', '--name-only', '--no-renames']) ?? '');

    return array_values(array_unique(array_filter([...$committed, ...$working])));
}

/**
 * Whether the last check that passed checked the code as it is now.
 */
function hasPassed(string $stateDir): bool
{
    $file = "{$stateDir}/".PASSED;

    return is_file($file) && trim((string) file_get_contents($file)) === fingerprint();
}

/**
 * A check starts: until it passes, no earlier pass counts.
 */
function revoke(string $stateDir): string
{
    $file = "{$stateDir}/".PASSED;

    if (is_file($file) && ! unlink($file)) {
        throw new RuntimeException("cannot remove {$file}");
    }

    return '';
}

/**
 * A check passed on the code whose fingerprint it took when it started: record
 * it, unless the code changed while the check ran.
 */
function passed(string $stateDir, string $started): string
{
    if (preg_match('/^[0-9a-f]{40}$/', $started) !== 1) {
        throw new RuntimeException('the check did not take the fingerprint of the code it started on.');
    }

    if ($started !== fingerprint()) {
        throw new RuntimeException('the code changed while the check ran. Run it again.');
    }

    writeFile("{$stateDir}/".PASSED, $started."\n");

    return '';
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
 * Code is every file outside docs/ and *.md, and the reference feature's files
 * other than Markdown: the files fingerprint() covers.
 */
function isCodePath(string $path): bool
{
    return ! str_ends_with($path, '.md') && (! str_starts_with($path, 'docs/') || str_starts_with($path, REFERENCE_FEATURE));
}

/**
 * Every file the plan's work changed: since the commit before the plan was first
 * added, or, for a plan not yet committed, since HEAD. A file that is as it is
 * in the template commit a sync plan names is left out.
 *
 * @return list<string>
 */
function planChanges(string $plan): array
{
    $name = basename($plan);
    $added = array_filter(explode("\n", git(['log', '--format=%H', '--diff-filter=A', '--', ACTIVE.$name, COMPLETED.$name]) ?? ''));
    // The empty tree, when the plan came with the first commit.
    $before = $added === [] ? 'HEAD' : git(['rev-parse', '--verify', '--quiet', end($added).'^']) ?? '4b825dc642cb6eb9a060e54bf8d69288fbee4904';
    $paths = changedSince($before);
    $template = preg_match(TEMPLATE_COMMIT, (string) file_get_contents($plan), $match) === 1
        ? git(['rev-parse', '--verify', '--quiet', $match[1].'^{commit}'])
        : null;

    if ($template === null) {
        return $paths;
    }

    return array_values(array_filter($paths, fn (string $path): bool => (git(['rev-parse', '--verify', '--quiet', "{$template}:{$path}"]) ?? 'deleted') !== contentHash($path)));
}

/**
 * Before a review round (docs/workflow.md, step 4): reviewers have no shell, so
 * this writes what they read into REVIEW_FILES. The diff is the plan's changes
 * since the commit before the plan was added (HEAD for a plan not committed
 * yet), untracked files included, without the files a sync plan's template
 * commit already has. The plan is copied as it is. Prints both paths.
 */
function reviewRound(string $plan): string
{
    if (preg_match(PLAN_PATH, $plan) !== 1 || ! is_file($plan)) {
        throw new RuntimeException("no plan at {$plan}");
    }

    $name = basename($plan);
    $added = array_filter(explode("\n", git(['log', '--format=%H', '--diff-filter=A', '--', ACTIVE.$name, COMPLETED.$name]) ?? ''));
    $before = $added === [] ? 'HEAD' : git(['rev-parse', '--verify', '--quiet', end($added).'^']) ?? '4b825dc642cb6eb9a060e54bf8d69288fbee4904';
    $untracked = array_filter(explode("\n", git(['ls-files', '--others', '--exclude-standard']) ?? ''));
    $paths = planChanges($plan);
    $tracked = array_values(array_diff($paths, $untracked));
    $diff = $tracked === [] ? '' : (git(['diff', '--no-renames', $before, '--', ...$tracked]) ?? '')."\n";

    foreach (array_intersect($paths, $untracked) as $path) {
        $diff .= newFileDiff($path);
    }

    if (! is_dir(REVIEW_FILES) && ! mkdir(REVIEW_FILES, 0777, true)) {
        throw new RuntimeException('cannot create '.REVIEW_FILES);
    }

    $diffFile = REVIEW_FILES.pathinfo($name, PATHINFO_FILENAME).'.diff';
    $planFile = REVIEW_FILES.$name;
    writeFile($diffFile, $diff);
    writeFile($planFile, (string) file_get_contents($plan));

    return "Diff: {$diffFile}\nPlan: {$planFile}\n";
}

/**
 * The diff of an untracked file, as git shows a new file.
 */
function newFileDiff(string $path): string
{
    $process = proc_open(['git', '-c', 'core.quotePath=false', 'diff', '--no-index', '--', '/dev/null', $path], [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);

    if (! is_resource($process)) {
        return '';
    }

    $output = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    proc_close($process);

    return $output;
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
 * Files that differ from $base in the working tree, untracked files included.
 *
 * @return list<string>
 */
function changedSince(string $base): array
{
    $tracked = git(['diff', '--name-only', '--no-renames', $base]) ?? '';
    $untracked = git(['ls-files', '--others', '--exclude-standard']) ?? '';
    $paths = array_filter(array_merge(explode("\n", $tracked), explode("\n", $untracked)));

    return array_values(array_unique($paths));
}

function contentHash(string $path): string
{
    return is_file($path) ? (git(['hash-object', '--', $path]) ?? '') : 'deleted';
}

/**
 * A hash of every code file (isCodePath), as it is in the working tree now.
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
