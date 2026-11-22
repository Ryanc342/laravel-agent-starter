<?php

/*
 * 计划闸门（Claude Code 钩子，在 .claude/settings.json 里登记）。
 *   user-prompt：你发的消息是「开始」，就批准 docs/plans/ 里最新的计划
 *   pre-tool：AI 写文件之前，没有已批准的计划就拦下（docs/plans/ 除外）
 * 批准认的是计划当时的内容：计划之后被改过（追加需求、写上结果）
 * 或者有了更新的计划，批准就自动作废。
 */

$event = $argv[1] ?? '';
$input = json_decode((string) stream_get_contents(STDIN), true);
$input = is_array($input) ? $input : [];
$root = rtrim((string) (getenv('CLAUDE_PROJECT_DIR') ?: getcwd()), '/');

$plans = glob($root.'/docs/plans/*.md') ?: [];
sort($plans);
$latest = end($plans) ?: null;
$current = $latest === null ? null : basename($latest).' '.md5_file($latest);

$gitPath = trim((string) shell_exec('cd '.escapeshellarg($root).' && git rev-parse --git-path claude-approved-plan'));
$marker = $gitPath === '' ? null : (str_starts_with($gitPath, '/') ? $gitPath : $root.'/'.$gitPath);
$approved = $marker !== null && is_file($marker) ? trim((string) file_get_contents($marker)) : null;

if ($event === 'user-prompt') {
    if (preg_match('/^\s*(?:好的?|ok|okay)?\s*(?:开始|start)\s*吧?[\s\p{P}]*$/iu', (string) ($input['prompt'] ?? '')) !== 1) {
        exit(0);
    }
    if ($current === null || $marker === null) {
        echo '用户说了「开始」，但 docs/plans/ 里还没有计划（或项目不是 git 仓库）：先 grill，再写计划。';
        exit(0);
    }
    file_put_contents($marker, $current);
    echo '用户批准了计划 docs/plans/'.basename($latest).'，现在可以按计划改文件。';
    exit(0);
}

if ($event === 'pre-tool') {
    $path = (string) ($input['tool_input']['file_path'] ?? $input['tool_input']['notebook_path'] ?? '');
    if ($path === '' || ! str_starts_with($path, $root.'/') || str_starts_with($path, $root.'/docs/plans/')) {
        exit(0);
    }
    if ($current !== null && $approved === $current) {
        exit(0);
    }
    fwrite(STDERR, '还不能改 '.substr($path, strlen($root) + 1).'：没有已批准的计划。按 AGENTS.md 先 grill 用户，把计划写进 docs/plans/，等用户说「开始」。计划批准后又被改过，也要用户再说一次「开始」。');
    exit(2);
}
