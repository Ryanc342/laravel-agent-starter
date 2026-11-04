# Laravel Agent Starter

[English](README.md) | [简体中文](README.zh-CN.md) | [CI](https://github.com/Ryanc342/laravel-agent-starter/actions/workflows/tests.yml)

A Laravel 13 + Inertia + React + TypeScript + Tailwind starter kit for projects where AI (Codex or Claude Code) writes the code.
It is the official [Laravel React Starter Kit](https://github.com/laravel/react-starter-kit) plus three things:

1. **Docs the AI reads.** A short `AGENTS.md` (under 100 lines) that works as a map, and `docs/` with the details: workflow, plans, rules per folder, a reference feature.
2. **A fixed architecture.** Every business rule has one owner (an Action, a Policy, a model scope or method, an enum method). Layers only depend downwards. All rules are listed once, in ten principles, in `docs/golden-principles.md`; `ARCHITECTURE.md` explains them.
3. **Machine checks for structure, a second AI for business logic.** Machines check where code goes and what it may use: PHPStan for PHP (with Ezdev's rules in `lint/phpstan/`), oxlint for React (with Ezdev's rules in `lint/oxlint/`), and structure tests for folders, routes, check configs, docs and the Resource types. One command runs them all: `composer check`. Whether the business logic is right needs reading, so a reviewer AI checks those rules on every change. When the AI is about to stop, a finish check sends back any step of the workflow it skipped.

The starter kit's own auth and settings code is rewritten to follow the same rules, so the checks have no exceptions. Every auth feature is kept (registration, email verification, two-factor, passkeys, password confirmation); Laravel Chisel is removed. To drop a feature in a project, ask the AI to plan its removal.

In-app notifications are built in (a notifications page and an unread badge in the sidebar). They are also the live example of scopes, Policies, bulk operations and button consistency tests; `ARCHITECTURE.md` maps each pattern to its file.

## Requirements

| Tool | Version or requirement |
| ---- | ---------------------- |
| PHP | 8.4.1 or later, with the extensions required by Composer, including PDO MySQL; this is the minimum required by the committed dependencies |
| Composer | 2.x |
| Laravel Installer | 5.28.1 or later; verified with 5.28.1 and 5.32.0 |
| Node.js | 22.22.2+ on the 22.x line, 24.15.0+ on the 24.x line, or 26+; CI uses Node 22 |
| MySQL | 8.4 recommended, matching CI; running during setup, development and checks |
| Git | Required for project initialization, plans, commits and the finish check |

The development database user must be able to create the configured database and its test databases. Checks use `<DB_DATABASE>_test`; the reference check uses a separate `<DB_DATABASE>_reference_test` database.

## Start a new project

Start MySQL, then use the official Laravel CLI:

```sh
laravel new my-project --using=ryanc342/laravel-agent-starter
cd my-project
composer dev
```

Keep the installer's default choices if it asks about Pest, Boost or frontend dependencies. The template prepares the environment, application key, project database, migrations, frontend assets and Git repository automatically. Open the URL printed by `composer dev` (normally `http://localhost:8000`) and register an account. Stop the development processes with Ctrl+C.

**MySQL is the default.** No database flag is needed. `my-project` gets the database `my_project`, with separate `my_project_test` and `my_project_reference_test` databases for checks. The default connection is `127.0.0.1:3306`, user `root`, with an empty password. An explicitly configured database or connection URL is preserved.

Open the project in Codex or Claude Code and describe what you need. In Codex, open `/hooks` once and trust `.codex/hooks.json`; Claude Code uses `.claude/settings.json`.

### If your MySQL credentials differ

Set your connection credentials before creating the project; the template saves them in the new project's local `.env`:

```sh
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_USERNAME=your-user
export DB_PASSWORD='your-password'
```

The account needs permission to create the project's databases. Keep `.env` local. If initialization reports a connection error, fix the credentials in the generated `.env` and run `composer setup` there. It preserves an existing application key and database name.

### Maintaining this starter kit

The public Composer package is [ryanc342/laravel-agent-starter](https://packagist.org/packages/ryanc342/laravel-agent-starter). Stable Git tags identify its releases. The short command follows the template’s main branch, as selected by Laravel Installer; updating the template does not change projects you already created. To pin a stable snapshot, use `--using=ryanc342/laravel-agent-starter:v1.0.0` instead.

To work on the template itself, clone this repository and run `composer setup` with MySQL running. `composer check` verifies the source, and `composer check:reference` also verifies the reference feature. In this repository's CI, `scripts/check-installer` creates two disposable projects with the actual Laravel Installer and checks their MySQL isolation, development servers and complete checks. Derived project repositories run the regular checks.

## What happens when you ask for a feature

1. **Grilling.** The AI looks up the code, then asks every decision it needs in rounds, each with a recommended answer. Reply "yes", or answer by number ("1 yes 2 no, because...").
2. **ExecPlan.** It writes the plan to `docs/exec-plans/active/<feature>.md`: agreed decisions, milestones, how it will be checked. Say "start" when it is right.
3. **Milestones.** For each one: tests first, then code bottom-up, then `composer check`, then a commit and a Progress line in the plan.
4. **Review.** A `reviewer` subagent with a fresh context compares the change with the plan and reports only real problems (at most 3 rounds).
5. **Report.** Where each decision lives, the check output, screenshots. You approve the merge; CI runs `composer check` again.

Whenever the AI is about to stop, the finish check (`scripts/hooks/finish-check.php`) sends back a missing step: code without a plan, a fixed tech-debt line without a `docs/QUALITY.md` update, a protected file or dependency changed without your recorded approval, or code that no passing `composer check` has seen.

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
| `docs/golden-principles.md`, `docs/QUALITY.md` | What review and the weekly cleanup check; quality grade per domain       |
| `phpstan.neon`, `lint/phpstan/`           | PHP linter rules: forbidden calls and layers, class shapes, scopes, companion files |
| `vite.config.ts` (`lint`), `lint/oxlint/` | Frontend linter rules: React never does the backend's job, component boundaries |
| `tests/Structure/`                        | Docs checks; code checks across folders; Resource and TypeScript type comparison |
| `.codex/`, `.claude/`, `.mcp.json`        | Subagents (explorer, reviewer), MCP servers (Boost, Playwright) and the finish check hooks |
| `scripts/hooks/finish-check.php`          | The finish check: what is missing when the AI is about to stop            |
| `scripts/worktree-up`                     | A git worktree with its own .env, database and port for parallel work    |

## Commands

| Command                    | Does                                                                  |
| -------------------------- | --------------------------------------------------------------------- |
| `composer check`           | Pint, PHPStan, oxlint (in `vp check`), tsc, Vitest (`tests/js`), Pest on MySQL (Feature, Structure) |
| `composer check:reference` | The same checks on a temporary copy that includes the reference feature |
| `composer setup`           | Install dependencies, preserve or generate the app key, migrate and build |
| `composer dev`             | Start the application's development processes together                  |
| `composer lint`            | Format PHP                                                            |
| `npm run check:fix`        | Format and lint the frontend                                          |
| `scripts/worktree-up <name>` | Parallel workspace for one plan                                     |

## Changing the rules

The AI proposes rule changes when it gets stuck or is corrected twice; you approve them. Keep `AGENTS.md` under 100 lines (a test checks it): add details to `docs/`, not to the map. When a structure rule is broken twice, turn it into a check (`phpstan.neon`, `lint/phpstan/`, `lint/oxlint/ezdev.js` or `tests/Structure/`) with an error message that says what to do instead; business rules stay with the reviewer. The weekly cleanup lists gaps in the checks and rules to merge or remove; you decide, and a check only ever gets stricter. After changing rules or tooling, run `composer check:reference`.

## DESIGN.md: how the app looks

The template ships a `DESIGN.md` for a quiet work tool, in Google's open DESIGN.md format: exact values (colors, type, corners, spacing) at the top, then eight sections that explain them, ending with Do's and Don'ts. Its values are the theme in `resources/css/app.css`. The AI follows it on every page and looks at the page at desktop and phone width; the reviewer opens the changed pages, takes screenshots and checks them against the `ui` rules (principle 10). For a project with its own brand, replace the file and keep its section order; it is protected, so the AI asks you first. Details: `docs/rules/visual-design.md`.

## Based on

- `laravel/react-starter-kit`, main branch, commit 98d9b82 (2026-10-29), auth and settings code rewritten (see `ARCHITECTURE.md`, "Starter kit code")
- Laravel 13, Inertia 3, React 19, Vite+, Pest, Larastan, Laravel Boost

To pick up later starter kit or Laravel changes, run `docs/prompts/upgrade.md`. See `CHANGELOG.md` for versions.

## Contributing and security

See [CONTRIBUTING.md](CONTRIBUTING.md) for the contribution workflow. Report vulnerabilities privately through the channel in [SECURITY.md](SECURITY.md), rather than a public issue.

## License

The project's own additions are licensed under [MIT](LICENSE). Laravel, the bundled Laravel skills, and the shadcn/ui components retain their upstream notices; installed dependencies retain their respective licenses. The internal Ezdev rule names identify this template's architecture checks.
