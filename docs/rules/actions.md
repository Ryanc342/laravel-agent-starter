# Actions

Applies to `app/Actions/**`.

An Action is one business operation: it checks the business conditions and makes every database write for that operation.

## Shape

- `final class {Verb}{Noun}` in `app/Actions/{Domain}/` (`PlaceOrder`, `CancelOrder`, `CompleteTask`).
- One public method, `handle()`, with plain typed parameters: models, validated arrays, enums, values. Never a `Request`.
- Other Actions are injected through the constructor and called through their `handle()`.

`tests/Arch/LayersTest.php` enforces: final, only `__construct` and `handle` are public, no HTTP classes, no Gate.

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

- The Action checks every business precondition itself (stock, record status, limits, eligibility) by calling the owner (enum method, model method or scope). It never restates the condition.
- A refusal throws `ValidationException::withMessages([...])` keyed by the field the user should fix.
- Permission is not checked here. The controller asked the Policy once; the Action does not ask again.

## Writes

- Every `create`, `update`, `save`, `delete` and pivot change happens in an Action. PHPStan rejects them elsewhere.
- Several writes that must succeed together go in `DB::transaction(...)`.
- Lock rows (`lockForUpdate()`) only for real concurrent writes on the same record, such as stock or balances.
- Money is integer cents. Multiply decimals with `Brick\Math\BigDecimal`.

## One operation, one Action

- No mode switches through nullable or boolean parameters (`handle($order, $force = false)`). Two behaviours are two Actions.
- An operation that repeats another (complete all overdue tasks) loops and calls the other Action instead of copying its checks and writes. See `docs/reference/tasks/app/Actions/Tasks/CompleteOverdueTasks.php`.
- External services are called through a class in `app/Integrations/`, injected into the Action.

## Tests

Each Action has `tests/Feature/{Domain}/{Action}Test.php` that calls `handle()` directly: the happy path and every refusal. A refusal test also checks that the database did not change.
