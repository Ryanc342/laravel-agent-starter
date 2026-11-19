<?php

require_once __DIR__.'/../../Support/MilestoneWorkspace.php';

test('milestone validation runs all static checks and the selected PHP and frontend scopes, and its pass lets the AI stop', function () {
    $repo = milestoneWorkspace();
    expect(milestoneStop($repo))->toContain('composer check:milestone');

    $run = milestoneRun($repo, ['tests/Feature', 'tests/js/quote.test.ts']);

    expect($run->getExitCode())->toBe(0)
        ->and(milestoneStop($repo))->toBe('');
    $commands = array_map(fn (string $line) => json_decode($line, true), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES));
    expect($commands)->toBe([
        ['static', ['check:static']],
        ['php', ['test', '--compact', '--fail-on-empty-test-suite', 'tests/Feature']],
        ['js', ['test', '--', 'tests/js/quote.test.ts']],
    ]);
});

test('a full composer check runs the static checks and both test suites, and its pass lets the AI stop', function () {
    $repo = milestoneWorkspace();

    expect(milestoneFullRun($repo)->getExitCode())->toBe(0)
        ->and(milestoneStop($repo))->toBe('');
    $commands = array_map(fn (string $line) => json_decode($line, true), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES));
    expect(array_column($commands, 0))->toBe(['static', 'js', 'php']);
});

test('milestone validation rejects an empty or invalid scope before executing checks, and revokes an earlier pass', function (array $paths) {
    $repo = milestoneWorkspace();
    mkdir("{$repo}/tests/Feature/Empty");
    expect(milestoneRun($repo)->getExitCode())->toBe(0);
    unlink("{$repo}/runtime/commands");

    $run = milestoneRun($repo, $paths);

    expect($run->getExitCode())->toBe(1)
        ->and(is_file("{$repo}/runtime/commands"))->toBeFalse()
        ->and(milestoneStop($repo))->toContain('composer check:milestone');
})->with([
    'no paths' => [[]],
    'missing file' => [['tests/Feature/MissingTest.php']],
    'not a test' => [['app/quote.php']],
    'empty directory' => [['tests/Feature/Empty']],
    'runner option' => [['--filter=Quote']],
    'parent traversal' => [['tests/Feature/../../app']],
]);

test('a failed command stops the run and revokes an earlier pass', function (string $phase, bool $full) {
    $repo = milestoneWorkspace();
    $paths = ['tests/Feature', 'tests/js'];
    expect(milestoneRun($repo, $paths)->getExitCode())->toBe(0);
    file_put_contents("{$repo}/runtime/commands", '');

    $run = $full ? milestoneFullRun($repo, ['MILESTONE_TEST_FAIL' => $phase]) : milestoneRun($repo, $paths, ['MILESTONE_TEST_FAIL' => $phase]);
    $order = $full ? ['static', 'js', 'php'] : ['static', 'php', 'js'];

    expect($run->getExitCode())->toBe(7)
        ->and(file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES))->toHaveCount(array_search($phase, $order, true) + 1)
        ->and(milestoneStop($repo))->toContain('composer check:milestone');
})->with(['static', 'php', 'js'])->with(['milestone' => [false], 'full' => [true]]);

test('a change during or after the check needs a new check', function (bool $during) {
    $repo = milestoneWorkspace();
    $run = milestoneRun($repo, env: $during ? ['MILESTONE_TEST_EDIT' => 'php'] : []);
    if (! $during) {
        file_put_contents("{$repo}/app/quote.php", "<?php\n\$edited = true;\n");
    }

    expect($run->getExitCode())->toBe($during ? 1 : 0)
        ->and(milestoneStop($repo))->toContain('composer check:milestone');
})->with(['during' => [true], 'after' => [false]]);

test('a frontend-only milestone runs no backend suite', function () {
    $repo = milestoneWorkspace();
    expect(milestoneRun($repo, ['tests/js'])->getExitCode())->toBe(0);
    $commands = array_map(fn (string $line) => json_decode($line, true), file("{$repo}/runtime/commands", FILE_IGNORE_NEW_LINES));

    expect(array_column($commands, 0))->toBe(['static', 'js']);
});

test('a PHP scope with no discovered tests cannot record a pass', function () {
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
        ->and(milestoneStop($repo))->toContain('composer check:milestone');
});
