<?php

/*
| The command check (docs/prompts/review.md, docs/golden-principles.md
| principle 9): Claude Code runs this script before every Bash call
| (.claude/settings.json, PreToolUse). A call of a reviewer subagent
| (`agent_type` reviewer-*) runs only when it reads:
|
| - a git subcommand that only reads (READ_ONLY_GIT), alone, joined with && or ;,
| - or piped into a filter that only reads its input (FILTERS).
|
| Everything else is refused before it runs: any other program, a redirection,
| a command or process substitution, a background job, git's --output, `sed -i`,
| a sed script that writes or runs. The reviewer reads the reason; the refusal
| is written to reviewer-refusals.jsonl in the state folder (time, reviewer,
| command). The builder's own calls and other subagents are never looked at.
*/

const READ_ONLY_GIT = ['diff', 'show', 'log', 'status', 'ls-files', 'ls-tree', 'rev-parse', 'rev-list', 'merge-base', 'cat-file', 'grep', 'blame', 'describe'];

// git options that write a file or start another program. git takes any
// unique prefix of a long option (`--open` for --open-files-in-pager), so a
// long option is refused when its name starts one of these.
const GIT_LONG_WRITES = ['output', 'open-files-in-pager', 'ext-diff'];

// git short options that write or start a program, also inside a cluster
// (`-iO` is `-i -O`).
const GIT_SHORT_WRITES = ['O'];

// sort options that write a file or start another program, the same way.
const SORT_LONG_WRITES = ['output', 'compress-program'];
const SORT_SHORT_WRITES = ['o'];

const FILTERS = ['head', 'tail', 'grep', 'wc', 'sort', 'uniq', 'cut', 'cat', 'tr', 'sed'];

const REASON = 'Reviewers only read. Bash runs only the git commands that read (git diff, show, log, status, ls-files, ls-tree, rev-parse, rev-list, merge-base, cat-file, grep, blame, describe), alone, joined with && or ;, or piped into head, tail, grep, wc, sort, uniq, cut, cat, tr or sed -n \'<lines>p\'. No redirection, substitution, tee, xargs or awk. Read files with the Read tool and search them with Read, Grep and Glob.';

$input = json_decode((string) stream_get_contents(STDIN), true);
$input = is_array($input) ? $input : [];
$agent = $input['agent_type'] ?? null;
$command = $input['tool_input']['command'] ?? null;

if (! is_string($agent) || ! str_starts_with($agent, 'reviewer-') || ! is_string($command)) {
    exit(0);
}

if (readsOnly($command)) {
    exit(0);
}

if (is_string($input['cwd'] ?? null) && is_dir($input['cwd'])) {
    chdir($input['cwd']);
}

$state = trim((string) shell_exec('git rev-parse --path-format=absolute --git-path ezdev 2>/dev/null'));

