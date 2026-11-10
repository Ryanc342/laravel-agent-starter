<?php

use Symfony\Component\Process\Process;

require_once __DIR__.'/../../Support/MilestoneWorkspace.php';

function acceptanceSession(string $repo, string $mode, string $session = 'next'): string
{
    $run = new Process([PHP_BINARY, 'scripts/hooks/finish-check.php', $mode], $repo);
    $run->setInput(json_encode(['cwd' => $repo, 'session_id' => $session]));
    $run->mustRun();

    return $run->getOutput();
}

function acceptanceCommit(string $repo): void
{
    (new Process(['git', 'add', '-A'], $repo))->mustRun();
    (new Process(['git', '-c', 'user.name=t', '-c', 'user.email=t@t', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null', 'commit', '-qm', 'milestone'], $repo))->mustRun();
}

function acceptancePlan(string $repo, bool $archived = true, string $result = 'pending'): void
{
    milestonePlan($repo, finished: true, archived: $archived);
    $folder = $archived ? 'completed' : 'active';
    $file = "{$repo}/docs/exec-plans/{$folder}/quotes.md";
    file_put_contents($file, str_replace('M1 affected tests passed.', "Final composer check {$result}; review round 1: 0 P0, 0 P1.", file_get_contents($file)));
}

test('finishing a plan in a later conversation requires a real full pass', function (bool $commit, bool $archive, string $text) {
    $repo = milestoneWorkspace();
    expect(milestoneRun($repo)->getExitCode())->toBe(0);
    if ($commit) {
        acceptanceCommit($repo);
    }
    acceptanceSession($repo, 'start');
    acceptancePlan($repo, $archive, $text);

    expect(acceptanceSession($repo, 'stop'))->toContain('Run composer check')->not->toContain('Run composer check:milestone');
})->with([
    'committed active' => [true, false, 'pending'],
    'uncommitted active' => [false, false, 'pending'],
    'committed archive pending' => [true, true, 'pending'],
    'uncommitted archive pending' => [false, true, 'pending'],
    'committed archive failed' => [true, true, 'failed'],
    'uncommitted archive failed' => [false, true, 'failed'],
    'committed archive claimed passed' => [true, true, 'passed'],
    'uncommitted archive claimed passed' => [false, true, 'passed'],
]);

test('a full pass carries across conversations only while the code stays unchanged', function (bool $editBeforeStart) {
    $repo = milestoneWorkspace();
    expect(milestoneFullRun($repo)->getExitCode())->toBe(0);
    if ($editBeforeStart) {
        file_put_contents("{$repo}/app/quote.php", "<?php\n\$newCode = true;\n");
    }
    acceptanceSession($repo, 'start');
    acceptancePlan($repo, result: 'passed');
    $stop = acceptanceSession($repo, 'stop');

    if ($editBeforeStart) {
        expect($stop)->toContain('Run composer check');
    } else {
        expect($stop)->toBe('');
    }
})->with(['unchanged' => [false], 'changed earlier' => [true]]);

test('an unvalidated archive remains pending in the next conversation', function () {
    $repo = milestoneWorkspace();
    expect(milestoneRun($repo)->getExitCode())->toBe(0);
    acceptancePlan($repo);
    expect(milestoneHook($repo, 'stop'))->toContain('Run composer check');
    acceptanceCommit($repo);
    acceptanceSession($repo, 'start');
    file_put_contents("{$repo}/docs/exec-plans/completed/quotes.md", "\nRecord updated.\n", FILE_APPEND);

    expect(acceptanceSession($repo, 'stop'))->toContain('Run composer check');
    expect(milestoneFullRun($repo)->getExitCode())->toBe(0)
        ->and(acceptanceSession($repo, 'stop'))->toBe('');
});

test('a real full pass can finish an active plan in a later conversation', function () {
    $repo = milestoneWorkspace();
    expect(milestoneFullRun($repo)->getExitCode())->toBe(0);
    acceptanceSession($repo, 'start');
    acceptancePlan($repo, archived: false, result: 'passed');

    expect(acceptanceSession($repo, 'stop'))->toContain('move the plan to docs/exec-plans/completed/')->not->toContain('Run composer check');
});

test('ordinary document work and historical record updates are not new delivery', function (string $change) {
    $repo = milestoneWorkspace();
    if ($change === 'historical record') {
        acceptancePlan($repo, result: 'passed');
        acceptanceCommit($repo);
    }
    acceptanceSession($repo, 'start');
    match ($change) {
        'read only' => null,
        'document' => file_put_contents("{$repo}/docs/notes.md", 'Document wording updated.'),
        'draft plan' => file_put_contents("{$repo}/docs/exec-plans/active/quotes.md", str_replace('- [x] M1', '- [ ] M1', file_get_contents("{$repo}/docs/exec-plans/active/quotes.md"))),
        'intermediate progress' => file_put_contents("{$repo}/docs/exec-plans/active/quotes.md", "\nProgress note.\n", FILE_APPEND),
        'historical record' => file_put_contents("{$repo}/docs/exec-plans/completed/quotes.md", "\nCorrected the report wording.\n", FILE_APPEND),
    };

    expect(acceptanceSession($repo, 'stop'))->toBe('');
})->with(['read only', 'document', 'draft plan', 'intermediate progress', 'historical record']);
