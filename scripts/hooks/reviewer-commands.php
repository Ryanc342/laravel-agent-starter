<?php

/*
| The command check (docs/prompts/review.md, docs/golden-principles.md
| principle 9): Claude Code runs this script before every Bash call
| (.claude/settings.json, PreToolUse). A call of a reviewer subagent
| (`agent_type` reviewer-*) runs only when every command in it only reads:
|
| - a git subcommand that reads (READ_ONLY_GIT), also after `git -C <folder>`,
| - a program that reads files or its input (READERS), without the options
|   that write a file or start another program,
| - `cd <folder>`,
|
| alone, joined with && or ;, or piped into each other. Everything else is
| refused before it runs: any other program (one that writes, a shell, the
| network), a redirection, a command or process substitution, a variable, a
| glob outside quotes, a background job. The reviewer reads the reason; the
| refusal is written to reviewer-refusals.jsonl in the state folder (time,
| reviewer, command). The builder's own calls and other subagents are never
| looked at.
*/

const READ_ONLY_GIT = ['diff', 'show', 'log', 'status', 'ls-files', 'ls-tree', 'rev-parse', 'rev-list', 'merge-base', 'cat-file', 'grep', 'blame', 'describe', 'shortlog', 'diff-tree', 'for-each-ref', 'name-rev', 'show-ref'];

// git subcommands that read only with these words after them.
const READ_ONLY_GIT_USES = ['worktree' => ['list'], 'branch' => ['--list', '-a', '-r', '-v', '-vv', '--show-current']];

// git options that write a file or start another program. git takes any
// unique prefix of a long option (`--open` for --open-files-in-pager), so a
// long option is refused when its name starts one of these.
const GIT_LONG_WRITES = ['output', 'open-files-in-pager', 'ext-diff'];

// git short options that write or start a program, also inside a cluster
// (`-iO` is `-i -O`).
const GIT_SHORT_WRITES = ['O'];

// The programs that read, each with the long and short options that would make
// it write a file or start another program.
const READERS = [
    'cat' => [[], []],
    'head' => [[], []],
    'tail' => [[], []],
    'grep' => [[], []],
    // --pre runs a program on each file, --hostname-bin one for the host of its links.
    'rg' => [['pre', 'pre-glob', 'hostname-bin'], []],
    'wc' => [[], []],
    'sort' => [['output', 'compress-program'], ['o']],
    'uniq' => [[], []],
    'cut' => [[], []],
    'tr' => [[], []],
    'ls' => [[], []],
    'diff' => [[], []],
    'comm' => [[], []],
    'nl' => [[], []],
    'stat' => [[], []],
    'du' => [[], []],
    'basename' => [[], []],
    'dirname' => [[], []],
    'realpath' => [[], []],
    'pwd' => [[], []],
    'echo' => [[], []],
    'jq' => [[], []],
    'find' => [[], []],
    'sed' => [[], []],
];

// find's actions that delete, write a file or start a program.
const FIND_WRITES = ['-delete', '-exec', '-execdir', '-ok', '-okdir', '-fprint', '-fprint0', '-fprintf', '-fls'];

// A sed script that only prints: an optional address or range (a line, `$`, a
// /pattern/) followed by `p`.
const SED_PRINT = '#^(?:(?:\d+|\$|/(?:[^/\\\\]|\\\\.)*/)(?:,(?:\d+|\$|/(?:[^/\\\\]|\\\\.)*/))?)?p$#';

const REASON = 'Reviewers only read. Bash runs only commands that read: git commands that read (git diff, show, log, status, ls-files, ls-tree, rev-parse, rev-list, merge-base, cat-file, grep, blame, describe, shortlog, worktree list, also after git -C <folder>), cd <folder>, and cat, head, tail, grep, rg, find, ls, wc, sort, uniq, cut, tr, diff, jq, echo and sed -n \'<address>p\', alone, joined with && or ; or piped. No redirection, substitution, variable, glob outside quotes, tee, xargs or awk, no option that writes or starts a program (find -delete or -exec, rg --pre or --hostname-bin, sort -o, git --output), no network. Read files with the Read tool and search them with Read, Grep and Glob.';

$raw = (string) stream_get_contents(STDIN);
$input = json_decode($raw, true);
// Input PHP cannot read (a lone UTF-16 surrogate) holds a command nothing can
// check: a reviewer's is refused.
$unreadable = ! is_array($input);
$input = is_array($input) ? $input : [];
$agent = $input['agent_type'] ?? (preg_match('/"agent_type"\s*:\s*"(reviewer-[\w-]*)"/', $raw, $match) === 1 ? $match[1] : null);
$command = $input['tool_input']['command'] ?? ($unreadable ? '(input the hook cannot read)' : null);

