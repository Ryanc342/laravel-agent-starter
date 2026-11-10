<?php

require_once __DIR__.'/../../Support/MilestoneWorkspace.php';

test('milestone validation runs all static checks and the selected PHP and frontend scopes without granting final acceptance', function () {
    $repo = milestoneWorkspace();
    $run = milestoneRun($repo, ['tests/Feature', 'tests/js/quote.test.ts']);

    expect($run->getExitCode())->toBe(0)
        ->and(is_file("{$repo}/.git/ezdev/checked"))->toBeFalse()
        ->and(milestoneHook($repo, 'stop'))->toBe('');
    $commands = array_map(fn (string $line) => json_decode($line, true), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES));
    expect($commands)->toBe([
        ['static', ['check:static']],
        ['php', ['test', '--compact', '--fail-on-empty-test-suite', 'tests/Feature']],
        ['js', ['test', '--', 'tests/js/quote.test.ts']],
    ]);

    milestonePlan($repo, finished: true);
    expect(milestoneHook($repo, 'stop'))->toContain('Run composer check')->not->toContain('Run composer check:milestone');
});

test('milestone validation rejects an empty or invalid scope before executing checks', function (array $paths) {
    $repo = milestoneWorkspace();
    mkdir("{$repo}/tests/Feature/Empty");
    $run = milestoneRun($repo, $paths);

    expect($run->getExitCode())->toBe(1)
        ->and(is_file("{$repo}/runtime/commands"))->toBeFalse()
        ->and(milestoneHook($repo, 'stop'))->toContain('Run composer check:milestone');
})->with([
    'no paths' => [[]],
    'missing file' => [['tests/Feature/MissingTest.php']],
    'not a test' => [['app/quote.php']],
    'empty directory' => [['tests/Feature/Empty']],
    'runner option' => [['--filter=Quote']],
    'parent traversal' => [['tests/Feature/../../app']],
]);

test('a failed milestone command stops the run and invalidates an earlier partial pass', function (string $phase) {
    $repo = milestoneWorkspace();
    $paths = ['tests/Feature', 'tests/js'];
    expect(milestoneRun($repo, $paths)->getExitCode())->toBe(0);
    file_put_contents("{$repo}/runtime/commands", '');
    $run = milestoneRun($repo, $paths, ['MILESTONE_TEST_FAIL' => $phase]);
    $commands = file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES);

    expect($run->getExitCode())->toBe(7)
        ->and($commands)->toHaveCount(array_search($phase, ['static', 'php', 'js'], true) + 1)
        ->and(milestoneHook($repo, 'stop'))->toContain('Run composer check:milestone');
})->with(['static', 'php', 'js']);

test('a change during or after milestone validation requires a new check', function (bool $during) {
    $repo = milestoneWorkspace();
    $run = milestoneRun($repo, env: $during ? ['MILESTONE_TEST_EDIT' => 'php'] : []);
    if (! $during) {
        file_put_contents("{$repo}/app/quote.php", "<?php\n\$edited = true;\n");
    }

    expect($run->getExitCode())->toBe($during ? 1 : 0)
        ->and(milestoneHook($repo, 'stop'))->toContain('Run composer check:milestone');
})->with(['during' => [true], 'after' => [false]]);

test('a partial pass cannot replace final validation without an incomplete valid active plan', function (string $state) {
    $repo = milestoneWorkspace();
    expect(milestoneRun($repo)->getExitCode())->toBe(0);
    match ($state) {
        'archived' => milestonePlan($repo, finished: true, archived: true),
        'missing' => unlink("{$repo}/docs/exec-plans/active/quotes.md"),
        'invalid' => file_put_contents("{$repo}/docs/exec-plans/active/quotes.md", "# Empty\n## Milestones\n- [ ] M1 Build\n"),
    };

    expect(milestoneHook($repo, 'stop'))->toContain('Run composer check')->not->toContain('Run composer check:milestone');
})->with(['archived', 'missing', 'invalid']);

test('partial validation preserves protected-file approval requirements', function () {
    $repo = milestoneWorkspace();
    file_put_contents("{$repo}/composer.json", '{}');
    expect(milestoneRun($repo)->getExitCode())->toBe(0)
        ->and(milestoneHook($repo, 'stop'))->toContain('You changed composer.json');
});

