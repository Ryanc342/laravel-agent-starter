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
|   lanes <path>   print the lanes a change to <path> touches, as JSON
|
| The Stop hook checks one thing: this conversation changed code, and no
| composer check or composer check:milestone passed on the code as it is now.
| Then it sends the AI back once, never twice in a row: at the stop right after
| a send-back (stop_hook_active in the Stop input) the AI may stop, and Ryan
| gets one line saying the code has no passing check.
|
| Code is every file outside docs/ and *.md, and the files of the reference
| feature and of the sample copies other than Markdown. This conversation changed a file when a commit made since
| it started touches it, or when the file differs from HEAD and was written
| since it started; a deleted file has no time of its own, so the nearest folder
| above it that is still there must have been written since it started.
| The conversation starts at the first entry of its transcript.
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
const CHECK_FILES = ['phpstan.neon', 'vite.config.ts', 'tsconfig.json', 'phpunit.xml', 'pint.json', 'lint/', 'tests/Structure/', 'template/phpunit.xml', 'template/tests/Lint/'];

// Where docs/PLANS.md puts plans.
const PLAN_PATH = '#^docs/exec-plans/(active|completed)/[^/]+\.md$#';

const ACTIVE = 'docs/exec-plans/active/';

const COMPLETED = 'docs/exec-plans/completed/';

// The review lanes of docs/golden-principles.md ("Review lanes"): a change touches a
// lane when it changes a file in one of its places.
const REVIEW_LANES = [
    'backend' => ['app/', 'database/', 'routes/', 'tests/', 'resources/js/hooks/', 'resources/js/lib/', 'composer.json', 'package.json', 'scripts/', 'template/'],
    'frontend' => ['resources/js/', 'app/Http/Resources/', 'app/Http/Requests/'],
    'screens' => ['resources/js/', 'resources/css/', 'resources/views/', 'lang/', 'app/', 'DESIGN.md', 'docs/design/'],
];

// The reference feature (docs/reference/tasks/): its files other than Markdown
// are code and touch the lanes of their path inside the feature.
const REFERENCE_FEATURE = 'docs/reference/tasks/';

// The sample copies (docs/reference/pages/): its files other than Markdown are
// code and touch the lanes of the page or component they copy.
const SAMPLE_COPIES = 'docs/reference/pages/';

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
        echo json_encode(['systemMessage' => 'The finish check failed: '.failureText($error)], JSON_OUTPUT);
        exit(0);
    }

    $what = match ($mode) {
        'round' => 'could not prepare the review round',
        'passed' => 'did not record this check as passed',
        default => 'failed',
    };
    fwrite(STDERR, "The finish check {$what}: ".failureText($error).PHP_EOL);
    exit(1);
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

/**
 * Runs one mode and returns what goes to stdout.
 */
function finishCheck(string $mode, string $argument = ''): string
{
    // Claude Code sends the conversation as JSON on stdin; the other modes read nothing.
    $input = $mode === 'stop' ? json_decode((string) stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR) : [];

    if (! is_array($input)) {
        throw new RuntimeException('the Stop input is not a JSON object');
    }

    if (isset($input['cwd']) && ! (is_string($input['cwd']) && is_dir($input['cwd']) && chdir($input['cwd']))) {
        throw new RuntimeException('the Stop input names no folder to work in');
    }

    if ($mode === 'lanes') {
        return json_encode($argument === '' ? REVIEW_LANES : lanesOf($argument), JSON_OUTPUT | JSON_THROW_ON_ERROR);
    }

    // Outside a git repository, or before its first commit, there is nothing to
    // compare with; any other answer git refuses is an error the user is told.
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
    $stateDir = mustGit(['rev-parse', '--path-format=absolute', '--git-path', 'ezdev']);

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
    $session = is_string($input['session_id'] ?? null) && $input['session_id'] !== ''
        ? $input['session_id']
        : throw new RuntimeException('the Stop input names no session');
    $told = "{$stateDir}/told-".sha1($session).'.json';
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

    return $output === [] ? '' : json_encode($output, JSON_OUTPUT | JSON_THROW_ON_ERROR);
}

/**
 * When the conversation started: the first time stamp of its transcript, or
 * null when Claude Code names no transcript. A named transcript that is not
 * there, cannot be read or has no time stamp stops the check.
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

            // An entry without a time stamp is skipped; a broken line or time stops the check.
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
 * When a file was last written; a file whose time cannot be read stops the check.
 */
function modified(string $path): int
{
    clearstatcache(true, $path);
    $time = filemtime($path);

    return $time === false ? throw new RuntimeException("cannot read when {$path} was written") : $time;
}

