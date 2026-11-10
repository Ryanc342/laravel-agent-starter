# Changelog

Versions follow [Semantic Versioning](https://semver.org): a new rule or behaviour change bumps the minor version, a wording fix bumps the patch version.

## [1.1.0] - 2026-11-10

Public Composer package release; the older 2.x entries below describe the template's history before its public 1.0 release.

### Changed

- Milestones run full formatting, static analysis and type checks plus tests for affected behavior and callers. Final plan acceptance and CI still run every check and test; shared infrastructure or unbounded impact requires an earlier full check.
- `composer check:static` owns the shared static phase. `composer check:milestone -- <test paths>` runs that phase and selected PHP/frontend tests, rejecting empty or invalid scopes and recording a separate fresh-code result.
- The finish check permits intermediate stops for validated incomplete active plans. Partial results cannot authorize final acceptance, satisfy a completed plan's full-check record, or survive a failed recheck or code change. The agent guide, workflow, plan format and bilingual public guides follow the same boundary.

## [2.13.0] - 2026-11-04

From the first round of the Sol experiment (purchase orders): what the AI had to guess, the template now shows.

### Added

- `components/ui/table.tsx` (shadcn's Table, no new package). DESIGN.md: every list of records is a Table in a bordered box, with its name or number, status and amount first so a phone shows them before the table scrolls; a feed of messages (notifications) stays rows. Principle 10 says the same.
- The reference feature's task list is a table, and it gains a task page (`tasks.show`, `TaskPolicy::view`, `pages/tasks/show.tsx`) whose breadcrumb is built from its data and ends at the task, with the task's actions and its edit form. Updating or completing a task returns to its page. A `ui` rule: a page about one record has such a breadcrumb and no separate back button.
- `resources/js/lib/format.ts` with `formatMoney(cents)`, `formatQuantity(value)` and `formatDate(date)` (Intl, en-MY and MYR set at the top), and its test. Every amount, quantity and date on screen goes through it.

### Changed

- Locks: a row an Action only reads to check it (is the supplier active?) is not locked, and a lock test must fail once its `lockForUpdate()` is removed (docs/rules/actions.md, docs/rules/tests.md, principles 6 and 8).
- Select options are `{ value, label }` pairs from one model or enum method with one query for the whole list, never a full Resource or a query per option (docs/rules/frontend.md, principle 4).

## [2.12.1] - 2026-11-04

### Fixed

- `scripts/worktree-up` seeds the database, so the test user the reviewer logs in with (test@example.com) exists in every worktree. docs/workflow.md step 4 says how to create it elsewhere.

## [2.12.0] - 2026-11-04

"Avoid unnecessary secondary labels and descriptions. Let the layout do the explaining." AI-built screens tend to read like a manual; these rules keep the words to what the layout cannot say.

### Added

- A notification bell on the right of every page's top bar. It shows the unread count (9+ above nine) and opens a card with the five newest notifications, a mark-as-read button on each, "Mark all as read" and "View all". The sidebar no longer has a Notifications item. The card loads its notifications when it opens (`latestNotifications`, an `Inertia::optional()` shared prop); every page gets only the summary (`notificationSummary`: unread count and `can.mark_all_read`, which the notifications page now reads too, instead of `unreadNotifications`).
- `@radix-ui/react-popover` and shadcn's `Popover` component (`components/ui/popover.tsx`).
- Two `ui` rules: explaining text only for an unusual state (an error, an empty list, a locked record) or a step the user must take, with other details behind a Tooltip, a collapsible section or a detail page; and a change to a screen changes only what the plan names, preferring to remove or simplify over adding.

### Changed

- The layout does the explaining: no description under a heading unless the title alone is unclear, no slogans, no text about how the screen was built or what was asked for, nothing shown twice. DESIGN.md says the same in its Overview and Do's and Don'ts.
- Every input has a label; it may be hidden (`sr-only`, `aria-label`) only when the layout already says what the field is, never left out (W3C).
- The starter kit's and the reference feature's headings lose the descriptions that repeated their titles (settings, notifications, login, register, password pages, tasks). The passkeys description now says what a passkey does. The auth layout shows no empty description line.

## [2.11.0] - 2026-11-04

How screens look gets the same treatment as the code: one file says it, and the reviewer checks it. Based on OpenAI's frontend guidance, Anthropic's frontend-design skill, Google's DESIGN.md format, Vercel's Web Interface Guidelines and shadcn's skill.

### Added

- `DESIGN.md` for a quiet work tool, in Google's DESIGN.md format: tokens (colors, type, corners, spacing, components) and eight sections, ending with Do's and Don'ts. Its values are the theme in `resources/css/app.css`. It is a protected file.
- Principle 10, "Screens follow DESIGN.md": 15 `[review]` rules of the new kind `ui` (tokens only, one primary color, no nested cards, the same layout for the same kind of page, text that fits at phone width, components over hand-styled markup, familiar controls, empty states and delete confirmation, labelled inputs, focus and contrast, button labels that say what happens, screenshots).
- The reviewer opens every changed page with Playwright at 1280px and 390px width and once in dark mode, and checks the `ui` rules on the screenshots (`docs/prompts/review.md` step 5).
- `success` and `warning` theme colors, and `Badge` variants for them.

### Changed

- `docs/rules/visual-design.md` always applies; the builder also looks at its pages at desktop and phone width before reporting.
- Corners are 8px at most (`--radius: 0.5rem`).
- The starter kit's components use theme colors instead of raw ones (input errors, status messages, appearance tabs, avatar fallback, delete warning, footer links, logo, passkey label), and the dark sidebar logo is readable.
- The reference feature puts the page action in the header row, shows a message for an empty list, asks before deleting a task, and says "Save changes".

## [2.10.0] - 2026-11-02

Every rule now says who checks it and what it is about. Machines check structure and the steps of the work; whether the business logic is right is read by the reviewer. A red-team pass found about 150 ways around the checks; the structure ones are closed here.

### Changed

- Every rule in `docs/golden-principles.md` carries two tags: who checks it (`[PHPStan]`, `[oxlint]`, `[Structure]`, `[hook]`, `[review]`, `[user]`) and what it is about (`structure`, `business`, `process`). `tests/Structure/DocsTest.php` checks the format, and that a business rule is never given to a machine.
- Business rules are no longer machine checks. These moved to `[review]`: comparisons on model fields and enums outside app/Models and app/Enums (`ConditionsLiveInModelsRule` removed), a model method re-comparing a column its scope filters on, React permission, status, date and money logic (the oxlint rules `no-permission-checks`, `no-business-comparisons`, `no-date-math`, `no-money-math` and `no-hand-formatting` removed), hard-coded URLs, and `round()` / `number_format()` (merged into the money and "do not hand-write formatting" rules). oxlint keeps `fetch` / `axios`, UI libraries and component boundaries.
- The reviewer lists every `[review]` rule the change touches with `ok` or the finding, all the same; the four searches are gone. The 27-rule cap is gone.
- The weekly cleanup goes through every `[review]` rule, lists gaps in the machine checks with the code that shows them and a suggested check, and lists rules to merge or remove. The user decides; a check only gets stricter. In a project made from the template, the suggestion is for the template.
- The finish check counts only a plan written or changed in this conversation, with its purpose, decisions and milestones filled in; approval only from Decision Log lines added in this conversation; the "asking" exemption only when the message ends with the question and names the files. A plan with every milestone done is sent back to be completed. A step it lets go after three times is told to the user and to the next conversation. If the check itself fails, it says so instead of staying silent.
- `composer check` records a pass only for the code it started on (`checking` step) and only when the tests ran to the end (`tests/Structure/RunFinishedTest.php`).
- The starter kit's `.well-known/passkey-endpoints` route calls `Settings\PasskeyEndpointsController` instead of a closure. A Resource error points at the line that sends the model or the relation.
- More protected files: `.npmrc`, `.gitignore`, `artisan`, `tests/bootstrap.php`, `tests/Pest.php`, `tests/TestCase.php`, `tests/js/setup.ts`, `docs/workflow.md`, `docs/PLANS.md`, `docs/reference/`.

### Fixed

- The layer bans in `phpstan.neon` were partly never enforced: phpstan-disallowed-calls keeps only the last entry for a namespace or function, so a later entry replaced an earlier one (Models and Policies were allowed in Actions and Http, and the Action bans on `request()` and the like were replaced). Each namespace and function now appears once.

### Added

- PHPStan: writes are found by method family (`save*`, `update*`, `delete*`, `insert*`, `sync*`, `touch*` and the rest, also on query builders, relations, `DB::statement()`, mail and notifications); `Validator::*()` outside app/Http/Fortify; the request, session, auth and Gate helpers in every lower layer; seeders, factories and test code in the app; models as `->toArray()` or through `Inertia::share()`, `->with()` and `response()->json()`; a relation sent without its Resource (`whenLoaded()`); Actions exactly in `app/Actions/{Domain}/`; app/Http holds only Controllers, Requests, Resources, Middleware and Fortify.
- `tests/Structure/FoldersTest.php`: the fixed folders of app/, resources/js and tests/, pages in `pages/{domain}/`, components in `components/{domain}/` or `shared/`, test file names and places, only TypeScript in resources/js, only Markdown in docs/, no symlinks, no PHP in public/.
- `tests/Structure/RoutesTest.php`: every route named, no closures in routes/, a fixed path before a wildcard that catches it.
- `tests/Structure/ConfigTest.php`: the checks stay as set (PHPStan level, paths, extensions and rules, every Ezdev oxlint rule on with only generated code ignored, strict TypeScript, the three Pest suites, `composer check` itself, no other lint config, no `script-shell`), and PHPStan reads every folder composer autoloads.
- `tests/Structure/CodeTest.php`: components/ui imports no app code, no import leaves resources/js, PHP requires only PHP files, no PHP in Blade views, no CSS-only UI library (resources/css and package.json), and more ways of writing test rows by hand.
- `tests/Structure/ResourceTypesTest.php` reads the Resource with a PHP parser and finds Resources and types in subfolders: `toArray()` returns one literal array.
- Pest fails on a skipped or unfinished test (`phpunit.xml`). Turning a Fortify feature off means deleting its tests.
- CI also runs `composer check:reference`.

## [2.9.0] - 2026-11-02

Rules now show up at the moment the AI breaks them instead of relying on it remembering them. In the experiments, no machine-checked rule was ever broken; every miss was a `[review]` rule or a step of the workflow.

### Added

- Finish check (`scripts/hooks/finish-check.php`, run by `.claude/settings.json` and `.codex/hooks.json`): when the AI is about to stop, it sends back a missing step: code without a plan, a removed tech-debt line without a `docs/QUALITY.md` update, a protected file or dependency without the user's approval in the plan's Decision Log, code that no passing `composer check` has seen, or a completed plan without check results, review rounds or outcome. Each reason is sent back at most three times. `composer check` records the code it passed on. In Codex, trust the hook once in `/hooks`.
- Principle 9, "Work follows the steps", lists the workflow rules with who checks them (`[hook]`, `[user]`).
- Checks for nine rules that were `[review]` only:
  - PHPStan: rows are locked only in Actions, and an Action that locks has `{Action}LockTest.php`; no `number_format()`, `round()`, `floor()` or `ceil()` in app/; a controller reads input only from a FormRequest (`ControllerInputRule`); no events, listeners, observers or model event callbacks; no `bool` parameter in an Action's `handle()`; a Resource cannot send the model through `getAttributes()` and the like.
  - oxlint: no `toFixed()`, `padStart()` or `padEnd()` in React, `lib/format.ts` included (`no-hand-formatting`); a status or role copied into a variable is still a business comparison.
  - tests/Structure/CodeTest.php: no silenced check or skipped test, no rows inserted by hand in tests, and `lib/format.ts` has `tests/js/lib/format.test.ts`.
- At most 27 `[review]` rules, counted by `tests/Structure/DocsTest.php`.

### Changed

- The reviewer lists only the `[review]` rules the change touches, and always runs four searches (a rule written twice, React deciding a business rule, a lock where nothing collides, hand-written formatting), pasting each search. The weekly cleanup does the same, writes a plan and asks the reviewer.
- Review rounds get a Progress line. Dependency updates, starter-kit changes and template changes copied into a project write a plan that records the user's approval.
- A list page with filters reads them through a FormRequest (`IndexTaskRequest::status()`); `$request->enum()` in a controller is replaced by a FormRequest method.

## [2.8.5] - 2026-11-02

From the experiments' round of fixing their tech debt.

### Added

- `tests/Support/TestsRowLocks.php` for row-lock tests: `waitsForLock()` runs a second user's write on a second connection and says whether it had to wait. The file commits its rows and empties the tables after each test, because a second connection cannot see RefreshDatabase's uncommitted rows. Three experiments had each built their own version. Each `lockForUpdate()` has such a test.

### Changed

- A request to fix tech debt goes through every step like any other request: grill, plan, wait for "start". "Fix them all" or "commit on the current branch" is not the "start".
- Fixing a tech-debt line also updates that domain's grade and notes in `docs/QUALITY.md`.

## [2.8.4] - 2026-11-01

### Fixed

- `tests/Support/VirtualPasskey.php` pads the key's x and y to 32 bytes. OpenSSL drops leading zero bytes, so about 0.7% of test keys were too short and a passkey test failed now and then. Found by two experiments while clearing their tech debt.

## [2.8.3] - 2026-11-01

Decisions on the template problems still listed in the experiments' tech debt after 2.8.2.

### Added

- A toast on every page when a request cannot reach the server (`use-network-error-toast`).
- Frontend tests: `vp test` (Vitest, part of Vite+) runs `tests/js` in `composer check`, with jsdom and Testing Library. A hook with timing or ordering logic and every money or date formatter get a test.

### Changed

- Tests run on MySQL, like the app, in their own `<database>_test` (created on the first run; `check:reference` uses `<database>_reference_test`). Row locks can be tested.
- Register, reset password and security share `NewPasswordFields`; every new password field is required.
- Rule: no code for a state nothing can reach yet (no refusal or test stand-in for a status the app cannot create); the grilling asks only about what this part builds.

## [2.8.2] - 2026-11-01

From the redone weekly cleanup: eight experiments found 16 kinds of problems in the template itself. All are fixed here. Twelve commits come from the experiments' own cleanups (two-factor and passkey tests, the split two-factor dialog, shared code input).

### Changed

- Rules: the weekly cleanup may fix a stale path or name in AGENTS.md, ARCHITECTURE.md, docs/rules/ and golden-principles; a change to what a rule says is listed for the user. A short list inside another page is not a list page; the page size is 20 unless the plan agreed another. A name that is not blank counts as complete input. Per-row owner queries are accepted. The Validator rule says what PHPStan does.
- The dashboard and welcome pages moved to `pages/dashboard/index.tsx` and `pages/welcome/index.tsx`.
- Delete account shows only when deleting succeeds (`can.delete_account`, verified email). The verification notice comes from `verificationLinkSent`, not from comparing Fortify's status text.
- Initials come from `UserResource` (`Str::initials`); `use-initials` is gone. The passkey name starts empty instead of being guessed from the browser.
- oxlint catches a bare `status` or `role` comparison and a path on either side of `??` or `?:`.
- In-app notifications extend `InAppNotification`; the list breaks same-second ties by id.
- The two-factor setup dialog is split by job, the code input is one component, and the profile page uses `TextLink`.

### Removed

- The unused header layout (and `AppHeader`), card and split auth layouts.

### Fixed

- A passkey the WebAuthn library rejects is a validation error, not a 500.
- `passkey-verify.tsx` takes the dashboard address from Wayfinder.
- The test user's two-factor secret is valid, so tests can use real authenticator codes.
- Every Fortify and passkey endpoint is tested, and docs/QUALITY.md grades Users and Notifications.
- `.playwright-mcp/` is ignored.

## [2.8.1] - 2026-11-01

### Changed

- The ResourceTypesTest row left docs/exec-plans/tech-debt.md. It was not debt an AI could fix: four experiments tried Wayfinder's untagged dev-next branch to replace the test and found wrong types, and two switched to it anyway. docs/rules/frontend.md now says the test stays until a tagged Wayfinder release generates page prop types, and docs/prompts/upgrade.md checks for that release.

## [2.8.0] - 2026-11-01

From the round 1.5 weekly cleanup: eight experiments found the same template problems (13 kinds). Each one is fixed here, or the rule is worded so it no longer reads as broken. Six commits come from the experiments' own cleanup fixes.

### Changed

- Notifications: the list is paginated (20 per page) with the new `components/shared/pagination.tsx`. "Mark all as read" is shown from `can.mark_all_read` (the Policy and `User::hasUnreadNotifications()`), not from React's unread count; with nothing unread the Action refuses, and it runs in one transaction. "Newest first" is written once, in the `listOrder()` scope.
- Reference Tasks: the list is paginated, and the bulk button is shown from `can.complete_overdue` (`Task::hasOverdueFor()`) instead of `overdueCount > 0`. `CompleteOverdueTasks` refuses when nothing is overdue and runs in one transaction. `TaskButtonsTest` checks the page's button too. The `docs/rules/controllers.md` read example pages its list.
- The account components (two-factor, passkeys, delete user, appearance tabs) moved to `components/users/`.
- "Email still needs verifying" is a `User` method.
- Rule wording in docs/golden-principles.md and docs/rules/: the owned-record rule is for ids picked in a form (a record in the URL is the Policy's, 403); only a write endpoint that receives input needs a FormRequest; `min`, `max` and `step` are allowed HTML attributes, and keeping submit disabled until a code is complete is display; the appearance setting is a browser display preference, not business options; the starter kit's layout and general components stay at the components root.

### Fixed

- The settings redirect route is named.
- The settings endpoints are tested for guests and invalid input.

## [2.7.0] - 2026-11-01

From the first round of the model experiment: the in-session reviewer reported "No P0/P1 findings" in every experiment, yet a later review found a broken `[review]` rule in eight of ten. Half of them were outside the reviewer's short list of P1 kinds; the other half were on it and missed.

### Changed

- `docs/prompts/review.md`: P1 is any broken `[review]` rule in docs/golden-principles.md (plus behaviour not in the plan and a missing or weakened test). The reviewer goes through every `[review]` rule and writes one line for each, with two searches for the kinds missed most (a rule written twice; React deciding a business rule). Copied code and hand-written formatting are P1, not style.
- `docs/prompts/cleanup.md` uses the same rule-by-rule check, and may commit on the current branch when the user says so. The reviewer subagents (Claude Code, Codex) say the same.
- `docs/workflow.md`: tests are run before the code and must fail; the Progress line records how many failed and the `composer check` result.
- `scripts/worktree-up` installs npm packages with `npm ci`, so package-lock.json no longer changes in every worktree.

### Added

- Four `[review]` rules in docs/golden-principles.md, with the matching lines in docs/rules/: every list page is paginated; a `can.*` flag includes the business state and the reason for a refusal comes from the Resource; a record the user must own is looked up in the Action with one message for "missing" and "not yours"; rows are locked only where concurrent writes can collide, never for reading.

## [2.6.0] - 2026-11-01

The template has run on a real machine: the first-run plan passed every milestone and is in `docs/exec-plans/completed/`.

### Added

- `composer.lock`, `package-lock.json`, `boost.json` and the Boost skills for Claude Code and Codex.

### Changed

- The app runs on MySQL: `.env.example` uses `DB_CONNECTION=mysql` and the database `ezdev_template`. A new project sets `DB_DATABASE` to its own name; `composer setup` (`php artisan migrate --force`) creates the database, and `scripts/worktree-up` gives each worktree its own (`<database>_<name>`). Tests still run on SQLite in memory (phpunit.xml); raw SQL must work on both (docs/rules/tests.md).
- CI starts a MySQL 8.4 service for `composer setup`.
- README: a new project is set up with `composer setup`; the first-run plan is the record of the template's own first run.

### Fixed

- The forbidden-call rules also apply when the receiver may be null, such as `$request->user()->update()` (`lint/phpstan/DisallowedTypeResolver.php`). Found by the first run's deliberate violations.
- `PasskeyResource` declares its model with `@mixin`, so PHPStan knows its fields.

## [2.5.1] - 2026-11-01

Fixes for running several copies of the template at the same time.

### Fixed

- `scripts/worktree-up` also sets `SERVER_PORT` (the port `composer dev` serves on, so it matches `APP_URL`) and a `SESSION_COOKIE` of its own.
- PHPStan keeps its cache in `storage/framework/cache/phpstan` instead of the system temp folder, so projects running PHPStan at the same time never share one cache.

## [2.5.0] - 2026-11-01

Every machine check is now a linter rule: PHPStan for PHP, oxlint for React. A linter reads the code without running it, gives its error with what to do instead, and also runs in the editor. The template is renamed Ezdev Template (folder `Ezdev-Template`); versions live in git tags only.

### Changed

- The Pest architecture tests (`tests/Arch/`) became PHPStan rules: the layer rules are `disallowedNamespaces` in `phpstan.neon`; the class shapes, names and extra-layer rules are `lint/phpstan/ClassShapeRule.php` and `lint/phpstan/TraitPlacementRule.php`.
- The PHP Structure tests (`tests/Structure/ConventionsTest.php`) became PHPStan rules: `lint/phpstan/ModelScopesRule.php` (`#[Scope]`, a condition written once inside a model) and `lint/phpstan/CompanionFilesRule.php` (a test per Action, a factory per model, a buttons test per `can.*` Resource). The rule tells the result cache which files it looked for (PHPStan 2.3+).
- The frontend Structure tests became oxlint rules in `lint/oxlint/ezdev.js`, loaded by the `lint` block in `vite.config.ts`. They read the syntax tree instead of lines of text, and also catch `switch`, `.includes()` and `+=`. The UI-library check now looks at imports (`no-restricted-imports`) instead of package.json; a UI library that comes in through CSS only (daisyui) is a review rule.
- The custom PHPStan rules moved from `tests/PHPStan/` to `lint/phpstan/` (namespace `Lint\PHPStan`).
- Actions also may not use the `request()`, `response()`, `redirect()`, `back()`, `to_route()` and `inertia()` helpers or call `can()`.
- The Resource and TypeScript type comparison stays a test, `tests/Structure/ResourceTypesTest.php`, until Wayfinder generates the types (`docs/exec-plans/tech-debt.md`).

### Removed

- dependency-cruiser (`.dependency-cruiser.cjs`, `npm run depcruise`): its rules are `ezdev/component-boundaries` and `import/no-cycle` in oxlint.

## [2.4.0] - 2026-11-01

### Removed

- jscpd duplicate-code detection (`.jscpd.json`, `npm run dupes`), at the user's request. "No copied blocks of code" is a review rule again.

## [2.3.0] - 2026-11-01

Principles 1 (every rule written once) and 4 (the frontend never does the backend's job) are now mostly machine checks.

### Added

- Custom PHPStan rules in `tests/PHPStan/`: no comparison on a model field or enum case outside app/Models and app/Enums (`ConditionsLiveInModelsRule`); no model in Inertia page props or shared props (`PagePropsUseResourcesRule`).
- PHPStan: `where…` and `orWhere…` only inside app/Models (`whereKey()` allowed everywhere).
- tests/Structure: a model method may not compare a column one of its scopes filters on; `tests/Structure/FrontendTest.php` stops permission, status, date and money logic in React, hard-coded URLs, `fetch`, `axios` and other UI libraries, and checks that every Resource has a TypeScript type with the same fields.
- jscpd duplicate-code detection (`npm run dupes`, `.jscpd.json`), part of `composer check`.
- First-run M6 proves five more checks bite.

## [2.2.0] - 2026-11-01

The rules are reorganized under eight principles in `docs/golden-principles.md`, the one list of every rule and the check that stops it. Twelve rules that were review-only are now machine checks.

### Added

- `tests/Arch/ClassesTest.php`: controllers have only the standard methods; FormRequests have no `authorize()`; app/Enums holds enums only; no interfaces or DTO classes; `*Controller`, `*Request`, `*Resource`, `*Policy` names. The Action shape and no-Service/Repository rules moved here from LayersTest.
- `tests/Structure/ConventionsTest.php`: every Action has a test file; every Resource with `can.*` has a buttons test; every model has a factory; scopes use `#[Scope]`.
- PHPStan: no `$request->validate()`, `Validator::make()` or `validator()` outside FormRequests (Fortify adapters excepted); no database queries in FormRequests or app/Concerns; no `toArray()`, `attributesToArray()` or `parent::toArray()` in Resources.
- First-run M6 proves five more checks bite.

### Fixed

- Static model writes such as `User::create()` and `User::destroy()` outside Actions were not caught. The PHPStan entries named `App\Models\*`, but Larastan reports these methods as declared on the Eloquent Builder and Model, which is what spaze matches. The entries now name those classes.

### Changed

- `docs/golden-principles.md` rewritten: eight principles, each rule tagged with the check that stops it or `[review]`. ARCHITECTURE.md points to it instead of keeping its own rule table and rule list.
- The reviewer also flags a changed file over 300 lines that does more than one job.

### Removed

- The 250-line file limit (`tests/Structure/FileSizeTest.php`). Files are split by job, not by size.

## [2.1.0] - 2026-11-01

The starter kit's own code now follows the v2 rules, so no check has an exception for it. In-app notifications are added as a live example of the patterns the starter kit cannot show.

### Added

- In-app notifications: a notifications page, "Mark as read" and "Mark all as read", and an unread badge in the sidebar. Users are notified when their account is created, their password changes and their email address changes.
- `App\Models\Notification` (extends Laravel's `DatabaseNotification`) with `ownedBy()` and `unread()` scopes, `NotificationPolicy`, `MarkNotificationRead` and `MarkAllNotificationsRead`, `NotificationResource` with `can.mark_read`, and a button consistency test.
- Arch rule: notifications use no actions, http or integrations. PHPStan: `markAsRead()`, `markAsUnread()`, `notify()` and `Notification::send()` only in Actions.
- `docs/prompts/upgrade.md`: how to take dependency updates, starter kit changes and a new major Laravel version.
- Actions that write the user and a notification do both in one `DB::transaction()`; `DeleteUser` also deletes the user's notifications.

### Changed

- Writes moved into Actions in `app/Actions/Users/`: `CreateUser`, `UpdateProfile` (owns "a changed email is unverified again"), `UpdatePassword` (called by both the security page and password reset), `DeleteUser`.
- Fortify's `CreateNewUser` and `ResetUserPassword` moved from `app/Actions/Fortify/` to `app/Http/Fortify/`. They check the input shape and call an Action.
- Settings controllers are thin: authorize, call one Action, return. New `UserPolicy`: only the owner may change or delete an account.
- `auth.user` is sent through `UserResource` instead of the raw model; the passkey list through `PasskeyResource`, wrapped in `data` like every collection. The TypeScript `User` type matches `UserResource`.
- The profile page receives `emailNeedsVerification` from the backend instead of checking `email_verified_at` in React.
- The theme cookie is read through the `Appearance` enum (`light`, `dark`, `system`); an unknown value falls back to `system`.
- The starter kit's tests are converted to Pest. New tests for each Action, the shared user and the theme cookie.
- `docs/QUALITY.md` grades the Users domain like any other.

### Removed

- Laravel Chisel (`chisel.php`, `chisel-paths.php`, `install:features`, every `@chisel-*` marker). Every auth feature stays.
- `tests/Unit/ExampleTest.php` (it only asserted `true`); `tests/Unit/Enums/AppearanceTest.php` takes its place.
- The scaffolding exceptions in `tests/Arch/LayersTest.php` and `phpstan.neon`.

## [2.0.0] - 2026-10-31

A rebuild on a fresh `laravel/react-starter-kit` (main, 98d9b82). It keeps the ideas of Ezdev 0.8.0 and changes how they are delivered to the AI and how they are enforced.

### Kept from 0.8.0

- Every business rule lives in one owner; the "where things go" table; "one condition, one written form".
- Grilling: rounds of questions with a recommended answer each, decisions always put to the user.
- The Tasks reference feature (rewritten for v2, now in `docs/reference/tasks/`) and the button consistency test.
- Pest, Larastan level 7, the Action architecture tests, the PHPStan rule on `Enum::from()`.
- Boost MCP and Playwright MCP for Claude Code and Codex. Optional `DESIGN.md`.

### Added

- Hand-written `AGENTS.md` of at most 100 lines that works as a map; `CLAUDE.md` points to it. Details in `docs/`.
- ExecPlans (`docs/PLANS.md`, `docs/exec-plans/active|completed/`, `tech-debt.md`): the agreed plan, progress and decisions live in the repository, not in the chat.
- Layer and dependency rules as Pest architecture tests (`tests/Arch/LayersTest.php`), including `app/Integrations/` as the only entry for external services and no Service or Repository classes.
- PHPStan rule: model writes only in Actions.
- dependency-cruiser: domain components do not import each other; nothing imports a page; no circular imports.
- Structure tests: files under 250 lines, `AGENTS.md` under 100 lines with valid paths, ExecPlans with the required sections.
- `explorer` and `reviewer` subagents for Codex and Claude Code; review and weekly cleanup prompts; `docs/golden-principles.md` and `docs/QUALITY.md`.
- `composer check` (every check in one command), `composer check:reference`, `scripts/worktree-up`.
- `docs/exec-plans/active/first-run.md`: the first task for the AI on a new machine.

### Removed

- Rule files generated by Boost from `.ai/` (`boost:update`); Boost now provides MCP and skills only.
- `composer dup` and its 548 MB model; review is done by the `reviewer` subagent and the weekly cleanup.
