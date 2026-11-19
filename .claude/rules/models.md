---
paths:
  - "app/Models/**"
  - "app/Enums/**"
  - "app/Policies/**"
  - "database/**"
---

# Models, Enums, Policies and the database

A rule names its check in its tag, with its lane and level, and says why in one sentence (docs/golden-principles.md); text without a tag explains how to follow them. Where query conditions and comparisons may be written: .claude/rules/app.md.

## One condition, one written form

Before writing a condition, decide what it is about and write it once:

| It is about                              | Write it as                     | Others ask it like this                                  |
| ---------------------------------------- | ------------------------------- | -------------------------------------------------------- |
| A fixed value (status, type, role)       | Enum method                     | `$task->status->isOpen()`; queries use `openValues()`     |
| Which records match                      | Model local scope (`#[Scope]`)  | lists use the scope; one record uses `whereKey()->exists()` |
| One loaded record, no query needs it     | Model method                    | `$order->isCancellable()`                                |
| Who may act                              | Policy ability                  | controller `Gate::authorize()`, Resource `can.*`         |

- `[review:backend:2]` Inside a model, a condition that a scope filters on is not written again in a method: for a fixed value (a status, a type) the scope and the method both ask the same enum method, with no query (`open()` uses `TaskStatus::openValues()`, `isOpen()` asks `$this->status->isOpen()`); for any other condition (a date, a count) the method asks the scope with `whereKey($this->getKey())->exists()`. Why: a condition written in the scope and again in the method drifts apart.

docs/reference/tasks/app/Models/Task.php shows both (`isOpen()` and `canBeCompleted()`, `isOverdue()`).

## Enums

- `[review:backend:2]` app/Enums holds enums only, no classes or traits (ARCHITECTURE.md, side folders, says where a trait goes). Why: the next change looks for the facts about a value in its enum.

Facts about a value are enum methods (`label()`, `isOpen()`, `requiresAddress()`). Every fixed set of values is a backed enum with TitleCase cases. Queries use a static helper derived from the enum's methods (`TaskStatus::openValues()`), so cases are never listed twice.

## Models

- `[review:backend:3]` Model scopes are protected methods with the `#[Scope]` attribute, not `scopeFoo()` methods. Why: every other scope is written that way.

Use `#[Fillable([...])]` and `casts()`, as in `app/Models/User.php`, and cast enums, dates and money. A scope takes a typed `Builder<self>` parameter. Relationships have return types with generics (`@return BelongsTo<User, $this>`). A model may extend a framework model to add scopes, as `App\Models\Notification` extends `DatabaseNotification`: then ask its scopes, not the framework's instance methods that compare the same column again (`unread()`).

## Policies

- `[review:backend:2]` A Policy ability calls model and enum methods (`$task->isOwnedBy($user)`) instead of comparing fields. Why: a comparison in the Policy is missed when the model's method changes.

One Policy per model. List, create and bulk operations have abilities too (`viewAny`, `create`, `completeOverdue`). A check that already has one owner for the whole app (for example signing out deactivated accounts in middleware) is not repeated in Policies or Actions.

## Database

- `[review:backend:2]` Every model (abstract ones excepted) has `database/factories/{Model}Factory.php` with the states tests need (`completed()`, `overdue()`). Why: tests that build records by hand build them differently.
- `[review:backend:2]` A factory sets plain values through enum cases and states, and never restates a condition: it does not write the test that decides a state (`due_on < today` to pick a status, the list of statuses that count as open). Why: a condition in the factory drifts from its owner, and the tests then prove the copy. Not a break: a state that sets the plain values that put a record in that state, as docs/reference/tasks/database/factories/TaskFactory.php does (`overdue()` sets yesterday as the due date).
- `[review:backend:1]` A migration that has run outside your own machine (on a server, or in a commit others pulled) is never edited: a change to the schema is a new migration. Why: an edited migration never runs again where it already ran, so those databases differ from the code.

Migrations: foreign keys with `foreignId()->constrained()`, money as unsigned integers in cents, statuses as strings cast to enums. A change to the existing rows is a migration of its own, after the one that changes the schema, and its `down()` undoes what `up()` did.
