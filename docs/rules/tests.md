# Tests

Applies to `tests/**`.

Write tests with Pest. Follow `docs/reference/tasks/tests/Feature/Tasks/` as the reference. Write them before the code.

## What every feature needs

| Test file                                          | Covers                                                                                                                |
| -------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| `tests/Feature/{Domain}/{Action}Test.php`          | Each Action called directly: happy path and every business rule violation                                             |
| `tests/Feature/{Domain}/{Model}ControllerTest.php` | Each endpoint: guest redirected, owner allowed, other user forbidden (403), invalid input rejected, success redirects |
| `tests/Feature/{Domain}/{Model}ButtonsTest.php`    | Consistency: each `can.*` button and each form's choices agree with what the endpoint accepts                         |

Tests run on MySQL, like the app, in a database of their own: the app's database name plus `_test`, created on the first run (tests/bootstrap.php). So a row lock can be tested too (Row locks, below). MySQL must be running for `composer check`.

PHPStan checks that every Action has its test file and every Resource with `can.*` has its buttons test (lint/phpstan/CompanionFilesRule.php). `lint/`, `tests/Structure/` and `tests/Support/` belong to the template. Do not edit them without the user's approval.

## Frontend tests

React only displays, so pages and components get no tests. Two kinds of frontend code do: a hook with timing or ordering logic (wait before asking the server, the latest answer wins, retry) and every money or date formatter in `resources/js/lib`. They get a Vitest test in `tests/js/`, mirroring the path (`tests/js/hooks/use-network-error-toast.test.tsx`). Import from `vite-plus/test`, render a hook with `renderHook` from `@testing-library/react`. `npm test` runs them, and `composer check` includes it.

## Consistency tests

When the same rule is used in two places, one test checks that both give the same answer for every case. Write one for:

- **Each `can.*` button:** for every combination of user and record (own or another user's, each status), the button is shown exactly when pressing it succeeds. See `docs/reference/tasks/tests/Feature/Tasks/TaskButtonsTest.php` and `tests/Feature/Notifications/NotificationButtonsTest.php`.
- **Each form's choices:** create one record per case (right type and active, wrong type, inactive, ...), then for each record check that saving it succeeds exactly when it is in the list the page receives.

These values are few (roles, statuses, active or not, own or not), so list every combination with a Pest dataset instead of picking examples.

## Row locks

Test each `lockForUpdate()` by playing a second user who saves at the same moment. Put the test in its own file, `tests/Feature/{Domain}/{Action}LockTest.php`, starting with `uses(TestsRowLocks::class);` (tests/Support/TestsRowLocks.php) instead of RefreshDatabase: a second connection only sees committed rows, so this file commits its rows and empties the tables after each test. While the Action holds the lock (listen for a model event such as `Task::deleting(...)`), run the other user's write with `$this->waitsForLock(fn () => ...)` and expect `true`. Load the records that write changes inside the closure. `tests/Feature/TestsRowLocksTest.php` shows the calls.

## Style

- Start each file with `uses(RefreshDatabase::class);`, or a row-lock test file with `uses(TestsRowLocks::class);` (Row locks, above).
- Name helper functions after the model (`taskCase()`, `notificationButtonsFor()`). Pest helpers are global, so two helpers with the same name stop the whole test run.
- One `test('...')` per rule. The name describes the rule in plain words: `test('a completed task cannot be completed again', ...)`.
- Build data with factories and their states (`Task::factory()->for($user)->overdue()->create()`). Never insert rows by hand.
- Never mock a model to fake a state. If no factory state or Action can create the state, the code for it should not exist yet (docs/golden-principles.md, principle 6).
- Check values with `expect()`: `expect($task->refresh()->status)->toBe(TaskStatus::Done)`.
- A rule that must refuse: `->throws(ValidationException::class)` on the test, or `expect(fn () => ...)->toThrow(...)` when you still check the database afterwards.
- Assert the outcome in the database (`$this->assertDatabaseHas`, `$task->refresh()`), not just the response code.
- For Inertia pages use `assertInertia(fn (Assert $page) => $page->component('tasks/index')->has('tasks.data', 2))`.

## When a test fails

Fix the code, not the test. Change an expected result only when the user changed the requirement or the assertion is clearly wrong, and say why. Never delete or skip a test without the user's approval.
