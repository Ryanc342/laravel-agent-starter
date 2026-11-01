# Ezdev v2

A Laravel 13 + Inertia + React + TypeScript + Tailwind starter kit for projects where AI (Codex or Claude Code) writes the code.
It is the official [Laravel React Starter Kit](https://github.com/laravel/react-starter-kit) plus three things:

1. **Docs the AI reads.** A short `AGENTS.md` (under 100 lines) that works as a map, and `docs/` with the details: workflow, plans, rules per folder, a reference feature.
2. **A fixed architecture.** Every business rule has one owner (an Action, a Policy, a model scope or method, an enum method). Layers only depend downwards. All rules are listed once, in eight principles, in `docs/golden-principles.md`; `ARCHITECTURE.md` explains them.
3. **Machine checks.** Pest architecture and structure tests, PHPStan (with custom rules in `tests/PHPStan`), dependency-cruiser and a duplicate-code detector enforce the architecture. One command runs everything: `composer check`.

The starter kit's own auth and settings code is rewritten to follow the same rules, so the checks have no exceptions. Every auth feature is kept (registration, email verification, two-factor, passkeys, password confirmation); Laravel Chisel is removed. To drop a feature in a project, ask the AI to plan its removal.

In-app notifications are built in (a notifications page and an unread badge in the sidebar). They are also the live example of scopes, Policies, bulk operations and button consistency tests; `ARCHITECTURE.md` maps each pattern to its file.

## Start a new project

1. On GitHub, click **Use this template**, create the repository and clone it.
2. Open it in Codex or Claude Code and say: "Do the first-run plan." The AI follows `docs/exec-plans/active/first-run.md`: installs everything, sets up Laravel Boost, runs every check, and proves the checks catch violations.
3. Describe what you need in business terms.

To set up by hand instead: `composer setup`, then `php artisan boost:install` (Claude Code and Codex, MCP and skills, no AI guidelines), then `composer check`.

## What happens when you ask for a feature

1. **Grilling.** The AI looks up the code, then asks every decision it needs in rounds, each with a recommended answer. Reply "yes", or answer by number ("1 yes 2 no, because...").
2. **ExecPlan.** It writes the plan to `docs/exec-plans/active/<feature>.md`: agreed decisions, milestones, how it will be checked. Say "start" when it is right.
3. **Milestones.** For each one: tests first, then code bottom-up, then `composer check`, then a commit and a Progress line in the plan.
4. **Review.** A `reviewer` subagent with a fresh context compares the change with the plan and reports only real problems (at most 3 rounds).
5. **Report.** Where each decision lives, the check output, screenshots. You approve the merge; CI runs `composer check` again.

Once a week, run `docs/prompts/cleanup.md` to find and fix drift. To update dependencies, take changes from the Laravel starter kit or move to a new Laravel version, run `docs/prompts/upgrade.md`.

## Where things are

| Path                                      | What                                                                     |
| ----------------------------------------- | ------------------------------------------------------------------------ |
| `AGENTS.md` (`CLAUDE.md` points to it)    | The map the AI reads at every start                                      |
| `ARCHITECTURE.md`                         | Layers, where each rule goes, which check enforces it                    |
| `docs/workflow.md`                        | Grilling format, plan, build, review, report                             |
| `docs/PLANS.md`, `docs/exec-plans/`       | How to write a plan; active and completed plans; tech debt               |
| `docs/rules/`                             | Detailed rules per folder (actions, controllers, models, frontend, tests) |
| `docs/reference/tasks/`                   | A complete reference feature the AI copies the structure from            |
| `docs/golden-principles.md`, `QUALITY.md` | What review and the weekly cleanup check; quality grade per domain       |
| `tests/Arch/`, `tests/Structure/`         | Architecture tests; conventions and docs checks                          |
| `phpstan.neon`, `.dependency-cruiser.cjs` | Forbidden calls (with what to call instead); frontend boundaries         |
| `.codex/`, `.claude/`, `.mcp.json`        | Subagents (explorer, reviewer) and MCP servers (Boost, Playwright)       |
| `scripts/worktree-up`                     | A git worktree with its own .env, database and port for parallel work    |

## Commands

| Command                    | Does                                                                  |
| -------------------------- | --------------------------------------------------------------------- |
| `composer check`           | Pint, PHPStan, Pest (Feature, Arch, Structure), tsc, lint, dependency-cruiser, jscpd |
| `composer check:reference` | The same checks on a temporary copy that includes the reference feature |
| `composer lint`            | Format PHP                                                            |
| `npm run check:fix`        | Format and lint the frontend                                          |
| `scripts/worktree-up <name>` | Parallel workspace for one plan                                     |

## Changing the rules

The AI proposes rule changes when it gets stuck or is corrected twice; you approve them. Keep `AGENTS.md` under 100 lines (a test checks it): add details to `docs/`, not to the map. When a rule is broken twice, turn it into a check (`tests/Arch/LayersTest.php` or `phpstan.neon`) with an error message that says what to do instead. After changing rules or tooling, run `composer check:reference`.

## Optional: DESIGN.md

Put a `DESIGN.md` in the project root when the project has a visual design. The AI moves its colors, fonts and radius into `resources/css/app.css` the first time it builds a page, and every page uses them. It affects appearance only. Details: `docs/rules/visual-design.md`.

## Based on

- `laravel/react-starter-kit`, main branch, commit 98d9b82 (2026-10-29), auth and settings code rewritten (see `ARCHITECTURE.md`, "Starter kit code")
- Laravel 13, Inertia 3, React 19, Vite+, Pest, Larastan, Laravel Boost

To pick up later starter kit or Laravel changes, run `docs/prompts/upgrade.md`. See `CHANGELOG.md` for versions.
