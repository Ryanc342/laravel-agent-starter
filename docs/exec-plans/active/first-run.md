# First run of the template

## Purpose
Install the dependencies, finish the Laravel Boost setup and prove that every check passes on this machine. The template was assembled in an environment that could not reach Packagist or npm, so no PHP or npm command has run on it yet.

## Agreed decisions
1. Laravel Boost provides MCP tools and skills only. AGENTS.md and CLAUDE.md stay hand-written.
2. Formatting changes made by `composer lint` and `npm run check:fix` are accepted as they are.
3. A check that fails because of the template's own configuration (lint/, tests/Structure, phpstan.neon, the `lint` block in vite.config.ts) is fixed in that configuration without weakening the rule. Anything that would weaken a rule is asked first.
4. The version constraints of the added dev packages (Pest, spaze/phpstan-disallowed-calls, Boost) may be moved to the newest compatible versions. PHPStan must resolve to 2.3 or later (`vendor/bin/phpstan --version`): CompanionFilesRule tells the result cache which test and factory files it looked for, and older versions cannot.
5. The reference feature in docs/reference/tasks was rewritten for v2 and has not run yet. If it fails, fix the reference itself.
6. The starter kit's auth and settings code was rewritten to the v2 rules (app/Actions/Users, app/Http/Fortify, UserPolicy, UserResource, PasskeyResource, the Appearance enum) and its tests were converted to Pest. None of it has run yet. If a test fails, fix the code so it keeps the starter kit's original behaviour; change a converted test only when the conversion itself was wrong, and log it.
7. In-app notifications (app/Models/Notification, app/Actions/Notifications, the notifications page and sidebar badge) were added and have not run yet. If they fail, fix them without changing what ARCHITECTURE.md says they show.
8. Ezdev's linter rules (lint/phpstan/ and lint/oxlint/ezdev.js) were written without running PHPStan or oxlint. The oxlint rules were run with ESLint, which loads the same plugin format, on 40 sample cases and on the project's React files. If a rule fails to load or reports something wrong, fix the rule's code so it still enforces what docs/golden-principles.md says; never remove it from phpstan.neon or vite.config.ts.
9. The `lint` block in vite.config.ts now names its plugins (oxlint's defaults plus `import`, for `import/no-cycle`). If the import plugin reports anything other than a cycle, turn that one rule off next to `import/default` and log it. If oxlint rejects a setting (`jsPlugins`, the `regex` patterns of `no-restricted-imports`), change it to the format the oxlint docs give, keeping what it stops.

## Milestones
- [x] M1 Install: `composer install` (writes composer.lock), `cp .env.example .env`, `php artisan key:generate`, `touch database/database.sqlite`, `php artisan migrate`, `npm install` (writes package-lock.json), `npm run build`.
- [x] M2 Boost: `php artisan boost:install`. Choose Claude Code and Codex, enable the MCP server and skills, do not enable AI guidelines. Then `git diff -- AGENTS.md CLAUDE.md` must be empty; if Boost changed them, restore them with `git checkout -- AGENTS.md CLAUDE.md` and set `"guidelines": false` in boost.json.
- [x] M3 Format: `composer lint`, then `npm run check:fix`. Commit.
- [ ] M4 Checks: `composer check` passes. Log every template-level fix in the Decision Log. Commit.
- [ ] M5 Reference: `composer check:reference` passes. Commit any fix to docs/reference/tasks.
- [ ] M6 Prove the checks bite, on a throwaway branch: a controller that calls `$request->user()->update([...])` must fail PHPStan with "writes belong in an Action"; a component in resources/js/components/a importing one from resources/js/components/b must fail oxlint with "does not import another domain's components"; an Action calling `Gate::authorize()` must fail PHPStan with "actions do not ask for permission again"; a controller calling `$request->user()->notify(new PasswordChanged)` must fail PHPStan with "writes belong in an Action"; a controller calling `$request->validate([...])` must fail PHPStan with "validate input in a FormRequest"; a FormRequest calling `User::find(1)` must fail PHPStan with "a FormRequest checks the shape of the input only"; a controller calling `User::create([...])` must fail PHPStan with "writes belong in an Action"; a controller with a public `approve()` method must fail PHPStan with "has the public method approve()"; a new Action without a test file must fail PHPStan with "has no feature test", and after adding the test file the error must be gone on the next run without clearing the result cache; an enum that imports `App\Models\User` must fail PHPStan with "enums are the bottom layer"; a model with a `scopeOpen()` method must fail PHPStan with "is an old-style scope"; a Policy returning `$task->user_id === $user->id` must fail PHPStan with "conditions on model fields live in the model or the enum"; an Action calling `->where('status', 'open')` must fail PHPStan with "query conditions live in a model scope"; a controller passing a model to `Inertia::render()` must fail PHPStan with "send models to React through a JsonResource"; a component with `new Date(task.due_on) < new Date()` must fail oxlint with "React does not compare or subtract dates"; a page importing `@mui/material` must fail oxlint with "No other UI library"; a `share()` in HandleInertiaRequests returning `'user' => $request->user()` must fail PHPStan with "send models to React through a JsonResource". Delete the branch afterwards.
- [ ] M7 Look at it: start the app with `composer dev` in the background, then with Playwright register a new user, verify the email (read the link from `storage/logs/laravel.log`, MAIL_MAILER is log), open /notifications and check the welcome notification and the sidebar badge, press "Mark as read" and see the badge go away, then change the password on /settings/security and see a new notification. Take screenshots.

## Validation
- `composer check` and `composer check:reference` pass.
- composer.lock, package-lock.json, boost.json and the generated skills folders are committed.
- `git diff 301f330 -- AGENTS.md CLAUDE.md` shows only the template's own edits, nothing from Boost.
- M6 shows each of the seventeen failures with its message.
- M7 screenshots show the notifications page before and after "Mark as read", and the badge in the sidebar.

## Progress
- 2026-11-01 M1–M3 executed: dependencies locked and installed, PHPStan 2.3.4 confirmed, local SQLite migrated, production build and both formatters passed. Boost installed seven skills for Claude Code and Codex with guidelines disabled; AGENTS.md and CLAUDE.md are unchanged.
- 2026-10-31 Template assembled on top of laravel/react-starter-kit main (98d9b82). PHP syntax checked with `php -l`; nothing else has run.
- 2026-11-01 Starter kit code rewritten to the v2 rules and Laravel Chisel removed (every auth feature kept). PHP syntax checked with `php -l`; nothing else has run.
- 2026-11-01 Machine checks moved to linters (v2.5.0). PHP syntax checked with `php -l`, the oxlint rules run with ESLint; PHPStan, oxlint and Pest have not run.

## Surprises & Discoveries
- Composer and npm registry access work on this machine. The production build succeeds; laravel-vite-plugin reports optional optimized font fallback support requires fontaine, which is not needed for the build and was not added.

## Decision Log
- 2026-11-01 The 250-line file limit (tests/Structure/FileSizeTest.php) was removed at the user's request. A file over 300 lines is now a review signal; the class-shape and convention checks (now lint/phpstan/ClassShapeRule.php and lint/phpstan/CompanionFilesRule.php) replace it.
- 2026-11-01 Every machine check became a linter rule at the user's request: the Pest architecture tests and the PHP Structure tests became PHPStan rules (phpstan.neon, lint/phpstan/), the frontend Structure tests and dependency-cruiser became oxlint rules (vite.config.ts, lint/oxlint/ezdev.js), and dependency-cruiser was removed. The Resource and TypeScript type comparison stays a test until Wayfinder generates the types (docs/exec-plans/tech-debt.md).
- 2026-11-01 tests/Feature/Settings/SecurityTest.php: two assertions changed from `passkeys` to `passkeys.data`. The user asked for the starter kit code to follow the v2 rules, and the rule in docs/rules/frontend.md sends every collection wrapped in `data`.

## Outcomes & Retrospective
Fill this in when done, then move this file to docs/exec-plans/completed/.
