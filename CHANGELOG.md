# Changelog

Versions follow [Semantic Versioning](https://semver.org): a new rule or behaviour change bumps the minor version, a wording fix bumps the patch version.

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
