# Laravel Agent Starter

[CI](https://github.com/Ryanc342/laravel-agent-starter/actions/workflows/tests.yml)

The official [Laravel React Starter Kit](https://github.com/laravel/react-starter-kit) (Laravel 13, Inertia 3, React 19, TypeScript, Tailwind) for projects where Claude Code writes the code. It adds: MySQL and UUID keys by default, in-app notifications, a clean design for a work tool, and four small files that keep the AI on a short leash.

## Requirements

PHP 8.4.1+ (with PDO MySQL), Composer 2, Node.js 22+, MySQL 8.4 (running during setup, development and checks), Git, and the Laravel Installer. The database user must be able to create the project's database and `<database>_test`.

## Start a new project

```sh
laravel new my-project --using=ryanc342/laravel-agent-starter
cd my-project
composer dev
```

`my-project` gets the database `my_project` (and `my_project_test` for the tests) on `127.0.0.1:3306`, user `root`, empty password. Other credentials: export `DB_HOST`, `DB_PORT`, `DB_USERNAME` and `DB_PASSWORD` before `laravel new`, or fix `.env` afterwards and run `composer setup`. `php artisan migrate:fresh --seed` adds a test account: test@example.com / password.

Open the folder in Claude Code and say yes when it asks whether you trust it: the hooks only run in a trusted folder.

## Commands

| Command | Does |
| ------- | ---- |
| `composer check` | Everything: Pint, PHPStan, oxlint, tsc, Vitest, Pest (with the architecture rules in `tests/Unit/ArchTest.php`) |
| `composer dev` | The app, the queue, the schedule and Vite together |
| `composer setup` | Install, `.env`, app key, migrate, build |
| `composer lint`, `npm run check:fix` | Format PHP; format and lint the frontend |
| `php artisan test --filter=<name>` | Only the tests you are working on |

## How the AI works

`AGENTS.md` (which `CLAUDE.md` loads) gives every task, big or small, the same four steps:

1. **Grill.** The AI looks up what the code, the database and the docs answer, then asks you the rest, round by round, each question with its recommendation. Reply "是" to take it, or answer by number.
2. **Plan.** It writes `docs/plans/<date>-<time>-<name>.md` (goal, questions and answers, files, how to verify) and stops.
3. **开始.** You say "开始" (or "start"); only then may it change files, and only what the plan says.
4. **Result.** After `composer check` passes it writes the result at the end of the plan, commits (`git add -A`, `git commit`), and reports in plain words.

Laravel conventions come from Laravel Boost (the guidelines in `CLAUDE.md`, the skills in `.claude/skills/`, the MCP server in `.mcp.json`); where they disagree with `AGENTS.md`, `AGENTS.md` wins. `composer update` refreshes them.

## The two hooks

Registered in `.claude/settings.json`:

- **Plan gate** (`.claude/hooks/plan-gate.php`). Before the AI edits a file outside `docs/plans/`, runs a Bash command, or uses Boost's `tinker` or `record-rule`, it needs an approved plan: your "开始" approves the newest plan as it is at that moment. Without one, Bash only runs read-only git commands (`status`, `log`, `diff`, `show`, `blame`, `grep`, `ls-files`) and `git add` / `git commit` (no `--amend`, no `push`). If the plan changes afterwards (a new requirement, the result written), or a newer plan appears, the approval lapses. A plan that already has its result is closed: "开始" does not approve it, and the AI has to grill you and write a new plan.
- **Check before stopping** (`.claude/hooks/check.sh`). When the AI is about to stop and code changed since the last pass, it runs `composer check`. If it fails, the AI is sent back once to fix it; if it still fails, the AI stops and you see one line saying so.

## Deploying

Set `APP_ENV=production` and `APP_DEBUG=false` on the server: with debug on, an error page shows code and settings.

## Contributing, security, license

See [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities privately as described in [SECURITY.md](SECURITY.md). The project's own additions are [MIT](LICENSE); Laravel, the Boost skills and the shadcn/ui components keep their upstream notices.