/**
 * The nearest folder above a deleted file that is still there: deleting the file
 * (or a folder holding it) wrote that folder, so its time is when the deletion
 * happened at the latest.
 */
function nearestFolder(string $path): string
{
    do {
        $path = dirname($path);
    } while ($path !== '.' && ! is_dir($path));

    return $path;
}

/**
 * The files this conversation changed: touched by a commit made since $start,
 * or different from HEAD and written since $start (a deleted file: its nearest
 * folder that is still there, nearestFolder()). Without a start every file
 * that differs from HEAD counts.
 *
 * @return list<string>
 */
function changedInConversation(?float $start): array
{
    $working = array_values(array_filter(changedSince('HEAD'), fn (string $path): bool => $start === null || modified(is_file($path) ? $path : nearestFolder($path)) >= floor($start)));

    if ($start === null) {
        return $working;
    }

    $committed = explode("\n", mustGit(['log', '--since=@'.(int) floor($start), '--format=', '--name-only', '--no-renames']));

    return array_values(array_unique(array_filter([...$committed, ...$working])));
}

/**
 * Whether the last check that passed checked the code as it is now.
 */
function hasPassed(string $stateDir): bool
{
    $file = "{$stateDir}/".PASSED;

    return is_file($file) && trim(readText($file)) === fingerprint();
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
 * Code is every file outside docs/ and *.md, and the files of the reference
 * feature and of the sample copies other than Markdown: the files fingerprint() covers.
 */
function isCodePath(string $path): bool
{
    return ! str_ends_with($path, '.md') && (! str_starts_with($path, 'docs/') || str_starts_with($path, REFERENCE_FEATURE) || str_starts_with($path, SAMPLE_COPIES));
}

/**
 * The review lanes a change to $path touches (docs/golden-principles.md): a file
 * of the reference feature as its path inside the feature, a sample copy as the
 * page or component it copies.
 *
 * @return list<string>
 */
function lanesOf(string $path): array
{
    if (! isCodePath($path)) {
        return [];
    }

    if (str_starts_with($path, REFERENCE_FEATURE)) {
        $path = substr($path, strlen(REFERENCE_FEATURE));
    } elseif (str_starts_with($path, SAMPLE_COPIES.'components/')) {
        $path = 'resources/js/'.substr($path, strlen(SAMPLE_COPIES));
    } elseif (str_starts_with($path, SAMPLE_COPIES)) {
        $path = 'resources/js/pages/'.substr($path, strlen(SAMPLE_COPIES));
    }

    $lanes = [];

    foreach (REVIEW_LANES as $lane => $places) {
        foreach ($places as $place) {
            if ($path === $place || (str_ends_with($place, '/') && str_starts_with($path, $place))) {
                $lanes[] = $lane;

                break;
            }
        }
    }

    return $lanes;
}

/**
 * A file's text; a file that cannot be read stops the check, never reads as empty.
 */
function readText(string $path): string
{
    $text = file_get_contents($path);

    return $text === false ? throw new RuntimeException("cannot read {$path}") : $text;
}

/**
 * The commit before the plan was first added; HEAD for a plan not committed
 * yet, and the empty tree when the plan came with the first commit.
 */
function planBase(string $plan): string
{
    $name = basename($plan);
    $added = array_filter(explode("\n", mustGit(['log', '--format=%H', '--diff-filter=A', '--', ACTIVE.$name, COMPLETED.$name])));

    if ($added === []) {
        return 'HEAD';
    }

    $commits = explode(' ', mustGit(['rev-list', '--parents', '-n', '1', end($added)]));

    return count($commits) > 1 ? $commits[1] : '4b825dc642cb6eb9a060e54bf8d69288fbee4904';
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
    $paths = changedSince(planBase($plan));

    if (preg_match(TEMPLATE_COMMIT, readText($plan), $match) !== 1) {
        return $paths;
    }

    $template = gitAnswer(['rev-parse', '--verify', '--quiet', $match[1].'^{commit}'])
        ?? throw new RuntimeException("the plan's template commit {$match[1]} is not in this repository");

    return array_values(array_filter($paths, fn (string $path): bool => (gitAnswer(['rev-parse', '--verify', '--quiet', "{$template}:{$path}"]) ?? 'deleted') !== contentHash($path)));
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
    $before = planBase($plan);
    $untracked = array_filter(explode("\n", mustGit(['ls-files', '--others', '--exclude-standard'])));
    $paths = planChanges($plan);
    $tracked = array_values(array_diff($paths, $untracked));
    $diff = $tracked === [] ? '' : mustGit(['diff', '--no-renames', $before, '--', ...$tracked])."\n";

    foreach (array_intersect($paths, $untracked) as $path) {
        $diff .= newFileDiff($path);
    }

    if (! is_dir(REVIEW_FILES) && ! mkdir(REVIEW_FILES, 0777, true)) {
        throw new RuntimeException('cannot create '.REVIEW_FILES);
    }

    $diffFile = REVIEW_FILES.pathinfo($name, PATHINFO_FILENAME).'.diff';
    $planFile = REVIEW_FILES.$name;
    writeFile($diffFile, $diff);
    writeFile($planFile, readText($plan));

    return "Diff: {$diffFile}\nPlan: {$planFile}\n";
}

/**
 * The diff of an untracked file, as git shows a new file.
 */
function newFileDiff(string $path): string
{
    // git diff --no-index exits 1 when the files differ (an empty new file too) and 0 when they do not.
    [$exit, $output, $error] = gitRun(['diff', '--no-index', '--', '/dev/null', $path], trim: false);

    return in_array($exit, [0, 1], true) ? $output : throw new RuntimeException(trim("git diff failed for {$path}: {$error}", ' :'));
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
 * Runs git where a failure must not pass as an empty answer.
 *
 * @param  list<string>  $args
 * @param  array<string, string>  $env
 */
function mustGit(array $args, array $env = []): string
{
    [$exit, $output, $error] = gitRun($args, $env);

    return $exit === 0 ? $output : throw new RuntimeException(trim("git {$args[0]} failed: {$error}", ' :'));
}

/**
 * Asks `git rev-parse --verify --quiet`: its answer, or null when the object
 * does not exist (exit 1, no message). Any other failure throws.
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
 * Runs git and returns its exit code, its output and its error, both trimmed.
 *
 * @param  list<string>  $args
 * @param  array<string, string>  $env
 * @return array{int, string, string}
 */
function gitRun(array $args, array $env = [], bool $trim = true): array
{
    // Git's errors go to a file, so a long error cannot fill a pipe while the
    // output is read; its messages are in English, whatever the user's language.
    $errors = tempnam(sys_get_temp_dir(), 'ezdev-git') ?: throw new RuntimeException('no temporary file for git');
    $failed = '';

    try {
        $process = proc_open(['git', '-c', 'core.quotePath=false', ...$args], [1 => ['pipe', 'w'], 2 => ['file', $errors, 'w']], $pipes, null, [...getenv(), ...$env, 'LC_ALL' => 'C']);

        if (! is_resource($process)) {
            throw new RuntimeException('git could not start');
        }

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $exit = proc_close($process);

        if ($output === false) {
            throw new RuntimeException("cannot read the answer of git {$args[0]}");
        }

        $answer = [$exit, $trim ? trim($output) : $output, trim(readText($errors))];
        // When removing the file fails below, git's own failure is still told.
        $failed = $exit === 0 ? '' : "git {$args[0]} failed: {$answer[2]}";

        return $answer;
    } finally {
        try {
            removeTemporary($errors);
        } catch (RuntimeException $error) {
            throw $failed === '' ? $error : new RuntimeException("{$error->getMessage()}; before that: {$failed}", previous: $error->getPrevious());
        }
    }
}

/**
 * Removes a temporary file; one that cannot be removed stops the check, never
 * stays behind without a word.
 */
function removeTemporary(string $path): void
{
    if (is_file($path) && ! unlink($path)) {
        throw new RuntimeException("cannot remove the temporary file {$path}");
    }
}

/**
 * Files that differ from $base in the working tree, untracked files included.
 *
 * @return list<string>
 */
function changedSince(string $base): array
{
    $tracked = mustGit(['diff', '--name-only', '--no-renames', $base]);
    $untracked = mustGit(['ls-files', '--others', '--exclude-standard']);
    $paths = array_filter(array_merge(explode("\n", $tracked), explode("\n", $untracked)));

    return array_values(array_unique($paths));
}

function contentHash(string $path): string
{
    return is_file($path) ? mustGit(['hash-object', '--', $path]) : 'deleted';
}

/**
 * A hash of every code file (isCodePath), as it is in the working tree now.
 */
function fingerprint(): string
{
    $index = tempnam(sys_get_temp_dir(), 'ezdev-index');

    if ($index === false) {
        throw new RuntimeException('no temporary file for the fingerprint index');
    }

    // git makes the index itself, so the empty file goes first.
    removeTemporary($index);
    $env = ['GIT_INDEX_FILE' => $index];

    try {
        mustGit(['read-tree', 'HEAD'], $env);
        mustGit(['add', '-A'], $env);
        $files = mustGit(['ls-files', '-s'], $env);
    } finally {
        removeTemporary($index);
    }

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
