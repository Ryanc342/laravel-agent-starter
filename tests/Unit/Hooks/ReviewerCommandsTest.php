<?php

use Symfony\Component\Process\Process;

/*
| scripts/hooks/reviewer-commands.php, run the way Claude Code's PreToolUse hook
| runs it before a Bash call: a reviewer subagent may run every command that only
| reads (search, viewing files, git commands that read); a write, a redirection,
| a substitution and the network are refused before they run and written to the
| refusal log in the state folder. The builder's own commands and other
| subagents are never looked at.
*/

/**
 * A throwaway git repository, so the script finds a state folder of its own.
 */
function reviewerCommandsRepo(): string
{
    $repo = sys_get_temp_dir().'/ezdev-reviewer-commands-'.bin2hex(random_bytes(6));
    mkdir($repo, 0777, true);
    (new Process(['git', 'init', '-q', '-b', 'main'], $repo))->mustRun();
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $repo]))->run());

    return $repo;
}

/**
 * Runs the hook for one Bash call and returns what it printed.
 */
function reviewerCommandsRun(string $repo, string $command, ?string $agent): string
{
    $input = ['session_id' => 'test', 'cwd' => $repo, 'hook_event_name' => 'PreToolUse', 'tool_name' => 'Bash', 'tool_input' => ['command' => $command]];

    if ($agent !== null) {
        $input += ['agent_id' => 'a1', 'agent_type' => $agent];
    }

    $process = new Process(['php', dirname(__DIR__, 3).'/scripts/hooks/reviewer-commands.php'], $repo);
    $process->setInput(json_encode($input, JSON_THROW_ON_ERROR));
    $process->mustRun();

    return $process->getOutput();
}

/**
 * @return list<array<string, mixed>>
 */
function reviewerCommandsLog(string $repo): array
{
    $file = "{$repo}/.git/ezdev/reviewer-refusals.jsonl";

    return is_file($file) ? array_map(fn (string $line): array => json_decode($line, true), file($file, FILE_IGNORE_NEW_LINES) ?: []) : [];
}

test('a reviewer may run every command that only reads, alone, joined or piped', function (string $command) {
    $repo = reviewerCommandsRepo();

    foreach (['reviewer-opus', 'reviewer-haiku'] as $reviewer) {
        expect(reviewerCommandsRun($repo, $command, $reviewer))->toBe('');
    }

    expect(reviewerCommandsLog($repo))->toBe([]);
})->with([
    'a diff' => ['git diff HEAD~1'],
    'a file at a commit, cut' => ['git show 1a2b3c4:app/Models/Task.php | head -50'],
    'two commands' => ['git log --oneline -5 && git status'],
    'a separator and filters' => ["git diff --stat main; git log --format='%H %s' | grep -i task | sort | uniq -c | wc -l"],
    'lines of a file at a commit' => ["git show HEAD:docs/workflow.md | sed -n '10,40p'"],
    'the commit that added a plan' => ['git log --diff-filter=A --format=%H -- docs/exec-plans/active/quotes.md | tail -1'],
    'options that only look like a dangerous one' => ['git log --oneline --no-ext-diff | sort -rn | grep -io task'],
    'only the matching part of a line' => ['git grep -o -n task'],
    'git without a pager' => ['git --no-pager log -5'],
    'an escaped dollar in double quotes' => ['git log --grep="\\$HOME"'],
    'a tab between words' => ["git log\t-5"],
    'the last line, and up to the last line' => ['git log | sed -n \'$p\' && git log | sed -n \'10,$p\''],
    'every git command that reads' => ['git ls-files && git ls-tree HEAD && git rev-parse HEAD && git rev-list -1 HEAD && git merge-base HEAD main && git cat-file -t HEAD && git blame app/Models/Task.php && git describe --always'],
    'a path with a dash' => ['git ls-files docs/exec-plans/Overview.md'],
    'every filter' => ['git log | cut -c1-80 | tr a-z A-Z | cat | head -5'],
    'a file' => ['cat .env'],
    'files with a separator line between them' => ['cat docs/prompts/review.md && echo ---- && cat ARCHITECTURE.md | head -150'],
    'a search of the files' => ['grep -rn "Accepted:" docs/exec-plans/ docs/QUALITY.md | head -20'],
    'a search with ripgrep' => ['rg -n "function \\w+\\(" app/Enums app/Models'],
    'the lines from a heading to the end' => ["sed -n '/Review lanes/,\$p' docs/golden-principles.md"],
    'the lines between two headings' => ["sed -n '/^## 4/,/^## 5/p' docs/workflow.md"],
    'lines of a file' => ['git log | sed -n 10p x'],
    'another folder, then git' => ['cd /work/app-review-opus && git status --short'],
    'git in another folder' => ['git -C /work/app-review-opus log --oneline -3'],
    'the folders and files' => ['ls -la docs && wc -l AGENTS.md && find docs -name "*.md" -type f | sort'],
    'the worktrees and the branches' => ['git worktree list && git branch && git branch -a'],
    'two files compared' => ['diff docs/workflow.md docs/PLANS.md'],
    'counted lines of a file' => ['git log | uniq -c x'],
    'the last commits of a branch' => ['git shortlog -sn && git for-each-ref --format="%(refname)" refs/heads && git diff-tree --name-only -r HEAD'],
]);

