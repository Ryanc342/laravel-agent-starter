# Tests

Applies to `tests/**`.

Write tests with Pest. Follow `docs/reference/tasks/tests/Feature/Tasks/` as the reference. Write them before the code.

## What every feature needs

| Test file                                          | Covers                                                                                                                |
| -------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| `tests/Feature/{Domain}/{Action}Test.php`          | Each Action called directly: happy path and every business rule violation                                             |
| `tests/Feature/{Domain}/{Model}ControllerTest.php` | Each endpoint: guest redirected, owner allowed, other user forbidden (403), invalid input rejected, success redirects |
| `tests/Feature/{Domain}/{Model}ButtonsTest.php`    | Consistency: each `can.*` button and each form's choices agree with what the endpoint accepts                         |

`tests/Arch/` and `tests/Structure/` belong to the template. Do not edit them without the user's approval.

## Consistency tests

When the same rule is used in two places, one test checks that both give the same answer for every case. Write one for:

- **Each `can.*` button:** for every combination of user and record (own or another user's, each status), the button is shown exactly when pressing it succeeds. See `docs/reference/tasks/tests/Feature/Tasks/TaskButtonsTest.php` and `tests/Feature/Notifications/NotificationButtonsTest.php`.
- **Each form's choices:** create one record per case (right type and active, wrong type, inactive, ...), then for each record check that saving it succeeds exactly when it is in the list the page receives.

These values are few (roles, statuses, active or not, own or not), so list every combination with a Pest dataset instead of picking examples.

## Style

- Start each file with `uses(RefreshDatabase::class);`.
- One `test('...')` per rule. The name describes the rule in plain words: `test('a completed task cannot be completed again', ...)`.
- Build data with factories and their states (`Task::factory()->for($user)->overdue()->create()`). Never insert rows by hand.
- Check values with `expect()`: `expect($task->refresh()->status)->toBe(TaskStatus::Done)`.
- A rule that must refuse: `->throws(ValidationException::class)` on the test, or `expect(fn () => ...)->toThrow(...)` when you still check the database afterwards.
- Assert the outcome in the database (`$this->assertDatabaseHas`, `$task->refresh()`), not just the response code.
- For Inertia pages use `assertInertia(fn (Assert $page) => $page->component('tasks/index')->has('tasks.data', 2))`.

## When a test fails

Fix the code, not the test. Change an expected result only when the user changed the requirement or the assertion is clearly wrong, and say why. Never delete or skip a test without the user's approval.
