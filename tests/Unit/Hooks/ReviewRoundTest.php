<?php

use Symfony\Component\Process\Process;

require_once dirname(__DIR__, 2).'/Support/Workspace.php';

/*
| Before a review round (docs/workflow.md, step 4) the builder runs
| `php scripts/hooks/finish-check.php round <plan>`: reviewers have no shell, so
| it writes the plan's diff and a copy of the plan for them. Each test runs it
| in a throwaway git repository.
*/

const ROUND_CODE = "<?php\n\n\$a = 1;\n\$b = 2;\n\$c = 3;\n";

function roundRepo(): string
{
    $repo = workspaceWithFinishCheck('review-round');
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n");
    workspaceWrite($repo, 'docs/exec-plans/active/.gitkeep', '');
    workspaceWrite($repo, 'storage/framework/testing/.gitignore', "*\n!.gitignore\n");
    workspaceGitInit($repo);

    return $repo;
}

function roundPlan(string $milestones): string
{
    return "# Send quotes\n\n## Purpose\nSales staff send a quote.\n\n## Agreed decisions\n1. A quote is sent as a PDF.\n\n## Milestones\n{$milestones}\n\n## Progress\n\n## Decision Log\n\n## Outcomes & Retrospective\n";
}

/**
 * The two files a round prints, by their label: ['Diff' => <path>, 'Plan' => <path>].
 *
 * @return array<string, string>
 */
function roundFiles(Process $process): array
{
    preg_match_all('/^(Diff|Plan): (\S+)$/m', $process->getOutput(), $match);

    return array_combine($match[1], $match[2]);
}

test('a round writes the plan\'s diff and a copy of the plan for the reviewers, and prints both paths', function () {
    $repo = roundRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/Before.php', ROUND_CODE);
    workspaceCommit($repo);
    workspaceWrite($repo, 'docs/exec-plans/active/send-quotes.md', roundPlan('- [ ] M1 Actions'));
    workspaceCommit($repo);
    workspaceWrite($repo, 'app/Actions/Quotes/AcceptQuote.php', ROUND_CODE);
    workspaceWrite($repo, 'docs/exec-plans/active/send-quotes.md', roundPlan('- [x] M1 Actions'));
    workspaceCommit($repo);
    workspaceWrite($repo, 'app/Actions/Quotes/RejectQuote.php', "<?php\n\n\$rejected = true;\n");

    $process = workspaceFinishCheck($repo, ['round', 'docs/exec-plans/active/send-quotes.md']);
    $files = roundFiles($process);

    expect($process->getExitCode())->toBe(0)
        ->and($files)->toBe([
            'Diff' => 'storage/framework/testing/ezdev-review/send-quotes.diff',
            'Plan' => 'storage/framework/testing/ezdev-review/send-quotes.md',
        ]);

    $diff = (string) file_get_contents("{$repo}/{$files['Diff']}");

    expect($diff)->toContain('+++ b/app/Actions/Quotes/AcceptQuote.php')
        ->toContain('+++ b/docs/exec-plans/active/send-quotes.md')
        ->toContain('+++ b/app/Actions/Quotes/RejectQuote.php')
        ->toContain('+$rejected = true;')
        ->not->toContain('Before.php')
        ->and(file_get_contents("{$repo}/{$files['Plan']}"))->toBe(roundPlan('- [x] M1 Actions'));
});

test('a plan not yet committed diffs against HEAD', function () {
    $repo = roundRepo();
    workspaceWrite($repo, 'app/Actions/Quotes/SendQuote.php', "<?php\n\n\$sent = true;\n");
    workspaceWrite($repo, 'docs/exec-plans/active/send-quotes.md', roundPlan('- [ ] M1 Actions'));

    $files = roundFiles(workspaceFinishCheck($repo, ['round', 'docs/exec-plans/active/send-quotes.md']));

    expect(file_get_contents("{$repo}/{$files['Diff']}"))->toContain('+$sent = true;')->toContain('+++ b/docs/exec-plans/active/send-quotes.md');
});

test('a round leaves out of the diff the files that are as they are in the template commit a sync plan names', function () {
    $repo = roundRepo();
    $git = fn (string ...$args) => trim((new Process(['git', ...$args], $repo))->mustRun()->getOutput());
    $git('checkout', '-q', '-b', 'template');
    workspaceWrite($repo, 'app/Actions/Quotes/FromTemplate.php', ROUND_CODE);
    workspaceWrite($repo, 'resources/js/pages/quotes/index.tsx', "export {};\n");
    workspaceCommit($repo);
    $template = $git('rev-parse', 'HEAD');
    $git('checkout', '-q', 'main');
    workspaceWrite($repo, 'app/Actions/Quotes/FromTemplate.php', ROUND_CODE);
    workspaceWrite($repo, 'resources/js/pages/quotes/index.tsx', "export const changed = true;\n");
    workspaceWrite($repo, 'docs/exec-plans/active/sync-template.md', str_replace("## Milestones\n", "## Agreed decisions\n- Template: {$template}\n\n## Milestones\n", roundPlan('- [x] M1 Sync')));
    workspaceCommit($repo);

    $diff = (string) file_get_contents($repo.'/'.roundFiles(workspaceFinishCheck($repo, ['round', 'docs/exec-plans/active/sync-template.md']))['Diff']);

    expect($diff)->toContain('resources/js/pages/quotes/index.tsx')
        ->not->toContain('FromTemplate.php');
});

