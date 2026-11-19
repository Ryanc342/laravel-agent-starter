# Actions

Applies to `app/Actions/**`.

An Action is one business operation: it checks the business conditions and makes every database write for that operation.

## Shape

- `final class {Verb}{Noun}` in `app/Actions/{Domain}/` (`PlaceOrder`, `CancelOrder`, `CompleteTask`).
- One public method, `handle()`, with plain typed parameters: models, validated arrays, enums, values. Never a `Request`.
- Other Actions are injected through the constructor and called through their `handle()`.

phpstan.neon rejects HTTP classes and helpers in Actions. The reviewers check the rest (lane `business`): final, only `__construct` and `handle` public, and no Gate or `can()`.

```php
final class CompleteTask
{
    public function handle(Task $task): Task
    {
        if (! $task->canBeCompleted()) {
            throw ValidationException::withMessages([
                'task' => __('This task is already completed.'),
            ]);
        }

        $task->update(['status' => TaskStatus::Done, 'completed_at' => now()]);

        return $task;
    }
}
```

## Business conditions

- The Action checks every business precondition itself (stock, record status, limits, eligibility) by calling the owner (enum method, model method or scope). It never restates the condition: the reviewers look for `where…` outside model scopes and for comparisons on model fields or enum cases.
- A refusal throws `ValidationException::withMessages([...])` keyed by the field the user should fix.
- Permission is not checked here. The controller asked the Policy once; the Action does not ask again.

## Writes

- Every `create`, `update`, `save`, `delete` and pivot change happens in an Action. The reviewers report them anywhere else.
- Several writes that must succeed together go in `DB::transaction(...)`.
- Lock rows (`lockForUpdate()`) only for real concurrent writes on the same record, such as stock, balances or a number counter. Pages and Actions that only read never lock, and neither does a row an Action only reads to check it: saving an order that checks "is the supplier active?" does not lock the supplier, because deactivating it a moment later gives the same result, and the lock would make everyone who edits that supplier wait for every order save. Only Actions may lock, inside the transaction of an operation that writes. When two or more Actions lock the row of the same model, write the lock once, in a shared Action `Lock{Model}` (`LockOrder`) that each writing Action calls first inside its transaction: it locks without writing, and it alone has the lock test. An Action that locks has `tests/Feature/{Domain}/{Action}LockTest.php` (docs/rules/tests.md, Row locks).
- Money is integer cents. Multiply decimals with `Brick\Math\BigDecimal` and round with its `RoundingMode`, never with `round()`, `floor()`, `ceil()` or `number_format()` (the reviewers check this).

## One operation, one Action

- No mode switches: `handle()` takes no bool parameter (`handle($order, $force = false)`). Two behaviours are two Actions.
- No events, listeners or observers: when a second step must follow, the Action calls that step's Action.
- An operation that repeats another (complete all overdue tasks) loops and calls the other Action instead of copying its checks and writes. See `docs/reference/tasks/app/Actions/Tasks/CompleteOverdueTasks.php`.
- External services are called through a class in `app/Integrations/`, injected into the Action. Its HTTP requests set a timeout (`Http::timeout()` and `connectTimeout()`), retry only what is safe to send twice (a read, or a write with an idempotency key), and turn a failed answer into an exception (`->throw()`). Its tests fake every request with `Http::fake()` and `Http::preventStrayRequests()`.

## Tests

Each Action has `tests/Feature/{Domain}/{Action}Test.php` that calls `handle()` directly: the happy path and every refusal. A refusal test also checks that the database did not change.
