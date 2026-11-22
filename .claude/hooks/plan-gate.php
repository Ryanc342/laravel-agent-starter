<?php

/*
 * 计划闸门（Claude Code 钩子，在 .claude/settings.json 里登记）。
 *   user-prompt：你发的消息是「开始」，就批准 docs/plans/ 里最新的计划；
 *     这份计划已经写了「## 结果」（已关闭）时不批准，提醒 AI 先 grill、再写新计划
 *   pre-tool：没有已批准的计划时拦下
 *     - 写文件（docs/plans/ 和项目外的路径除外）
 *     - Bash（只放行 git 的 status、log、diff、show、blame、grep、ls-files 和 add、commit，
 *       命令里不能有 > < ; & | $( 反引号 换行 --output；git grep 不能 -O，git commit 不能 --amend）
 *     - Boost 的 tinker、record-rule
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
$isApproved = $current !== null && $approved === $current;

$howToWork = '按 AGENTS.md 先 grill 用户，把计划写进 docs/plans/，等用户说「开始」。计划批准后又被改过（包括写上结果），也要用户再说一次「开始」。'
    .'没有批准的计划时能用的：看代码用 Read、Grep、Glob，或者 git grep、git ls-files；git status、log、diff、show、blame；'
    .'查数据库用 Boost 的只读工具（database-query、database-schema）；路由看 routes/ 里的文件，Fortify 的登录、注册、重置密码、passkey 路由在 vendor/laravel/fortify/routes/routes.php；'
    .'提交用 git add、git commit（不能 --amend），提交信息用多个 -m，不要用 heredoc。命令里不能有 > < ; & | $( 反引号、换行、--output。';

/**
 * 没有批准的计划时，这条 Bash 命令能不能跑：只认 git 的只读命令和 add、commit。
 */
function isAllowedWithoutPlan(string $command): bool
{
    if (preg_match('/[<>;&|`\r\n]|\$\(/', $command) === 1) {
        return false;
    }
    if (preg_match('/^\s*git\s+(status|log|diff|show|blame|grep|ls-files|add|commit)(?:\s|$)/', $command, $match) !== 1) {
        return false;
    }

    // 去掉引号和反斜杠再查选项，'-O' 这样的写法也认得出；git 也认长选项的缩写（--out、--open、--am）。
    $words = ' '.str_replace(['"', "'", '\\'], '', $command);
    if (preg_match('/\s--out/', $words) === 1) {
        return false;
    }
    if ($match[1] === 'grep' && preg_match('/\s(?:-[a-zA-Z]*O|--op)/', $words) === 1) {
        return false;
    }
    if ($match[1] === 'commit' && preg_match('/\s--am/', $words) === 1) {
        return false;
    }

    return true;
}

if ($event === 'user-prompt') {
    if (preg_match('/^\s*(?:好的?|ok|okay)?\s*(?:开始|start)\s*吧?[\s\p{P}]*$/iu', (string) ($input['prompt'] ?? '')) !== 1) {
        exit(0);
    }
    if ($current === null || $marker === null) {
        echo '用户说了「开始」，但 docs/plans/ 里还没有计划（或项目不是 git 仓库）：先 grill，再写计划。';
        exit(0);
    }
    if (preg_match('/^## 结果\s*$/mu', (string) file_get_contents($latest)) === 1) {
        echo '用户说了「开始」，但最新的计划 docs/plans/'.basename($latest).' 已经写了结果，已经关闭，没有批准。不要动手：先 grill 用户，再写一份新计划，等用户说「开始」。';
        exit(0);
    }
    file_put_contents($marker, $current);
    echo '用户批准了计划 docs/plans/'.basename($latest).'，现在可以按计划改文件。';
    exit(0);
}

if ($event === 'pre-tool') {
    $tool = (string) ($input['tool_name'] ?? '');

    if ($tool === 'Bash') {
        $command = (string) ($input['tool_input']['command'] ?? '');
        if ($isApproved || isAllowedWithoutPlan($command)) {
            exit(0);
        }
        fwrite(STDERR, '还不能运行这条命令：没有已批准的计划。'.$howToWork);
        exit(2);
    }

    if (in_array($tool, ['mcp__laravel-boost__tinker', 'mcp__laravel-boost__record-rule'], true)) {
        if ($isApproved) {
            exit(0);
        }
        fwrite(STDERR, '还不能用 '.$tool.'：没有已批准的计划。'.$howToWork);
        exit(2);
    }

    $path = (string) ($input['tool_input']['file_path'] ?? $input['tool_input']['notebook_path'] ?? '');
    if ($path === '' || ! str_starts_with($path, $root.'/') || str_starts_with($path, $root.'/docs/plans/')) {
        exit(0);
    }
    if ($isApproved) {
        exit(0);
    }
    fwrite(STDERR, '还不能改 '.substr($path, strlen($root) + 1).'：没有已批准的计划。'.$howToWork);
    exit(2);
}