if ($state !== '') {
    @mkdir($state, 0777, true);
    @file_put_contents("{$state}/reviewer-refusals.jsonl", json_encode(['time' => date('c'), 'reviewer' => $agent, 'command' => $command], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND | LOCK_EX);
}

echo json_encode(['hookSpecificOutput' => ['hookEventName' => 'PreToolUse', 'permissionDecision' => 'deny', 'permissionDecisionReason' => REASON]], JSON_UNESCAPED_SLASHES);

/**
 * Whether the command only reads: every pipeline starts with a git command that
 * reads and pipes only into filters that read.
 */
function readsOnly(string $command): bool
{
    $pipelines = split($command);

    if ($pipelines === null || $pipelines === []) {
        return false;
    }

    foreach ($pipelines as $pipeline) {
        foreach ($pipeline as $index => $words) {
            if ($words === [] || ! ($index === 0 ? gitReads($words) : filterReads($words))) {
                return false;
            }
        }
    }

    return true;
}

/**
 * The command as pipelines (split at && and ;), each a list of stages (split
 * at |), each a list of words with their quotes removed. Null when the command
 * holds anything else outside quotes: a redirection, a substitution, a
 * background job, ||, a subshell, a variable or a new line.
 *
 * @return list<list<list<string>>>|null
 */
function split(string $command): ?array
{
    $pipelines = [];
    $stages = [];
    $words = [];
    $word = null;
    $length = strlen($command);

    for ($i = 0; $i < $length; $i++) {
        $char = $command[$i];

        if ($char === "'") {
            $end = strpos($command, "'", $i + 1);

            if ($end === false) {
                return null;
            }

            $word = ($word ?? '').substr($command, $i + 1, $end - $i - 1);
            $i = $end;

            continue;
        }

        if ($char === '"') {
            $end = $i + 1;
            $text = '';

            while ($end < $length && $command[$end] !== '"') {
                if (in_array($command[$end], ['$', '`'], true)) {
                    return null;
                }

                if ($command[$end] === '\\' && $end + 1 < $length) {
                    $end++;
                }

                $text .= $command[$end];
                $end++;
            }

            if ($end >= $length) {
                return null;
            }

            $word = ($word ?? '').$text;
            $i = $end;

            continue;
        }

        if ($char === '\\') {
            if ($i + 1 >= $length || $command[$i + 1] === "\n") {
                return null;
            }

            $word = ($word ?? '').$command[++$i];

            continue;
        }

        if ($char === ' ' || $char === "\t") {
            if ($word !== null) {
                $words[] = $word;
                $word = null;
            }

            continue;
        }

        $joiner = match (true) {
            substr($command, $i, 2) === '&&' => '&&',
            $char === ';' => ';',
            // `||` leaves an empty stage between its two pipes, which is refused.
            $char === '|' => '|',
            default => null,
        };

        if ($joiner !== null) {
            if ($word !== null) {
                $words[] = $word;
                $word = null;
            }

            $stages[] = $words;
            $words = [];

            if ($joiner !== '|') {
                $pipelines[] = $stages;
                $stages = [];
            }

            $i += strlen($joiner) - 1;

            continue;
        }

        if (str_contains("<>&`$(){}\n\r!*?[]", $char)) {
            // Globs (* ? [ ]) are refused too: git takes its own patterns in quotes.
            return null;
        }

        $word = ($word ?? '').$char;
    }

    if ($word !== null) {
        $words[] = $word;
    }

    if ($words !== [] || $stages !== []) {
        $stages[] = $words;
        $pipelines[] = $stages;
    }

    return $pipelines;
}

/**
 * @param  list<string>  $words
 */
function gitReads(array $words): bool
{
    if ($words[0] !== 'git') {
        return false;
    }

    $rest = array_slice($words, 1);

    while (($rest[0] ?? null) === '--no-pager') {
        array_shift($rest);
    }

    if (! in_array($rest[0] ?? null, READ_ONLY_GIT, true)) {
        return false;
    }

    // Every word, also after `--`: that `--` may be an option's value
    // (`git grep -e -- -O rm`), and git then still reads what follows.
    foreach (array_slice($rest, 1) as $word) {
        if (writingOption($word, GIT_LONG_WRITES, GIT_SHORT_WRITES)) {
            return false;
        }
    }

    return true;
}

/**
 * Whether $word is an option that writes: a long option whose name (before =)
 * starts one of $long, or a cluster of short options that holds one of $short.
 *
 * @param  list<string>  $long
 * @param  list<string>  $short
 */
function writingOption(string $word, array $long, array $short): bool
{
    if (str_starts_with($word, '--')) {
        $name = explode('=', substr($word, 2), 2)[0];

        return $name !== '' && array_filter($long, fn (string $option): bool => str_starts_with($option, $name)) !== [];
    }

    // The whole cluster, digits included: git reads `-1O` as `-1 -O`.
    if (preg_match('/^-(.+)/', $word, $cluster) === 1) {
        return array_intersect(str_split($cluster[1]), $short) !== [];
    }

    return false;
}

/**
 * @param  list<string>  $words
 */
function filterReads(array $words): bool
{
    $program = $words[0];
    $arguments = array_slice($words, 1);

    if (! in_array($program, FILTERS, true)) {
        return false;
    }

    return match ($program) {
        'sort' => array_filter($arguments, fn (string $word): bool => writingOption($word, SORT_LONG_WRITES, SORT_SHORT_WRITES)) === [],
        // uniq writes to a second file name; in a pipe it takes none.
        'uniq' => array_filter($arguments, fn (string $word): bool => ! str_starts_with($word, '-')) === [],
        // Only printing line ranges: `sed -n '10,40p'`.
        'sed' => count($arguments) === 2 && $arguments[0] === '-n' && preg_match('/^(\d+|\$)(,(\d+|\$))?p$/', $arguments[1]) === 1,
        default => true,
    };
}
