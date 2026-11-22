#!/usr/bin/env bash
# 收工前检查（Claude Code 的 Stop 钩子）。
# 自上次通过以来改过代码，就跑一次 composer check；没通过就把错误交回给 AI 修。
# 只交回一次：修完还不过，AI 停下，你会看到一行提示。
input=$(cat)
cd "$CLAUDE_PROJECT_DIR" || exit 1
marker="$(git rev-parse --git-dir)/claude-check-passed"

if [ -f "$marker" ] && [ -z "$(find app bootstrap config database resources routes tests composer.json package.json -type f -newer "$marker" -print -quit)" ]; then
  exit 0
fi

if out=$(composer check 2>&1); then
  touch "$marker"
  exit 0
fi

if printf '%s' "$input" | grep -Eq '"stop_hook_active" *: *true'; then
  echo '{"systemMessage":"composer check 仍没通过，AI 已停下。回复「继续修」即可。"}'
  exit 0
fi

printf 'composer check 没通过，修好再结束。最后的输出：\n%s\n' "$(printf '%s' "$out" | tail -n 60)" >&2
exit 2