test('a milestone result in an archived plan is not a recorded full check result', function () {
    $repo = milestoneWorkspace();
    milestonePlan($repo, finished: true, archived: true);
    $path = "{$repo}/docs/exec-plans/completed/quotes.md";
    file_put_contents($path, str_replace('M1 affected tests passed.', 'composer check:milestone passed; review round 1: 0 P0, 0 P1.', file_get_contents($path)));

    expect(milestoneHook($repo, 'stop'))->toContain('no composer check result in Progress');
});

test('a frontend-only milestone runs no backend suite', function () {
    $repo = milestoneWorkspace();
    expect(milestoneRun($repo, ['tests/js'])->getExitCode())->toBe(0);
    $commands = array_map(fn (string $line) => json_decode($line, true), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES));

    expect(array_column($commands, 0))->toBe(['static', 'js']);
});

test('a PHP scope with no discovered tests cannot record a milestone pass', function () {
    $repo = milestoneWorkspace();
    // A real configuration avoids Pest's generated-config path when PHP assertions are disabled in CI.
    file_put_contents("{$repo}/phpunit.xml", '<phpunit bootstrap="vendor/autoload.php"/>');
    $artisan = <<<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';
$process = new Symfony\Component\Process\Process([
    PHP_BINARY, '-d', 'zend.assertions=-1', PEST_BINARY, '--configuration', __DIR__.'/phpunit.xml', ...array_slice($argv, 2),
]);
exit($process->run(function ($type, $output) { echo $output; }));
PHP;
    file_put_contents("{$repo}/artisan", str_replace('PEST_BINARY', var_export(dirname(__DIR__, 3).'/vendor/bin/pest', true), $artisan));
    $run = milestoneRun($repo);

    expect($run->getExitCode())->toBe(1, $run->getOutput().$run->getErrorOutput())
        ->and(is_file("{$repo}/.git/ezdev/milestone-checked"))->toBeFalse();
});

test('an invalid milestone retry revokes the previous partial pass', function (array $paths) {
    $repo = milestoneWorkspace();
    expect(milestoneRun($repo)->getExitCode())->toBe(0);
    mkdir("{$repo}/tests/Feature/Empty");

    $run = milestoneRun($repo, $paths);

    expect($run->getExitCode())->toBe(1)
        ->and(is_file("{$repo}/.git/ezdev/milestone-checked"))->toBeFalse()
        ->and(milestoneHook($repo, 'stop'))->toContain('Run composer check:milestone');
})->with([
    'no paths' => [[]],
    'missing file' => [['tests/Feature/MissingTest.php']],
    'not a test' => [['app/quote.php']],
    'empty directory' => [['tests/Feature/Empty']],
    'runner option' => [['--filter=Quote']],
    'parent traversal' => [['tests/Feature/../../app']],
]);

test('a failed retry cannot borrow a prior full or partial pass', function (bool $fullBefore, bool $fullRetry) {
    $repo = milestoneWorkspace();
    file_put_contents("{$repo}/.git/info/exclude", "/storage/\n");
    mkdir("{$repo}/storage/framework/testing", 0777, true);
    if ($fullBefore) {
        expect(milestoneFullRun($repo)->getExitCode())->toBe(0);
    } else {
        expect(milestoneRun($repo)->getExitCode())->toBe(0);
    }
    expect(milestoneHook($repo, 'stop'))->toBe('');

    if ($fullRetry) {
        $run = milestoneFullRun($repo, ['MILESTONE_TEST_FAIL' => 'static']);
    } else {
        $run = milestoneRun($repo, env: ['MILESTONE_TEST_FAIL' => 'static']);
    }

    expect($run->getExitCode())->toBe(7)
        ->and(is_file("{$repo}/.git/ezdev/checked"))->toBeFalse()
        ->and(is_file("{$repo}/.git/ezdev/milestone-checked"))->toBeFalse()
        ->and(milestoneHook($repo, 'stop'))->toContain('Run composer check:milestone');
    expect(milestoneRun($repo)->getExitCode())->toBe(0)
        ->and(milestoneHook($repo, 'stop'))->toBe('');
})->with([
    'partial then full' => [false, true],
    'full then full' => [true, true],
    'full then partial' => [true, false],
]);
