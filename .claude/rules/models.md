---
paths:
  - "app/Models/**"
  - "app/Enums/**"
  - "app/Policies/**"
  - "database/**"
---

# Models, Enums, Policies and the database

A rule names its check in its tag (docs/golden-principles.md); text without a tag explains how to follow them. Where query conditions and comparisons may be written: .claude/rules/app.md.

## One condition, one written form

Before writing a condition, decide what it is about and write it once:

| It is about                              | Write it as                     | Others ask it like this                                  |
| ---------------------------------------- | ------------------------------- | -------------------------------------------------------- |
| A fixed value (status, type, role)       | Enum method                     | `$task->status->isOpen()`; queries use `openValues()`     |
| Which records match                      | Model local scope (`#[Scope]`)  | lists use the scope; one record uses `whereKey()->exists()` |
| One loaded record, no query needs it     | Model method                    | `$order->isCancellable()`                                |
| Who may act                              | Policy ability                  | controller `Gate::authorize()`, Resource `can.*`         |

- `[review:backend:2]` Inside a model, a column that a scope filters on is not compared again in a method: the method asks the scope with `whereKey($this->getKey())->exists()`.

docs/reference/tasks/app/Models/Task.php shows it (`isOverdue()`, `canBeCompleted()`).

## Enums

- `[review:backend:2]` app/Enums holds enums only, no classes or traits (ARCHITECTURE.md, side folders, says where a trait goes). Facts about a value are enum methods (`label()`, `isOpen()`, `requiresAddress()`).

Every fixed set of values is a backed enum with TitleCase cases. Queries use a static helper derived from the enum's methods (`TaskStatus::openValues()`), so cases are never listed twice.

## Models

- `[review:backend:3]` Model scopes are protected methods with the `#[Scope]` attribute, not `scopeFoo()` methods.

Use `#[Fillable([...])]` and `casts()`, as in `app/Models/User.php`, and cast enums, dates and money. A scope takes a typed `Builder<self>` parameter. Relationships have return types with generics (`@return BelongsTo<User, $this>`). A model may extend a framework model to add scopes, as `App\Models\Notification` extends `DatabaseNotification`: then ask its scopes, not the framework's instance methods that compare the same column again (`unread()`).

## Policies

- `[review:backend:2]` Policy abilities call model and enum methods (`$task->user_id === $user->id` is wrong when `isOwnedBy()` exists).

One Policy per model. List, create and bulk operations have abilities too (`viewAny`, `create`, `completeOverdue`). A check that already has one owner for the whole app (for example signing out deactivated accounts in middleware) is not repeated in Policies or Actions.

## Database

- `[review:backend:2]` Every model (abstract ones excepted) has `database/factories/{Model}Factory.php` with the states tests need (`completed()`, `overdue()`).
- `[review:backend:2]` Factories set values through enum cases and states; they never restate a condition.

Migrations: foreign keys with `foreignId()->constrained()`, money as unsigned integers in cents, statuses as strings cast to enums. A migration that has run outside your own machine (on a server, or in a commit others pulled) is never edited: a change to the schema is a new migration. A change to the existing rows is a migration of its own, after the one that changes the schema, and its `down()` undoes what `up()` did.
