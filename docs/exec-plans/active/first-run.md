# First run of the template

## Purpose
Install the dependencies, finish the Laravel Boost setup and prove that every check passes on this machine. The template was assembled in an environment that could not reach Packagist or npm, so no PHP or npm command has run on it yet.

## Agreed decisions
1. Laravel Boost provides MCP tools and skills only. AGENTS.md and CLAUDE.md stay hand-written.
2. Formatting changes made by `composer lint` and `npm run check:fix` are accepted as they are.
3. A check that fails because of the template's own configuration (tests/Arch, tests/Structure, phpstan.neon, .dependency-cruiser.cjs) is fixed in that configuration without weakening the rule. Anything that would weaken a rule is asked first.
4. The version constraints of the added dev packages (Pest, spaze/phpstan-disallowed-calls, Boost, dependency-cruiser) may be moved to the newest compatible versions.
5. The reference feature in docs/reference/tasks was rewritten for v2 and has not run yet. If it fails, fix the reference itself.
6. The starter kit's auth and settings code was rewritten to the v2 rules (app/Actions/Users, app/Http/Fortify, UserPolicy, UserResource, PasskeyResource, the Appearance enum) and its tests were converted to Pest. None of it has run yet. If a test fails, fix the code so it keeps the starter kit's original behaviour; change a converted test only when the conversion itself was wrong, and log it.
7. In-app notifications (app/Models/Notification, app/Actions/Notifications, the notifications page and sidebar badge) were added and have not run yet. If they fail, fix them without changing what ARCHITECTURE.md says they show.
8. The custom PHPStan rules in tests/PHPStan/ were written without running PHPStan. If one fails to load or reports something wrong, fix the rule's code so it still enforces what docs/golden-principles.md says; never remove it from phpstan.neon.

## Milestones
- [ ] M1 Install: `composer install` (writes composer.lock), `cp .env.example .env`, `php artisan key:generate`, `touch database/database.sqlite`, `php artisan migrate`, `npm install` (writes package-lock.json), `npm run build`.
- [ ] M2 Boost: `php artisan boost:install`. Choose Claude Code and Codex, enable the MCP server and skills, do not enable AI guidelines. Then `git diff -- AGENTS.md CLAUDE.md` must be empty; if Boost changed them, restore them with `git checkout -- AGENTS.md CLAUDE.md` and set `"guidelines": false` in boost.json.
- [ ] M3 Format: `composer lint`, then `npm run check:fix`. Commit.
- [ ] M4 Checks: `composer check` passes. Log every template-level fix in the Decision Log. Commit.
- [ ] M5 Reference: `composer check:reference` passes. Commit any fix to docs/reference/tasks.
- [ ] M6 Prove the checks bite, on a throwaway branch: a controller that calls `$request->user()->update([...])` must fail PHPStan with "writes belong in an Action"; a component in resources/js/components/a importing one from resources/js/components/b must fail dependency-cruiser; an Action calling `Gate::authorize()` must fail the Arch test; a controller calling `$request->user()->notify(new PasswordChanged)` must fail PHPStan with "writes belong in an Action"; a controller calling `$request->validate([...])` must fail PHPStan with "validate input in a FormRequest"; a FormRequest calling `User::find(1)` must fail PHPStan with "a FormRequest checks the shape of the input only"; a controller calling `User::create([...])` must fail PHPStan with "writes belong in an Action"; a controller with a public `approve()` method must fail the Arch test; a new Action without a test file must fail the Structure test; a Policy returning `$task->user_id === $user->id` must fail PHPStan with "conditions on model fields live in the model or the enum"; an Action calling `->where('status', 'open')` must fail PHPStan with "query conditions live in a model scope"; a controller passing a model to `Inertia::render()` must fail PHPStan with "send models to React through a JsonResource"; a component with `new Date(task.due_on) < new Date()` must fail the Frontend Structure test; a `share()` in HandleInertiaRequests returning `'user' => $request->user()` must fail PHPStan with "send models to React through a JsonResource". Delete the branch afterwards.
- [ ] M7 Look at it: start the app with `composer dev` in the background, then with Playwright register a new user, verify the email (read the link from `storage/logs/laravel.log`, MAIL_MAILER is log), open /notifications and check the welcome notification and the sidebar badge, press "Mark as read" and see the badge go away, then change the password on /settings/security and see a new notification. Take screenshots.

## Validation
- `composer check` and `composer check:reference` pass.
- composer.lock, package-lock.json, boost.json and the generated skills folders are committed.
- `git diff 301f330 -- AGENTS.md CLAUDE.md` shows only the template's own edits, nothing from Boost.
- M6 shows each of the fourteen failures with its message.
- M7 screenshots show the notifications page before and after "Mark as read", and the badge in the sidebar.

## Progress
- 2026-10-31 Template assembled on top of laravel/react-starter-kit main (98d9b82). PHP syntax checked with `php -l`; nothing else has run.
- 2026-11-01 Starter kit code rewritten to the v2 rules and Laravel Chisel removed (every auth feature kept). PHP syntax checked with `php -l`; nothing else has run.

## Surprises & Discoveries
- None yet.

## Decision Log
- 2026-11-01 The 250-line file limit (tests/Structure/FileSizeTest.php) was removed at the user's request. A file over 300 lines is now a review signal; the class-shape rules in tests/Arch/ClassesTest.php and the conventions in tests/Structure/ConventionsTest.php replace it.
- 2026-11-01 tests/Feature/Settings/SecurityTest.php: two assertions changed from `passkeys` to `passkeys.data`. The user asked for the starter kit code to follow the v2 rules, and the rule in docs/rules/frontend.md sends every collection wrapped in `data`.

## Outcomes & Retrospective
Fill this in when done, then move this file to docs/exec-plans/completed/.