if (! is_string($agent) || ! str_starts_with($agent, 'reviewer-') || ! is_string($command)) {
    exit(0);
}

if (! $unreadable && readsOnly($command)) {
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
 * Whether the command only reads: every stage of every pipeline is a command
 * that reads.
 */
function readsOnly(string $command): bool
{
    $pipelines = split($command);

    if ($pipelines === null || $pipelines === []) {
        return false;
    }

    foreach ($pipelines as $pipeline) {
        foreach ($pipeline as $words) {
            if ($words === [] || ! commandReads($words)) {
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
 * background job, ||, a subshell, a variable, a glob or a new line.
 *
 * @return list<list<list<string>>>|null
 */
function split(string $command): ?array
{
    // A new line ends a command, also inside quotes it can start a second sed command.
    if (strpbrk($command, "\n\r") !== false) {
        return null;
    }

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

                // Inside double quotes the shell drops a backslash only before
                // these; before any other character the program gets it too.
                if ($command[$end] === '\\' && $end + 1 < $length && in_array($command[$end + 1], ['$', '`', '"', '\\'], true)) {
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
            if ($i + 1 >= $length) {
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

        if (str_contains('<>&`$(){}!*?[]', $char)) {
            // Globs (* ? [ ]) are refused too: a file name could turn into an option.
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
 * Whether one command (a stage of a pipeline) only reads.
 *
 * @param  list<string>  $words
 */
function commandReads(array $words): bool
{
    $program = $words[0];
    $arguments = array_slice($words, 1);

    if ($program === 'git') {
        return gitReads($arguments);
    }

    // `cd` moves to one folder and reads nothing.
    if ($program === 'cd') {
        return count($arguments) <= 1;
    }

    if (! isset(READERS[$program])) {
        return false;
    }

    [$long, $short] = READERS[$program];

    if (array_filter($arguments, fn (string $word): bool => writingOption($word, $long, $short)) !== []) {
        return false;
    }

    return match ($program) {
        'find' => array_intersect($arguments, FIND_WRITES) === [],
        // uniq writes to a second file name.
        'uniq' => count(fileOperands($arguments)) <= 1,
        // Only printing: `sed -n '<address>p' [files]`.
        'sed' => ($arguments[0] ?? null) === '-n' && preg_match(SED_PRINT, $arguments[1] ?? '') === 1
            && array_filter(array_slice($arguments, 2), fn (string $word): bool => str_starts_with($word, '-')) === [],
        default => true,
    };
}

/**
 * The words a command reads as file names: every word that is not an option,
 * and every word after `--` or after the first file name (BSD tools stop
 * reading options there), so a name that starts with `-` counts too.
 *
 * @param  list<string>  $arguments
 * @return list<string>
 */
function fileOperands(array $arguments): array
{
    $operands = [];

    foreach ($arguments as $index => $word) {
        if ($word === '--' && $operands === []) {
            return [...$operands, ...array_slice($arguments, $index + 1)];
        }

        if ($operands !== [] || $word === '-' || ! str_starts_with($word, '-')) {
            $operands[] = $word;
        }
    }

    return $operands;
}

/**
 * Whether the words after `git` only read: `-C <folder>` and `--no-pager` may
 * come first, then a subcommand that reads, with no option that writes.
 *
 * @param  list<string>  $rest
 */
function gitReads(array $rest): bool
{
    while (in_array($rest[0] ?? null, ['--no-pager', '-C'], true)) {
        $rest = array_slice($rest, $rest[0] === '-C' ? 2 : 1);
    }

    $subcommand = $rest[0] ?? null;
    $arguments = array_slice($rest, 1);

    if (isset(READ_ONLY_GIT_USES[$subcommand])) {
        // A bare `git branch` lists the branches; a bare `git worktree` only prints its usage.
        return ($arguments !== [] || $subcommand === 'branch') && array_diff($arguments, READ_ONLY_GIT_USES[$subcommand]) === [];
    }

    if (! in_array($subcommand, READ_ONLY_GIT, true)) {
        return false;
    }

    // Every word, also after `--`: that `--` may be an option's value
    // (`git grep -e -- -O rm`), and git then still reads what follows.
    foreach ($arguments as $word) {
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
    if ($short !== [] && preg_match('/^-(.+)/', $word, $cluster) === 1) {
        return array_intersect(str_split($cluster[1]), $short) !== [];
    }

    return false;
}
