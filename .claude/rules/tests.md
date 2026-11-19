---
paths:
  - "tests/**"
---

# Tests

Write tests with Pest, before the code, following `docs/reference/tasks/tests/Feature/Tasks/`. A rule names its check in its tag, with its lane and level, and says why in one sentence (docs/golden-principles.md); text without a tag explains how to follow them. When a test fails, and when an existing assertion may change or go: AGENTS.md, "Red lines".

## What every feature needs

- `[review:backend:2]` Every Action has `tests/Feature/{Domain}/{Action}Test.php` that calls `handle()` directly: the happy path, and every ValidationException the Action throws with a check that the database did not change. Why: an untested refusal is found broken by a user.
- `[review:backend:2]` Every endpoint is tested for the cases it has (`tests/Feature/{Domain}/{Model}ControllerTest.php`): guests, success, another user's record (403) when it takes an owned record, and invalid input when it takes input. Why: an endpoint's permission and validation break silently otherwise. Not a break: no invalid-input test for an endpoint with no input, no other-user test for one with no owned record.
- `[review:backend:1]` Every Resource that sends `can.*` has a `…ButtonsTest.php` in tests/Feature/{Domain}/, named after the model or the Resource, that proves every `can.*` flag it sends matches the Policy (copy docs/reference/tasks/tests/Feature/Tasks/TaskButtonsTest.php). Why: a button shown when pressing it fails, or hidden when it would succeed, is wrong behaviour.
- `[review:backend:1]` Every form's choices have a consistency test: a value is accepted exactly when it is in the list the page receives. Why: a list and a check that disagree offer what is refused, or refuse what is offered.

Tests run on MySQL, like the app, in a database of their own: the app's database name plus `_test`, created on the first run (tests/bootstrap.php). So a row lock can be tested too (Row locks, below). MySQL must be running for `composer check`.

## Consistency tests

When the same rule is used in two places, one test checks that both give the same answer for every case:

- **Each `can.*` button:** for every combination of user and record (own or another user's, each status), the button is shown exactly when pressing it succeeds. See `docs/reference/tasks/tests/Feature/Tasks/TaskButtonsTest.php` and `tests/Feature/Notifications/NotificationButtonsTest.php`.
- **Each form's choices:** create one record per case (right type and active, wrong type, inactive, ...), then for each record check that saving it succeeds exactly when it is in the list the page receives.

These values are few (roles, statuses, active or not, own or not), so list every combination with a Pest dataset instead of picking examples.

## Frontend tests

- `[review:backend:2]` A hook with timing or ordering logic (wait before asking, the latest answer wins, retry) has a Vitest test in tests/js. Why: timing bugs do not show when you click through once.
- `[review:backend:3]` resources/js/lib/format.ts, the money, quantity and date formatters, has `tests/js/lib/format.test.ts` (or `.tsx`). Why: the template ships it with that test, and a project keeps it.

A frontend test checks what a user does or hears: clicks, keys, focus, the URL, the request sent, roles and names, translated text. It never checks which class a component carries; how a screen looks is guarded by the shared components and by the reviewers. A component or hook with behaviour (a toggle, a menu, a dialog, a selection, a calendar's today mark, the cap of the validation toasts) gets a Vitest test too, mirroring the path (`tests/js/hooks/use-network-error-toast.test.tsx`); a component that only lays out markup gets none. A Pest test that renders a page added since the last build needs `npm run build` first, or Vite cannot find the page in its manifest. Import from `vite-plus/test`, render a hook with `renderHook` from `@testing-library/react`. `npm test` runs them, and `composer check` includes it.

## Row locks

- `[review:backend:1]` An Action that locks a row, or the shared `Lock{Model}` Action that locks it for several (.claude/rules/actions.md, Writes), has `tests/Feature/{Domain}/{Action}LockTest.php` with `uses(TestsRowLocks::class)`. Why: a lock nobody tested may protect nothing. Not a break: when nothing writes the row at the same time, the lock goes, and with it the test.

Test each `lockForUpdate()` by playing a second user who saves at the same moment. Put the test in its own file, `tests/Feature/{Domain}/{Action}LockTest.php`, starting with `uses(TestsRowLocks::class);` (tests/Support/TestsRowLocks.php) instead of RefreshDatabase: a second connection only sees committed rows, so this file commits its rows and empties the tables after each test. While the Action holds the lock (listen for a model event such as `Task::deleting(...)`), run the other user's write with `$this->waitsForLock(fn () => ...)` and expect `true`. Load the records that write changes inside the closure. `tests/Feature/TestsRowLocksTest.php` shows the calls. When a shared `Lock{Model}` Action holds the lock for several writing Actions (.claude/rules/actions.md, Writes), only `Lock{Model}LockTest.php` exists; its second write goes through one of the Actions that call it. Then remove the `lockForUpdate()` once and run the test: it must fail. A lock test that still passes without its lock proves nothing; make the second write go through the same Action, or through the step the lock protects.

## Files and data

- `[review:backend:2]` Test data comes from factories, never from `DB::insert()`, `DB::table(…)->insert()`, `Model::create()` (also through `query()`, `forceCreate`, `createQuietly`, `createOrFirst`, `firstOrCreate` and `updateOrCreate`), `insert()`, `upsert()`, `updateOrInsert()` or `INSERT` statements in tests/ (tests/Structure excepted). Why: a record built by hand skips the factory's states and drifts from what the app creates.
- `[review:backend:3]` tests/ holds only Feature, Unit, Structure, Support, js, bootstrap.php, Pest.php and TestCase.php; every PHP file in tests/Feature or tests/Unit ends in `Test.php` (helpers go in tests/Support); Vitest files are `tests/js/**/*.test.ts(x)`, and tests/js holds only those and setup.ts. Why: every other test is found there by its runner.

Factories are for tests: apart from the test accounts of database/seeders, no row in the app's own database is made by a factory, and the data for looking at pages comes from the walk through the forms (docs/workflow.md, step 4).

`lint/`, `tests/Structure/` and the template's helpers in `tests/Support/` belong to the template: a change to a check there is an agreed decision of the plan (docs/workflow.md, step 2). No test is skipped or unfinished (.claude/rules/checks.md); turning a Fortify feature off in config/fortify.php means deleting that feature's tests with it.

## Style

- Start each file with `uses(RefreshDatabase::class);`, or a row-lock test file with `uses(TestsRowLocks::class);` (Row locks, above).
- Name helper functions after the model (`taskCase()`, `notificationButtonsFor()`). Pest helpers are global, so two helpers with the same name stop the whole test run.
- One `test('...')` per rule. The name describes the rule in plain words: `test('a completed task cannot be completed again', ...)`.
- Build data with factories and their states (`Task::factory()->for($user)->overdue()->create()`).
- A test whose result depends on the date or time sets the clock with `freezeTime()` or `travelTo()`, never `Carbon::setTestNow()`. A fake (`Notification::fake()`, `Http::fake()`) is made inside the test that needs it, not in `beforeEach()`, and names the classes it expects when it can.
- Never mock a model to fake a state. If no factory state or Action can create the state, the code for it should not exist yet (.claude/rules/app.md, Keep it simple).
- Check values with `expect()`: `expect($task->refresh()->status)->toBe(TaskStatus::Done)`.
- A rule that must refuse: `->throws(ValidationException::class)` on the test, or `expect(fn () => ...)->toThrow(...)` when you still check the database afterwards.
- Assert the outcome in the database (`$this->assertDatabaseHas`, `$task->refresh()`), not just the response code.
- For Inertia pages use `assertInertia(fn (Assert $page) => $page->component('tasks/index')->has('tasks.data', 2))`.
