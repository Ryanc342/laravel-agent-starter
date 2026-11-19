# Tests

Applies to `tests/**`.

Write tests with Pest. Follow `docs/reference/tasks/tests/Feature/Tasks/` as the reference. Write them before the code.

## What every feature needs

| Test file                                          | Covers                                                                                                                |
| -------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| `tests/Feature/{Domain}/{Action}Test.php`          | Each Action called directly: happy path and every business rule violation                                             |
| `tests/Feature/{Domain}/{Model}ControllerTest.php` | Each endpoint: guest redirected, owner allowed, other user forbidden (403), invalid input rejected, success redirects |
| `tests/Feature/{Domain}/{Model}ButtonsTest.php`    | Consistency: each `can.*` button and each form's choices agree with what the endpoint accepts; named after the model or after the Resource that sends the `can.*` flags |

Tests run on MySQL, like the app, in a database of their own: the app's database name plus `_test`, created on the first run (tests/bootstrap.php). So a row lock can be tested too (Row locks, below). MySQL must be running for `composer check`.

The reviewers check that every Action has its test file and every Resource with `can.*` has its buttons test (lane `tests`). `lint/`, `tests/Structure/` and `tests/Support/` belong to the template, and their files are protected (AGENTS.md, "Red lines").

## Which tests run when

While building, run the smallest relevant tests. After each milestone, `composer check:milestone -- <affected test files or directories>` runs every static check and those tests: the tests of what changed and of its callers, also when a test file itself did not change. The finished plan passes full `composer check`, again after the final review's code fixes.

## Frontend tests

A frontend test checks what a user does or hears: clicks, keys, focus, the URL, the request sent, roles and names, translated text. It never checks which class a component carries; how a screen looks is guarded by the shared components, by the reviewers (principle 10 in docs/golden-principles.md) and by the browser screenshots each plan takes. Three kinds of frontend code get a Vitest test in `tests/js/`, mirroring the path (`tests/js/hooks/use-network-error-toast.test.tsx`): a hook with timing or ordering logic (wait before asking the server, the latest answer wins, retry); a component or hook with behaviour (a toggle, a menu, a dialog, a selection, a calendar's today mark, the cap of the validation toasts); and the money and date formatters in `resources/js/lib/format.ts` (their test is `tests/js/lib/format.test.ts`). A component that only lays out markup gets no test. A Pest test that renders a page added since the last build needs `npm run build` first, or Vite cannot find the page in its manifest. Import from `vite-plus/test`, render a hook with `renderHook` from `@testing-library/react`. `npm test` runs them, and `composer check` includes it.

## Consistency tests

When the same rule is used in two places, one test checks that both give the same answer for every case. Write one for:

- **Each `can.*` button:** for every combination of user and record (own or another user's, each status), the button is shown exactly when pressing it succeeds. See `docs/reference/tasks/tests/Feature/Tasks/TaskButtonsTest.php` and `tests/Feature/Notifications/NotificationButtonsTest.php`.
- **Each form's choices:** create one record per case (right type and active, wrong type, inactive, ...), then for each record check that saving it succeeds exactly when it is in the list the page receives.

These values are few (roles, statuses, active or not, own or not), so list every combination with a Pest dataset instead of picking examples.

## Row locks

Test each `lockForUpdate()` by playing a second user who saves at the same moment. Put the test in its own file, `tests/Feature/{Domain}/{Action}LockTest.php`, starting with `uses(TestsRowLocks::class);` (tests/Support/TestsRowLocks.php) instead of RefreshDatabase: a second connection only sees committed rows, so this file commits its rows and empties the tables after each test. While the Action holds the lock (listen for a model event such as `Task::deleting(...)`), run the other user's write with `$this->waitsForLock(fn () => ...)` and expect `true`. Load the records that write changes inside the closure. `tests/Feature/TestsRowLocksTest.php` shows the calls. When a shared `Lock{Model}` Action holds the lock for several writing Actions (docs/rules/actions.md, Writes), only `Lock{Model}LockTest.php` exists; its second write goes through one of the Actions that call it. Then remove the `lockForUpdate()` once and run the test: it must fail. A lock test that still passes without its lock proves nothing; make the second write go through the same Action, or through the step the lock protects.

## Style

- Start each file with `uses(RefreshDatabase::class);`, or a row-lock test file with `uses(TestsRowLocks::class);` (Row locks, above).
- Name helper functions after the model (`taskCase()`, `notificationButtonsFor()`). Pest helpers are global, so two helpers with the same name stop the whole test run.
- One `test('...')` per rule. The name describes the rule in plain words: `test('a completed task cannot be completed again', ...)`.
- Build data with factories and their states (`Task::factory()->for($user)->overdue()->create()`). Factories are for tests: apart from the test accounts of database/seeders, no row in the app's own database is made by a factory; the data for looking at pages comes from the walk through the forms (docs/workflow.md, step 4). Never insert rows by hand: no `DB::insert()`, `DB::table(…)->insert()`, `Model::create()`, `firstOrCreate()`, `updateOrCreate()`, `upsert()` and `INSERT` statements in tests/.
- Never skip a test or silence a check: tests/Structure/CodeTest.php rejects `->skip()`, `->todo()`, `@phpstan-ignore`, `oxlint-disable` and the like, and Pest fails on any skipped or unfinished test (phpunit.xml). Turning a Fortify feature off in config/fortify.php means deleting that feature's tests with it.
- Pest files end in `Test.php` inside tests/Feature or tests/Unit; helpers go in tests/Support; Vitest files are `tests/js/**/*.test.ts(x)`.
- A test whose result depends on the date or time sets the clock with `freezeTime()` or `travelTo()`, never `Carbon::setTestNow()`. A fake (`Notification::fake()`, `Http::fake()`) is made inside the test that needs it, not in `beforeEach()`, and names the classes it expects when it can.
- Never mock a model to fake a state. If no factory state or Action can create the state, the code for it should not exist yet (docs/golden-principles.md, principle 6).
- Check values with `expect()`: `expect($task->refresh()->status)->toBe(TaskStatus::Done)`.
- A rule that must refuse: `->throws(ValidationException::class)` on the test, or `expect(fn () => ...)->toThrow(...)` when you still check the database afterwards.
- Assert the outcome in the database (`$this->assertDatabaseHas`, `$task->refresh()`), not just the response code.
- For Inertia pages use `assertInertia(fn (Assert $page) => $page->component('tasks/index')->has('tasks.data', 2))`.

## When a test fails

Fix the code, not the test. When an existing assertion may change or go: AGENTS.md, "Red lines".
