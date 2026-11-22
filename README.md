# Laravel Agent Starter

[CI](https://github.com/Ryanc342/laravel-agent-starter/actions/workflows/tests.yml)

A Laravel 13 + Inertia + React + TypeScript + Tailwind starter kit for projects where AI (Claude Code) writes the code.
It is the official [Laravel React Starter Kit](https://github.com/laravel/react-starter-kit) plus three things:

1. **A skeleton.** Every block of the app (pages, components, controllers, operations, policies, models...) has a fixed place and a card of rules, and so does every arrow between two blocks. The AI reads the overview (`.claude/rules/skeleton.md`) in every session, and Claude Code loads a card when the AI reads or changes a file in its place. Each rule says who guarantees it: a machine check, a test, a ready-made part of the template, or a reviewer.
2. **Ready-made parts.** An operation part runs every change of data in a fixed order (permission, input, records, lock, save, what happens after); a listing part, a download part, a webhook part, a background job part, a file part and an outside-service part do the same for their jobs. Shared components (fields with floating labels, a submit button, a confirm button, icon buttons, page headers) give every page the same behaviour; `/style` shows them all.
3. **A workflow tool.** Every change goes through one plan: the AI writes its questions and recommendations into the plan, you answer and say "开始" (or "start"), and only then can code change. The tool keeps the steps (build, `composer check`, review, report), sends the AI back once when it is about to stop with a step missing, and writes the report from the plan, its tests and the review.

The starter kit's own auth and settings code follows the same rules, so the checks have no exceptions. Every auth feature is kept (registration, email verification, two-factor, passkeys, password confirmation). In-app notifications are built in (a notifications page and an unread badge in the sidebar).

## Requirements

| Tool | Version or requirement |
| ---- | ---------------------- |
| PHP | 8.4.1 or later, with the extensions required by Composer, including PDO MySQL |
| Composer | 2.x |
| Laravel Installer | 5.28.1 or later; verified with 5.28.1 and 5.32.0 |
| Node.js | 22.22.2+ on the 22.x line, 24.15.0+ on the 24.x line, or 26+; CI uses Node 22 |
| MySQL | 8.4 recommended, matching CI; running during setup, development and checks |
| Git | Required for project initialization, plans and the workflow tool |
| Chromium for the browser tests | `npx playwright install chromium`, once per machine |

The development database user must be able to create the configured database and its test database (`<DB_DATABASE>_test`).

## Start a new project

Start MySQL, then use the official Laravel CLI:

```sh
laravel new my-project --using=ryanc342/laravel-agent-starter
cd my-project
npx playwright install chromium
composer dev
```

Keep the installer's default choices if it asks about Pest, Boost or frontend dependencies. The template prepares the environment, application key, project database, migrations, frontend assets and Git repository automatically. Open the URL printed by `composer dev` (normally `http://localhost:8000`) and register an account. `composer dev` also runs the schedule. Stop the development processes with Ctrl+C.

**MySQL is the default.** No database flag is needed. `my-project` gets the database `my_project`, with a separate `my_project_test` database for checks. The default connection is `127.0.0.1:3306`, user `root`, with an empty password. An explicitly configured database or connection URL is preserved.

Open the project in Claude Code and describe what you need. Claude Code is the supported tool: the workflow tool runs as its hooks and the review as its subagents (`.claude/settings.json`, `.claude/agents/`). The first time you open the project, Claude Code asks once whether you trust this folder: say yes. The project's settings, with its hooks, apply only in a folder you trust.

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

The public Composer package is [ryanc342/laravel-agent-starter](https://packagist.org/packages/ryanc342/laravel-agent-starter). Stable Git tags identify its releases. The short command follows the template's main branch, as selected by Laravel Installer; updating the template does not change projects you already created: a project takes a new template release with Copier (below). To pin a stable snapshot, use `--using=ryanc342/laravel-agent-starter:v3.0.0` instead.

To work on the template itself, clone this repository and run `composer setup` with MySQL running. `composer check` verifies it. The template's own files are in `template/`: its changelog, its plans, `template/scripts/check-installer` and the tests of its tools and checks, which `composer check` runs here. `laravel new` and Copier leave that folder out. In this repository's CI, `template/scripts/check-installer` creates two disposable projects with the actual Laravel Installer and checks their MySQL isolation, development servers and complete checks.

## What happens when you ask for something

1. **Plan.** The AI looks up what the code, the database and the rules answer, and asks only what they cannot: what to build, and every decision about money, permissions, who sees which data, or changing real data. It writes a plan in `docs/exec-plans/active/` in your language: the purpose, its questions with a recommended answer each, the decisions, and each screen in words (its fields, actions and layout). It asks all the questions in one message; reply "yes", or answer by number ("1 yes 2 no, because...").
2. **开始.** When the plan is right, say "开始" (or "start"). With a question still unanswered, "开始" is held back and you are told which. Until then the AI cannot change code: a file write is refused, and a command that changed code is caught and the AI is told to put it back. If the plan changes after "开始", you say "开始" again.
3. **Build.** After "开始" the AI asks nothing. Each decision and each screen gets a test that names it; what is left is the tests still missing. What the plan does not say follows the rules or keeps the current behaviour, and waits for you in the report. Then `composer check`.
4. **Review.** Reviewer subagents (`reviewer-haiku`, read-only) check the change against the reviewer rules of every card it touches, at most ten rules per run, plus one run for the rules of every block and the workflow. They report only what breaks a written rule. The AI fixes every finding, runs `composer check` again, and the next round reviews only what is still open, until every card has a run with no finding.
5. **Report.** The workflow tool writes it from the plan: how each decision was proved, the screenshots of each screen, the cases no rule settled, what waits for you, and how many items of existing debt there are. Problems the reviewers found in older code are not fixed on the side: they go to `docs/exec-plans/tech-debt.md`, and at ten items the report brings a draft cleanup plan for you to answer. You approve the merge; CI runs `composer check` again.

When the AI is about to stop after changing code with a step missing, the workflow tool sends it back once, never twice in a row: if the step is still missing at the next stop, the AI stops and you see one line saying so. When a conversation changed a check file (`phpstan.neon`, `vite.config.ts`, `tsconfig.json`, `phpunit.xml`, `pint.json`, `lint/` or `tests/Structure/`), you also see one line naming it.

When you want to, ask for a cleanup (a plan from the existing debt) or an upgrade (a plan with one question per outdated package, security advisory or template merge conflict): `docs/workflow.md`.

## Where things are

| Path | What |
| ---- | ---- |
| `AGENTS.md` (`CLAUDE.md` points to it) | The map the AI reads at every start |
| `.claude/rules/skeleton.md` | The skeleton: every block, where its files live, the arrows, and the rules for every block and the workflow |
| `.claude/rules/skeleton/` | One card per block and per arrow: its rules, each with who guarantees it, and how a reviewer checks it |
| `docs/workflow.md` | What the workflow tool does not do: questions, build, review, report, cleanup, upgrades |
| `docs/PLANS.md`, `docs/exec-plans/` | How to write a plan; the open and the reported plans; the existing debt |
| `scripts/workflow.php` | The workflow tool and its hooks |
| `phpstan.neon`, `lint/phpstan/` | PHP linter: the layer rules (disallowed namespaces and calls) |
| `vite.config.ts` (`lint`), `lint/oxlint/` | Frontend linter: component boundaries, import cycles, only the submit button sends a form |
| `tests/Structure/` | Folder layout, code placement, silenced or turned-down checks, `/style` shows every shared component |
| `tests/Browser/` | Browser tests: every page on a computer and a phone, light and dark, with no sideways scrolling, page errors or accessibility issues |
| `.claude/`, `.mcp.json` | Subagents (explorer, reviewer-haiku), the hooks, the Boost MCP server |
| `template/` | The template's own files: changelog, plans, the installer check and the tests of its tools; not in projects |
| `scripts/worktree-up` | A git worktree with its own .env, database and port for parallel work |

## Commands

| Command | Does |
| ------- | ---- |
| `composer check` | Pint, PHPStan, the generated page types, oxlint (in `vp check`), tsc, Vitest (`tests/js`), the build, Pest on MySQL (Unit, Feature, Structure, Browser) |
| `composer check:static` | The formatting, lint, static analysis and type checks only |
| `php scripts/workflow.php status` | Where the plan is and the next step |
| `composer setup` | Install dependencies, preserve or generate the app key, migrate and build |
| `composer dev` | Start the application's development processes together, the schedule included |
| `composer lint` | Format PHP |
| `npm run check:fix` | Format and lint the frontend |
| `scripts/worktree-up <name>` | Parallel workspace for one plan |

## Changing the rules

The report lists the cases no rule settled; you decide which become rules, in a plan of their own. A rule lives on one card, and its tag says who guarantees it. Keep `AGENTS.md` under 100 lines. A machine check only ever gets stricter, and each is proven both ways in `template/tests/Lint/`: it rejects code that breaks its rule and accepts code that keeps it.

## Design: how the app looks

The template ships a design for a clean and bright work tool. Its values are the theme in `resources/css/app.css`; each shared component says at its top what it is for. In development, open `/style` to see the tokens, every shared component and a demo of every kind of page (it answers 404 outside the local environment): it is the one design reference. The browser tests keep the looks: each page's screenshot must match the one you approved. For a project with its own brand, change the theme in a plan of its own.

## Deploying

Before the app goes live, set `APP_ENV=production` and `APP_DEBUG=false` in the server's `.env`: with debug on, an error page shows the code and the settings.

## Based on

- `laravel/react-starter-kit`, main branch, commit 98d9b82 (2026-10-29), auth and settings code rewritten to the skeleton
- Laravel 13, Inertia 3, React 19, Vite+, Pest, Larastan, Laravel Boost

To take later starter kit or Laravel changes, ask for an upgrade (`docs/workflow.md`, Upgrades). The template's versions are in `template/CHANGELOG.md`.

## Taking a new template release

A project takes a template release with [Copier](https://copier.readthedocs.io), which merges the template's changes between two releases into the project and keeps the project's own changes. Install it once with `pipx install copier` (or `uv tool install copier`, or `brew install copier`); it needs Python 3.9 or later and Git. Then ask the AI to take a release (`v3.0.0`): it follows `docs/workflow.md`, Upgrades, asks about each conflict in the plan, and after your "开始" resolves them, runs `composer check` and the review.

## Contributing and security

See [CONTRIBUTING.md](CONTRIBUTING.md) for the contribution workflow. Report vulnerabilities privately through the channel in [SECURITY.md](SECURITY.md), rather than a public issue.

## License

The project's own additions are licensed under [MIT](LICENSE). Laravel, the bundled Laravel skills, and the shadcn/ui components retain their upstream notices; installed dependencies retain their respective licenses.
