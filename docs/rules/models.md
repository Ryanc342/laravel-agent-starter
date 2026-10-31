# Models, Enums, Policies and the database

Applies to `app/Models/**`, `app/Enums/**`, `app/Policies/**` and `database/**`.

## One condition, one written form

Before writing a condition, decide what it is about and write it once:

| It is about                              | Write it as                     | Others ask it like this                                  |
| ---------------------------------------- | ------------------------------- | -------------------------------------------------------- |
| A fixed value (status, type, role)       | Enum method                     | `$task->status->isOpen()`; queries use `openValues()`     |
| Which records match                      | Model local scope (`#[Scope]`)  | lists use the scope; one record uses `whereKey()->exists()` |
| One loaded record, no query needs it     | Model method                    | `$order->isCancellable()`                                |
| Who may act                              | Policy ability                  | controller `Gate::authorize()`, Resource `can.*`         |

Never write the same condition both as a scope and as a PHP comparison. A single record asks the scope:

```php
public function isOverdue(): bool
{
    return self::query()->overdue()->whereKey($this->getKey())->exists();
}
```

## Enums

- Every fixed set of values is a backed enum in `app/Enums/` with TitleCase cases.
- Facts about a value are enum methods (`label()`, `isOpen()`, `requiresAddress()`). Queries use a static helper derived from those methods (`TaskStatus::openValues()`), so cases are never listed twice.
- To get one enum from another, call a method on the enum (`$role->locationType()`). Never rely on two enums sharing the same values. `from()` and `tryFrom()` are allowed only where input arrives (PHPStan enforces this).

## Models

- Use `#[Fillable([...])]` and `casts()`, as in `app/Models/User.php`. Cast enums, dates and money.
- Scopes use the `#[Scope]` attribute on a protected method with a typed `Builder<self>` parameter.
- Relationships have return types with generics (`@return BelongsTo<User, $this>`).
- Models never call Actions, Policies or HTTP classes (Arch test).

## Policies

- One Policy per model. Each ability calls existing model methods; it never restates a condition (`$task->user_id === $user->id` is wrong when `isOwnedBy()` exists).
- List, create and bulk operations have abilities too (`viewAny`, `create`, `completeOverdue`).
- A check that already has one owner for the whole app (for example signing out deactivated accounts in middleware) is not repeated in Policies or Actions.

## Database

- Migrations: foreign keys with `foreignId()->constrained()`, money as unsigned integers in cents, statuses as strings cast to enums.
- Every model has a factory with states for the cases tests need (`completed()`, `overdue()`). Tests never insert rows by hand.