test('a reviewer is refused every command that could write, with the reason and a log line', function (string $command) {
    $repo = reviewerCommandsRepo();

    foreach (['reviewer-opus', 'reviewer-haiku'] as $count => $reviewer) {
        $output = json_decode(reviewerCommandsRun($repo, $command, $reviewer), true);

        expect($output['hookSpecificOutput']['hookEventName'] ?? null)->toBe('PreToolUse')
            ->and($output['hookSpecificOutput']['permissionDecision'] ?? null)->toBe('deny')
            ->and($output['hookSpecificOutput']['permissionDecisionReason'] ?? '')->toContain('git diff')->toContain('Read, Grep and Glob')
            ->and(reviewerCommandsLog($repo))->toHaveCount($count + 1)
            ->and(reviewerCommandsLog($repo)[$count])->toMatchArray(['reviewer' => $reviewer, 'command' => $command])
            ->and(reviewerCommandsLog($repo)[$count]['time'] ?? null)->toBeString();
    }
})->with([
    'a redirection to a file' => ['git show 1a2b3c4:app/Models/Task.php > /tmp/x'],
    'a copy' => ['cp app/Models/Task.php /tmp/x'],
    'a removal' => ['rm -rf storage'],
    'php' => ["php -r 'file_put_contents(\"x\", 1);'"],
    'git diff --output' => ['git diff --output=x'],
    'sed -i' => ["sed -i 's/a/b/' app/Models/Task.php"],
    'tee' => ['echo x | tee y'],
    'a command substitution' => ['git show $(git rev-parse HEAD)'],
    'backticks' => ['git show `git rev-parse HEAD`'],
    'a process substitution' => ['git diff <(git show HEAD:x)'],
    'git checkout' => ['git checkout .'],
    'git stash' => ['git stash'],
    'xargs' => ['git ls-files | xargs rm'],
    'awk' => ["git log | awk '{print > \"x\"}'"],
    'sort -o' => ['git log | sort -o x'],
    'a sed script that writes' => ["git log | sed -n 'w x'"],
    'git with a config option' => ["git -c core.pager='rm x' log"],
    'a pipe into a filter that is no filter' => ['git log | sh'],
    'an appending redirection' => ['git log >> notes.txt'],
    'a background job' => ['git log & rm x'],
    'git grep -O' => ['git grep -O x'],
    'git grep --open-files-in-pager' => ['git grep --open-files-in-pager=rm x'],
    'a prefix of --open-files-in-pager' => ['git grep --open-files=rm x'],
    'a short prefix of --open-files-in-pager' => ['git grep --open=rm x'],
    '-O inside a cluster of short options' => ['git grep -iOrm x'],
    'git diff --ext-diff' => ['git diff --ext-diff'],
    'a prefix of --output' => ['git log --out=x'],
    'sort --compress-program' => ['git log | sort --compress-program=rm'],
    'a prefix of --compress-program' => ['git log | sort --compress=rm'],
    '-o inside a cluster of sort options' => ['git log | sort -ro x'],
    'uniq with a file it writes' => ['git log | uniq - out'],
    '-O after a number in a cluster' => ['git grep -1Orm x'],
    '-O after a letter and a number in a cluster' => ['git grep -i1Orm x'],
    'sort --output' => ['git log | sort --output=x'],
    'a prefix of sort --output' => ['git log | sort --out=x'],
    '-o after a number in a sort cluster' => ['git log | sort -S1o x'],
    '-O after a -- that is an option value' => ['git grep -e -- -Orm -e x'],
    '--output after a -- that is an option value' => ['git log --grep -- --output=x'],
    'a path after -- that looks like a dangerous option' => ['git grep -n task -- --open-files-in-pager'],
    'a new line' => ["git status \nrm x"],
    'an or' => ['git show x || rm x'],
    'a brace expansion' => ['git log {--output=x,}'],
    'a variable' => ['git log --output$IFS=x'],
    'a variable inside double quotes' => ['git log --grep="$HOME"'],
    'backticks inside double quotes' => ['git log --grep="`rm x`"'],
    'an unclosed single quote' => ["git log --grep='x"],
    'an unclosed double quote' => ['git log --grep="x'],
    'a backslash at the end' => ['git log \\'],
    'a glob' => ['git show HEAD:*'],
    'a history expansion' => ['git log !x'],
    'sed without -n' => ['git log | sed -e 10p'],
    'an opening parenthesis' => ['git log (x'],
    'a closing parenthesis' => ['git log x)'],
    'a closing brace' => ['git log x}'],
    'a question mark glob' => ['git show HEAD:?'],
    'an opening bracket' => ['git show HEAD:['],
    'a closing bracket' => ['git show HEAD:]'],
    'a carriage return' => ["git status \rrm x"],
    'an input redirection' => ['git log < x'],
    'an or into a filter' => ['git log || head'],
    'an opening brace' => ['git log {x'],
    'a backslash before a new line' => ["git log \\\n--output=x"],
    'an empty command' => [''],
    'a blank command' => ['   '],
    'a pipe with nothing after it' => ['git log |'],
    'a pipe with nothing before it' => ['| head'],
    'an empty stage between two pipes' => ['git log | | head'],
    'a separator with nothing before it' => ['; git log'],
    'a second command that writes' => ['git log ; rm x'],
    'another program with a git word' => ['rm show'],
    'an option with its value in single quotes' => ["git diff --output='x'"],
    'an option with its value in double quotes' => ['git diff --output="x"'],
    'an option with an escaped character' => ['git diff --outpu\\t=x'],
    'a trailing pipe after a separator' => ['git status; git log |'],
    'a whole option in double quotes' => ['git log "--output=x"'],
    'a sed write after the line range' => ["git log | sed -n '10p;w notes'"],
    'a sed write before the line range' => ["git log | sed -n 'w notes;10p'"],
    'a sed command that runs the line' => ['git log | sed -n 10e'],
    'a write option just before a separator' => ['git log --output=x; git status'],
    'find that deletes' => ['find storage -name "*.log" -delete'],
    'find that runs a program' => ['find . -name x -exec rm {} ;'],
    'find that writes its list' => ['find . -fprint list'],
    'ripgrep with a preprocessor' => ['rg --pre rm x'],
    'a download' => ['curl https://example.com'],
    'git fetch' => ['git fetch origin'],
    'git push' => ['git push'],
    'a branch deleted' => ['git branch -D main'],
    'a worktree removed' => ['git worktree remove ../x'],
    'git -C before a command that writes' => ['git -C ../x checkout .'],
    'cd with more than a folder' => ['cd x y'],
    'uniq with two files' => ['uniq a b'],
    'a sed write after a pattern range' => ["sed -n '/a/,/b/w notes'"],
]);

