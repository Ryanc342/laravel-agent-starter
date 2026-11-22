# Workflow

Every change goes through one plan: draft, the user's 开始, build, check, review, report. The workflow tool (scripts/workflow.php) keeps the steps: `php scripts/workflow.php status` says where the plan is and what comes next, a new conversation is told the same, code cannot change before 开始, and when you are about to stop with a step missing you are sent back once. This file holds only what the tool does not.

## Questions and the plan

Look up yourself what the code, the database (Boost MCP), the cards and the docs answer; never ask the user for it. Ask only what the user alone can decide: what to build, and every decision about money, permissions, who can see which data, or deleting or changing real data. Write the questions with your recommendations into the plan (docs/PLANS.md), then ask them all in one message, in the user's language and in plain business words, and write each answer under its question. If the scope is too big, propose splitting it first. For large lookups use the read-only `explorer` subagent with a fixed file list and a capped answer.

Then give the user the plan and wait for 开始 (also `开始吧`, `好开始`, `好的开始`, `ok开始` or `start`).

## Build

After 开始 you ask nothing. Whatever the plan does not say follows the cards, or keeps the current behaviour where no rule settles it, and goes under "Waits for you". A case about money, permissions, who can see which data, or deleting or changing real data keeps the current behaviour and goes there too. A dependency, a check or an existing test assertion the plan does not name is not changed.

- Before you create an Action, scope, enum method, model method, component or hook, search for one that exists, conditions included (ALL-2). A rule in the wrong place moves in this change; it is not copied.
- Write each decision's test first (docs/PLANS.md, Tests) and run only that test while you build. Then run `composer check`.
- A new page goes into `mainNavItems` in `resources/js/components/app-sidebar.tsx`.
- Older code is left as it is, even when it breaks a rule (FLOW-5): the reviewers list it and the report counts it.

## Review

Run `php scripts/workflow.php review`. It prints one line per reviewer run: start every run in one message, each as the `reviewer-haiku` subagent with the request `Pack: <file>`, and wait for them. Fix every finding, and every problem of the same kind in the change, not only the place named. Then `composer check` and `php scripts/workflow.php review` again: the next round reviews only what is still open. Never edit a pack or a reviewer's answer.

## Report

Write "Waits for you" in the plan, run `php scripts/workflow.php report`, and give the user the report in their language and in business words.

## Cleanup

When the user asks for it, in a branch or worktree of its own: `php scripts/workflow.php cleanup` writes a draft plan with one question for each item of docs/exec-plans/tech-debt.md. Fill in your recommendations and ask.

## Upgrades

In a branch or worktree of its own (`scripts/worktree-up upgrade`). `php scripts/workflow.php upgrade` writes a draft plan with one question for each outdated package, each package with security advisories and each file a template release left with a merge conflict. Before you run it:

- **A template release** (a project made from this template): the first time, add `.copier-answers.yml` with `_src_path: https://github.com/ryanc342/laravel-agent-starter.git` and `_commit: <the release the project is on>`. Run `copier update --defaults --vcs-ref=<tag>` and set `_commit` to `<tag>`. Fill in the plan's `Template:` line (`git ls-remote <_src_path> <tag>`): files that are as they are in the release are not reviewed.
- **Changes from the Laravel starter kit**: find the base commit in README.md ("Based on"), compare it with https://github.com/laravel/react-starter-kit, and add one question per change, with a recommendation (security fixes: take). A change taken is rewritten to the cards, with tests; update the base commit.
- **A new major version**: read the official upgrade guide (Boost `search-docs`) and the upgrade notes of every package whose major version changes; each change of behaviour is a question. Afterwards update Boost's skills (`php artisan boost:update`); AGENTS.md and CLAUDE.md do not change.

After 开始, upgrade only what the answers name, and fix the code a new version breaks, never the check.

## Parallel work

One plan, one folder: `scripts/worktree-up <name>` makes a git worktree with its own `.env`, database and port.
