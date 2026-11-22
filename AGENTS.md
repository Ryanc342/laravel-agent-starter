# Ezdev Template: agent guide

This file is a map, not a manual. It stays under 100 lines.
Details live in the files it points to; read them when the task needs them.

## Commands

- `composer check`: every machine check (Pint, PHPStan with the layer rules, the generated types, oxlint, tsc, Vitest, the build, Pest on MySQL with the browser tests). It must pass before you say "done". MySQL must be running. It takes a few minutes: run it in the foreground with a Bash timeout of 600000 ms. `composer check:static`: just the static phase.
- `php artisan test --compact --group=<plan>.D1`, `npx vp test run -t <plan>.D1`: the tests of one decision, while you build.
- `composer lint`: format PHP. `npm run check:fix`: format and lint the frontend.
- `php scripts/workflow.php status`: where the plan is and the next step. `review`, `report`, `cleanup`, `upgrade`: docs/workflow.md.
- `scripts/worktree-up <name>`: a git worktree with its own .env, database and port, for parallel work.

## How we work

Every change goes through one plan (docs/PLANS.md) and the steps of docs/workflow.md: the draft with your questions, the user's "开始", the build, composer check, the review, the report. After "开始" you ask nothing. The workflow tool (scripts/workflow.php) keeps the steps and tells you the next one; a new conversation starts with it.

## The skeleton

- .claude/rules/skeleton.md, read in every session: every block of the app, where its files live, the arrows between the blocks, and the rules for every block and for the workflow.
- .claude/rules/skeleton/: one card per block and per arrow, loaded when you read or change a file in its location. Read the card yourself before you add a file to a block.
- Each rule's tag says who guarantees it: `machine`, `test`, `part`, `reviewer` (with its Check line) or `note`.

## Red lines

- Fix the code, not the test. Change or delete an existing test assertion only when a decision of the plan changes the requirement, and name that decision.
- Build only what the plan lists. What it does not say follows the cards or keeps the current behaviour, and waits for the user (docs/workflow.md, Build).
- Never silence a check: no ignore comments, no skipped tests, no lowered level or wider exception; the error says what to do instead.
- Do not create documentation files the user did not ask for, except plans and the docs this file lists.

## Docs map

- .claude/rules/skeleton.md and .claude/rules/skeleton/: the rules
- docs/workflow.md: what the workflow tool does not do: questions, build, review, report, cleanup, upgrades
- docs/PLANS.md: how to write a plan, and how a test names a decision
- docs/exec-plans/active/: the open plan. docs/exec-plans/completed/: reported plans
- docs/exec-plans/tech-debt.md: the existing debt the reviews found
- /style (resources/js/pages/style, local only): the one design reference, every shared component and the demo of every kind of page; each component's header says what it is for

## Tools

- Boost MCP (`laravel-boost`): database-schema, search-docs, logs. Prefer it over guessing.
- Subagents: `explorer` (read-only lookups) and `reviewer-haiku` (read-only, one review pack per run). They run in the foreground: the session waits for their answers.
- Hooks (.claude/settings.json): the workflow tool runs when a session starts, on each message, before a file is written or a command runs, after a command, when you stop and when a reviewer ends.