test('a reviewer call without a command is let through and not logged', function () {
    $repo = reviewerCommandsRepo();
    $process = new Process(['php', dirname(__DIR__, 3).'/scripts/hooks/reviewer-commands.php'], $repo);
    $process->setInput(json_encode(['cwd' => $repo, 'tool_name' => 'Bash', 'tool_input' => [], 'agent_id' => 'a1', 'agent_type' => 'reviewer-opus'], JSON_THROW_ON_ERROR));
    $process->mustRun();

    expect($process->getOutput())->toBe('')
        ->and(reviewerCommandsLog($repo))->toBe([]);
});

test('the refusal is logged in the repository the call came from, wherever the hook runs', function () {
    $repo = reviewerCommandsRepo();
    $process = new Process(['php', dirname(__DIR__, 3).'/scripts/hooks/reviewer-commands.php'], sys_get_temp_dir());
    $process->setInput(json_encode(['cwd' => $repo, 'tool_name' => 'Bash', 'tool_input' => ['command' => 'rm x'], 'agent_id' => 'a1', 'agent_type' => 'reviewer-opus'], JSON_THROW_ON_ERROR));
    $process->mustRun();

    expect(reviewerCommandsLog($repo))->toHaveCount(1);
});

test('the builder and other subagents are never looked at', function (?string $agent) {
    $repo = reviewerCommandsRepo();

    expect(reviewerCommandsRun($repo, 'rm -rf storage > /tmp/x', $agent))->toBe('')
        ->and(reviewerCommandsLog($repo))->toBe([]);
})->with([
    'the builder' => [null],
    'explorer' => ['explorer'],
]);

test('Claude Code runs the command check before every Bash call', function () {
    $hooks = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/.claude/settings.json'), true)['hooks']['PreToolUse'] ?? [];
    $bash = array_values(array_filter($hooks, fn (array $hook): bool => ($hook['matcher'] ?? null) === 'Bash'));

    expect($bash)->toHaveCount(1)
        ->and($bash[0]['hooks'][0]['command'])->toContain('scripts/hooks/reviewer-commands.php');
});
