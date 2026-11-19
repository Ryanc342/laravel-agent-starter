---
paths:
  - "app/Actions/**"
  - "app/Integrations/**"
---

# Actions

An Action is one business operation: it checks the business conditions and makes every database write for that operation. A rule names its check in its tag (docs/golden-principles.md); text without a tag explains how to follow them.

## Shape

- `[review:input]` Actions live in `app/Actions/{Domain}/{Verb}{Noun}.php`, one folder per business domain: never directly in app/Actions, never in a deeper folder.
- `[review:input]` An Action is named `{Verb}{Noun}` (`SendQuote`, `CompleteOverdueTasks`).
- `[review:business]` An Action is final, has one public method, `handle()` (and a constructor), and does one operation; its other methods are private. `handle()` takes plain typed parameters: models, validated arrays, enums, values, never a `Request`. Other Actions are injected through the constructor and called through their `handle()`. app/Actions holds no traits: steps two Actions share go in a third Action that both call.
- `[review:business]` `handle()` takes no `bool` parameter (typed, nullable, `true` or `false`, in a union, or in the docblock): a switch between two behaviours is two Actions (`ArchiveTask` and `RestoreTask`).

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

The Action checks every business precondition itself (stock, record status, limits, eligibility) by calling its owner (enum method, model method or scope), and a refusal throws `ValidationException::withMessages([...])` keyed by the field the user should fix.

## Writes

- `[review:business]` Writes that must succeed together run in one `DB::transaction()`.
- `[review:business]` A bulk operation, or one that repeats another (complete all overdue tasks), loops over the single-record Action instead of copying its checks and writes (`docs/reference/tasks/app/Actions/Tasks/CompleteOverdueTasks.php`).
- `[review:input]` Lock rows only where concurrent writes can collide, such as stock, balances or a number counter. Reading never locks, also not a row an Action only reads to check it: saving an order that checks "is the supplier active?" does not lock the supplier, because deactivating it a moment later gives the same result, and the lock would make everyone who edits that supplier wait for every order save.
- `[review:input]` `lockForUpdate()`, `sharedLock()` and `lock()` are called only in Actions of an operation that writes, inside its `DB::transaction()` (tests/ excepted; pages and read-only operations never lock). When two or more Actions lock the row of the same model, the lock is written once, in a shared Action `Lock{Model}` (`LockOrder`) that each writing Action calls first inside its transaction: that Action may lock without writing, and it alone has the lock test. An Action that locks has `tests/Feature/{Domain}/{Action}LockTest.php` with `uses(TestsRowLocks::class)` (.claude/rules/tests.md, Row locks); when nothing writes the row at the same time, the lock goes.
- `[review:input]` Money is stored as integer cents, multiplied with `Brick\Math\BigDecimal` and rounded with its `RoundingMode`, never with `round()`, `floor()`, `ceil()` or `number_format()`.

## External services

An external service is called through a class in `app/Integrations/`, injected into the Action. Its HTTP requests set a timeout (`Http::timeout()` and `connectTimeout()`), retry only what is safe to send twice (a read, or a write with an idempotency key), and turn a failed answer into an exception (`->throw()`). Its tests fake every request with `Http::fake()` and `Http::preventStrayRequests()`.
