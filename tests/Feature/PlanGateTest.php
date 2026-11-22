<?php

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->project = sys_get_temp_dir().'/plan-gate-'.uniqid();
    File::ensureDirectoryExists($this->project.'/docs/plans');
    Process::path($this->project)->run(['git', 'init', '--quiet'])->throw();
    File::put($this->project.'/docs/plans/2026-01-01-0900-feature.md', "# Feature\n\n## 目标\n\nSomething.\n");
});

afterEach(function () {
    File::deleteDirectory($this->project);
});

/**
 * Runs the plan gate the way Claude Code does: the event as argument, the hook input as JSON on stdin.
 *
 * @param  array<string, mixed>  $input
 */
function runPlanGate(string $project, string $event, array $input): ProcessResult
{
    return Process::path($project)
        ->env(['CLAUDE_PROJECT_DIR' => $project])
        ->input((string) json_encode($input))
        ->run(['php', base_path('.claude/hooks/plan-gate.php'), $event]);
}

function runBashThroughPlanGate(string $project, string $command): ProcessResult
{
    return runPlanGate($project, 'pre-tool', ['tool_name' => 'Bash', 'tool_input' => ['command' => $command]]);
}

test('without an approved plan, read-only git commands and committing still run', function (string $command) {
    expect(runBashThroughPlanGate($this->project, $command)->exitCode())->toBe(0);
})->with([
    'git status',
    'git diff HEAD~1',
    'git log --oneline',
    'git grep -n foo',
    'git add -A',
    'git commit -m "First line" -m "Second line"',
]);

test('without an approved plan, any other command is stopped', function (string $command) {
    $result = runBashThroughPlanGate($this->project, $command);

    expect($result->exitCode())->toBe(2)
        ->and($result->errorOutput())->toContain('没有已批准的计划')
        ->toContain('多个 -m');
})->with([
    'sed -i' => "sed -i '' 's/a/b/' README.md",
    'redirect' => 'echo x > a',
    'git checkout' => 'git checkout .',
    'chained with &&' => 'git status && rm a',
    'diff to a file' => 'git diff --output=a',
    'grep opening a program' => 'git grep -O vim foo',
    'grep opening a pager' => 'git grep --open-files-in-pager=vim foo',
    'newline' => "git status\nrm a",
    'process substitution' => 'git diff <(rm a)',
    'amend' => 'git commit --amend -m a',
    'push' => 'git push',
]);

test('without an approved plan, Boost tinker and record-rule are stopped', function (string $tool) {
    expect(runPlanGate($this->project, 'pre-tool', ['tool_name' => $tool, 'tool_input' => []])->exitCode())->toBe(2);
})->with(['mcp__laravel-boost__tinker', 'mcp__laravel-boost__record-rule']);

test('without an approved plan, only files in docs/plans may be written', function () {
    $write = fn (string $path) => runPlanGate($this->project, 'pre-tool', [
        'tool_name' => 'Write',
        'tool_input' => ['file_path' => $this->project.'/'.$path],
    ])->exitCode();

    expect($write('docs/plans/2026-01-02-0900-next.md'))->toBe(0)
        ->and($write('README.md'))->toBe(2);
});

test('once the user says 开始, the plan is approved and any command or tool runs', function () {
    $approval = runPlanGate($this->project, 'user-prompt', ['prompt' => '开始']);

    expect($approval->output())->toContain('用户批准了计划')
        ->and(runBashThroughPlanGate($this->project, "sed -i '' 's/a/b/' README.md")->exitCode())->toBe(0)
        ->and(runPlanGate($this->project, 'pre-tool', ['tool_name' => 'mcp__laravel-boost__tinker', 'tool_input' => []])->exitCode())->toBe(0);
});

test('开始 does not approve a plan that already has its result, and says to write a new one', function () {
    File::append($this->project.'/docs/plans/2026-01-01-0900-feature.md', "\n## 结果\n\nDone.\n");

    $approval = runPlanGate($this->project, 'user-prompt', ['prompt' => '开始']);

    expect($approval->output())->toContain('已经关闭')
        ->toContain('新计划')
        ->not->toContain('用户批准了计划')
        ->and(runBashThroughPlanGate($this->project, "sed -i '' 's/a/b/' README.md")->exitCode())->toBe(2);
});
