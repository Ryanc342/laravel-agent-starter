# Laravel Agent Starter

[English](README.md) | [简体中文](README.zh-CN.md) | [CI](https://github.com/Ryanc342/laravel-agent-starter/actions/workflows/tests.yml)

A Laravel 13 + Inertia + React + TypeScript + Tailwind starter kit for projects where AI (Claude Code) writes the code.
It is the official [Laravel React Starter Kit](https://github.com/laravel/react-starter-kit) plus three things:

1. **Docs the AI reads.** A short `AGENTS.md` (under 100 lines) that works as a map, and `docs/` with the details: workflow, plans, rules per folder, a reference feature.
2. **A fixed architecture.** Every business rule has one owner (an Action, a Policy, a model scope or method, an enum method). Layers only depend downwards. Every rule is written once, in the `.claude/rules/` file of the folders it is about, which Claude Code loads when the AI reads a matching file; `docs/golden-principles.md` holds the principles and the review lanes, and `ARCHITECTURE.md` explains them.
3. **Machine checks for a skeleton, two AI reviewers for everything else.** Machines check a skeleton: the layers (PHPStan, the layer rules in `phpstan.neon`), the component boundaries, import cycles and no `t()` at import time (oxlint, with Ezdev's rules in `lint/oxlint/`), the folders of `app/`, where pages and components go, that no check is silenced or turned down, and the design spec's index and values (structure tests). One command runs them all: `composer check`. Every other rule needs reading, so two reviewer subagents on different models, `reviewer-opus` and `reviewer-haiku`, read every change for them, one review lane at a time. When the AI is about to stop, a finish check sends back any step of the workflow it skipped, review rounds included.

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

The development database user must be able to create the configured database and its test databases. Checks use `<DB_DATABASE>_test`; the reference check uses a separate `<DB_DATABASE>_reference_test` database, and the screen rounds `<DB_DATABASE>_review_opus` and `<DB_DATABASE>_review_haiku`.

## Start a new project

Start MySQL, then use the official Laravel CLI:

```sh
laravel new my-project --using=ryanc342/laravel-agent-starter
cd my-project
composer dev
```

Keep the installer's default choices if it asks about Pest, Boost or frontend dependencies. The template prepares the environment, application key, project database, migrations, frontend assets and Git repository automatically. Open the URL printed by `composer dev` (normally `http://localhost:8000`) and register an account. Stop the development processes with Ctrl+C.

**MySQL is the default.** No database flag is needed. `my-project` gets the database `my_project`, with separate `my_project_test` and `my_project_reference_test` databases for checks. The default connection is `127.0.0.1:3306`, user `root`, with an empty password. An explicitly configured database or connection URL is preserved.

Open the project in Claude Code and describe what you need. Claude Code is the supported tool: the review rounds need its subagents and hooks (`.claude/settings.json`). The first time you open the project, Claude Code asks once whether you trust this folder: say yes. The project's settings, with its hooks, subagent settings and MCP servers, apply only in a folder you trust, and the checks before the AI stops are those hooks.

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

The public Composer package is [ryanc342/laravel-agent-starter](https://packagist.org/packages/ryanc342/laravel-agent-starter). Stable Git tags identify its releases. The short command follows the template’s main branch, as selected by Laravel Installer; updating the template does not change projects you already created: a project takes a new template release with Copier (below). To pin a stable snapshot, use `--using=ryanc342/laravel-agent-starter:v1.0.0` instead.

To work on the template itself, clone this repository and run `composer setup` with MySQL running. `composer check` verifies the source, and `composer check:reference` also verifies the reference feature. In this repository's CI, `scripts/check-installer` creates two disposable projects with the actual Laravel Installer and checks their MySQL isolation, development servers and complete checks. Derived project repositories run the regular checks.

## What happens when you ask for a feature

1. **Questions.** The AI looks up what the code, the database and the rules answer, and asks only what they cannot: what to build, and every decision about money, permissions, who sees which data, or changing real data. It asks in rounds, each question with a recommended answer. Reply "yes", or answer by number ("1 yes 2 no, because..."). Questions come only now, before the plan.
2. **Plan.** It writes the plan to `docs/exec-plans/active/<feature>.md`: the agreed decisions, the protected files and dependencies it will change ("Approved changes"), the milestones and how they will be checked. Say "开始" (or "start") when it is right: that approves the plan as it stands, and a copy of it is kept as the approved one.
3. **Build.** After "开始" the AI asks nothing. What the plan does not say follows the template's rules or keeps the current behaviour, and the report names it. For each milestone: tests first, code bottom-up, then `composer check:milestone -- <affected test paths>`, a commit and one Progress line. When every milestone is done, full `composer check`.
4. **Review.** All review happens once, in the final review, after full `composer check`: no milestone has a review round. A round runs both reviewers at the same time on the same commit, one run each for every review lane the change touches (`docs/golden-principles.md`, Review lanes). A lane is done when, in its latest round, both report no P0 and no P1 on committed code and nothing in the lane's places changed since; the AI fixes the findings and starts the next round, with no cap on rounds. The screens come last: the lanes `look` and `behaviour` start when every other lane is done, on two seats at once. `scripts/screen-round` prepares them: each reviewer gets a copy of the app of its own (`../<project folder>-review-opus` for `reviewer-opus` in the `playwright` browser, `../<project folder>-review-haiku` for `reviewer-haiku` in the `playwright-review` browser), each with its own database and a free port; the project's own app and database are never touched, and `composer dev` need not run. Both walk the plan's flows through the forms and look at every changed page at 1280px, 390px and in dark mode. A change to the reference feature (`docs/reference/tasks/`) is reviewed like app code: `scripts/screen-round --reference` installs the feature in both copies. A reviewer has no shell, only file reading, search and its browser: before each round `php scripts/hooks/finish-check.php round <plan path>` writes the plan's diff and the plan as approved, and the request names both files.
5. **Report.** In business words: what was built, the check results, the rule gaps and the decisions that differ from a rule, and what waits for you. Screenshots are temporary unless you ask to keep them. You approve the merge; CI runs `composer check` again.

Whenever the AI is about to stop, the finish check (`scripts/hooks/finish-check.php`) sends back what is missing, one line each: a plan for changed code, a check that passed on the current code (the milestone check while milestones remain, full `composer check` at the end), the review of a finished plan, and the approval of a protected change. A reminder goes back once, never twice in a row: if the step is still missing at the next stop, the AI stops and you see one line naming it. Each reviewer run writes one line to a review ledger (Claude Code's subagent hook), and the finish check reads the ledger, not what the AI writes: a plan is finished only when every lane it touches is done.

The review rounds add time and model cost to every change: at least two reviewer runs per touched lane, again after each fix, and the screen rounds drive two browsers. Claude Code is the supported tool, because the rounds need its subagents and hooks.

Once a week, run `docs/prompts/cleanup.md` to find and fix drift. To update dependencies, take changes from the Laravel starter kit or move to a new Laravel version, run `docs/prompts/upgrade.md`.

## Where things are

| Path                                      | What                                                                     |
| ----------------------------------------- | ------------------------------------------------------------------------ |
| `AGENTS.md` (`CLAUDE.md` points to it)    | The map the AI reads at every start                                      |
| `ARCHITECTURE.md`                         | Layers, where each rule goes, which check enforces it                    |
| `docs/workflow.md`                        | Questions, plan, build, review, report                                   |
| `docs/PLANS.md`, `docs/exec-plans/`       | How to write a plan; active and completed plans; tech debt               |
| `.claude/rules/`                          | Every rule, once, in the file of the folders it is about; Claude Code loads a file when the AI reads a matching file |
| `DESIGN.md`, `docs/design/`               | How the app looks: the style, foundations, components and one file per page pattern |
| `docs/reference/tasks/`                   | A complete reference feature the AI copies the structure from            |
| `docs/golden-principles.md`, `docs/QUALITY.md` | The principles, the review lanes and how a rule names who checks it; quality grade per domain |
| `docs/prompts/review.md`                  | What a reviewer run reads and how it reports                              |
| `phpstan.neon`, `lint/phpstan/`           | PHP linter: the layer rules (disallowed namespaces and functions)         |
| `vite.config.ts` (`lint`), `lint/oxlint/` | Frontend linter: component boundaries and import cycles                   |
| `tests/Structure/`, `tests/Unit/Lint/`    | Folder layout, code placement, silenced or turned-down checks, the design spec; every machine rule proved on a broken fixture |
| `.claude/`, `.mcp.json`                   | Subagents (explorer, reviewer-opus, reviewer-haiku), MCP servers (Boost, two Playwright browsers) and the hooks (finish check, review ledger) |
| `scripts/hooks/finish-check.php`          | The finish check: what is missing when the AI is about to stop, and the review ledger |
| `scripts/screen-round`                    | Prepares a screen round: a copy of the app per reviewer, both databases reset, both apps answering; `--reference` for the reference feature |
| `scripts/worktree-up`                     | A git worktree with its own .env, database and port for parallel work    |

## Commands

| Command                    | Does                                                                  |
| -------------------------- | --------------------------------------------------------------------- |
| `composer check`           | Pint, PHPStan, oxlint (in `vp check`), tsc, Vitest (`tests/js`), Pest on MySQL (Unit, Feature, Structure) |
| `composer check:static` | Full PHP/frontend formatting, lint, static analysis and type checks |
| `composer check:milestone -- <test paths>` | Full static checks, then the selected PHP/frontend tests |
| `composer check:reference` | The same checks on a temporary copy that includes the reference feature |
| `composer setup`           | Install dependencies, preserve or generate the app key, migrate and build |
| `composer dev`             | Start the application's development processes together                  |
| `composer lint`            | Format PHP                                                            |
| `npm run check:fix`        | Format and lint the frontend                                          |
| `scripts/worktree-up <name>` | Parallel workspace for one plan                                     |

## Changing the rules

The AI's report lists the cases no rule settled and the decisions that differ from a rule; you decide which become rules, in a plan of their own. Keep `AGENTS.md` under 100 lines: add details to `docs/`, not to the map. The machine checks are the skeleton; every other rule is a `[review:<lane>]` rule in `.claude/rules/` and stays with the reviewers. The weekly cleanup (`docs/prompts/cleanup.md`) goes through the `[review:<lane>]` rules lane by lane and lists the gaps in the machine checks and the rules to merge or remove; you decide, and a check only ever gets stricter. After changing rules or tooling, run `composer check:reference`.

## Design: how the app looks

The template ships a design for a clean and bright work tool, in four layers that the AI reads only as far as the task needs: `DESIGN.md` (the style, and an index of which file to read for which task), `docs/design/foundations.md` (colour, type, space, messages), `docs/design/components.md` (which component for which need) and `docs/design/patterns/` (one file per kind of page: list, detail, form, empty / error / loading states, dashboard, settings, feed, login and register, error pages, bulk actions, import and export, print, search results, wizard, approval, list and detail, report, calendar, kanban, and the customer pages: home / landing, pricing, product, customer area, checkout, help / FAQ and contact). The home page `/` is built in that style on the public layout (`resources/js/layouts/public-layout.tsx`: a top bar and a footer; the links are the pages the project has). The values are the theme in `resources/css/app.css`, not text in the documents. Every pattern has a demo: in development, open `/style` to see the tokens, the components and the pattern demos (it answers 404 outside the local environment). The AI follows the patterns; in the final review's screen rounds the two reviewers open each changed page at desktop and phone width and in dark mode, compare it with the style overview page and check the `ui` rules (principle 10). For a project with its own brand, change `DESIGN.md` and the theme tokens; they are protected, so a plan that changes them names them in "Approved changes" and your "开始" approves them. Details: `.claude/rules/visual-design.md`.

## Languages

The app speaks English and Simplified Chinese. A first visit follows the browser's `Accept-Language` (any Chinese gives Chinese, anything else English); users switch on Settings → Appearance, and guests with the "EN / 中文" switch at the top right of the public bar and the sign-in pages. The choice is saved on the user (`users.locale`) and in the `locale` cookie, and `SetLocale` applies it to every request. There is one source, Laravel's language files and no package: a sentence is keyed by its English text (`lang/zh_CN.json`, `"Save changes": "保存更改"`), Laravel's own groups live in `lang/zh_CN/`, and React reads the current language's sentences through `t()` and `tChoice()` (`resources/js/lib/i18n.ts`). Write every sentence in English through `t()` or `__()` and add its Chinese in the same commit; the reviewers report a missing or unused translation and text written straight into JSX (lane `behaviour`), and oxlint fails on a `t()` that runs at import time: mark such a sentence with `msg()` and translate it where it is shown. What users type is never translated. Details: `.claude/rules/translations.md`.

## Deploying

A server that makes PDFs needs a PHP `memory_limit` of at least 256M. The PDF views draw every character with Noto Sans SC (`resources/fonts/`, about 10.6 MB per weight, SIL Open Font License) so Chinese text prints; DOMPDF reads the full font to cut out the characters a PDF uses, and one render peaks at 120 to 130 MB, over PHP's default 128M. `phpunit.xml` sets 256M for the tests. DOMPDF keeps its font files and metrics in `storage/fonts`, which must be writable.

## Based on

- `laravel/react-starter-kit`, main branch, commit 98d9b82 (2026-10-29), auth and settings code rewritten (see `ARCHITECTURE.md`, "Starter kit code")
- Laravel 13, Inertia 3, React 19, Vite+, Pest, Larastan, Laravel Boost

To pick up later starter kit or Laravel changes, run `docs/prompts/upgrade.md`. See `CHANGELOG.md` for versions.

## Taking a new template release

A project takes a template release with [Copier](https://copier.readthedocs.io), which merges the template's changes between two releases into the project and keeps the project's own changes. Install it once with `pipx install copier` (or `uv tool install copier`, or `brew install copier`); it needs Python 3.9 or later and Git. Then ask the AI to sync the template to a release (`v1.4.0`): it follows `docs/prompts/upgrade.md`, "Projects made from this template", asks about each conflict in the plan, and after your "开始" resolves them, runs `composer check` and the review.

## Contributing and security

See [CONTRIBUTING.md](CONTRIBUTING.md) for the contribution workflow. Report vulnerabilities privately through the channel in [SECURITY.md](SECURITY.md), rather than a public issue.

## License

The project's own additions are licensed under [MIT](LICENSE). Laravel, the bundled Laravel skills, and the shadcn/ui components retain their upstream notices; installed dependencies retain their respective licenses. The internal Ezdev rule names identify this template's architecture checks.