test('a round for a sync plan whose template commit is not in the repository fails and says so', function () {
    $repo = roundRepo();
    workspaceWrite($repo, 'docs/exec-plans/active/sync-template.md', str_replace("## Milestones\n", "## Agreed decisions\n- Template: 0123456789abcdef0123456789abcdef01234567\n\n## Milestones\n", roundPlan('- [x] M1 Sync')));
    workspaceCommit($repo);

    $process = workspaceFinishCheck($repo, ['round', 'docs/exec-plans/active/sync-template.md']);

    expect($process->getExitCode())->toBe(1)
        ->and($process->getErrorOutput())->toContain('0123456789abcdef0123456789abcdef01234567');
});

test('a round for a sync plan fails and says so when git refuses to read the template commit', function () {
    $repo = roundRepo();
    $git = fn (string ...$args) => trim((new Process(['git', ...$args], $repo))->mustRun()->getOutput());
    $template = $git('rev-parse', 'HEAD');
    workspaceWrite($repo, 'app/Actions/Quotes/FromTemplate.php', ROUND_CODE);
    workspaceWrite($repo, 'docs/exec-plans/active/sync-template.md', str_replace("## Milestones\n", "## Agreed decisions\n- Template: {$template}\n\n## Milestones\n", roundPlan('- [x] M1 Sync')));
    workspaceCommit($repo);
    // A git that refuses to read objects of the template commit, the way a broken object store does.
    $path = workspaceFakeGit($repo, "case \"\$*\" in *{$template}:*) echo 'fatal: bad object' >&2; exit 128;; esac");

    $process = workspaceFinishCheck($repo, ['round', 'docs/exec-plans/active/sync-template.md'], ['PATH' => $path]);

    expect($process->getExitCode())->toBe(1)
        ->and($process->getErrorOutput())->toContain('fatal: bad object');
});

test('a round takes an empty new file into the diff', function () {
    $repo = roundRepo();
    workspaceWrite($repo, 'docs/exec-plans/active/send-quotes.md', roundPlan('- [x] M1 Actions'));
    workspaceCommit($repo);
    workspaceWrite($repo, 'app/Actions/Quotes/.gitkeep', '');

    $process = workspaceFinishCheck($repo, ['round', 'docs/exec-plans/active/send-quotes.md']);

    expect($process->getExitCode())->toBe(0, $process->getErrorOutput())
        ->and(file_get_contents($repo.'/'.roundFiles($process)['Diff']))->toContain('app/Actions/Quotes/.gitkeep');
});

test('a round for a plan that does not exist writes nothing and fails', function () {
    $repo = roundRepo();
    $process = workspaceFinishCheck($repo, ['round', 'docs/exec-plans/active/missing.md']);

    expect($process->getExitCode())->toBe(1)
        ->and($process->getErrorOutput())->toContain('docs/exec-plans/active/missing.md')
        ->and(is_dir("{$repo}/storage/framework/testing/ezdev-review"))->toBeFalse();
});

test('the lanes of the finish check are the lanes of docs/golden-principles.md', function () {
    $root = dirname(__DIR__, 3);
    $doc = (string) file_get_contents("{$root}/docs/golden-principles.md");
    preg_match_all('/^\| [^|]+\| `(\w+)`\s*\| [^|]+\| ([^|]+)\|$/m', $doc, $rows, PREG_SET_ORDER);
    $lanes = [];

    foreach ($rows as [, $key, $places]) {
        $lanes[$key] = array_map('trim', explode(',', trim($places)));
    }

    $hook = json_decode(workspaceFinishCheck($root, ['lanes'])->getOutput(), true, flags: JSON_THROW_ON_ERROR);

    expect($lanes)->toBe($hook);
});

test('the two reviewers run on two different Claude models', function () {
    $models = [];

    foreach (glob(dirname(__DIR__, 3).'/.claude/agents/reviewer-*.md') ?: [] as $file) {
        preg_match('/^model:\s*(\S+)/m', (string) file_get_contents($file), $model);
        $models[basename($file, '.md')] = $model[1] ?? '';
    }

    expect($models)->toBe(['reviewer-haiku' => 'haiku', 'reviewer-opus' => 'opus']);
});
