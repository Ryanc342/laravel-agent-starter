---
paths:
  - "app/Actions/**"
  - "app/Integrations/**"
---

# Actions

An Action is one business operation: it checks the business conditions and makes every database write for that operation. A rule names its check in its tag, with its lane and level, and says why in one sentence (docs/golden-principles.md); text without a tag explains how to follow them.

## Shape

docs/reference/tasks/app/Actions/Tasks/CompleteTask.php shows an Action.

- `[review:backend:2]` An Action lives in `app/Actions/{Domain}/{Verb}{Noun}.php`, one folder per business domain, never directly in app/Actions or in a deeper folder. Why: the next change looks for an operation in its domain's folder and nowhere else.
- `[review:backend:3]` An Action is named `{Verb}{Noun}` (`SendQuote`, `CompleteOverdueTasks`). Why: every other Action is named that way.
- `[review:backend:2]` An Action is final and has one public method, `handle()` (and a constructor); its other methods are private. Why: a second public method is a second operation that callers reach without the checks of the first.
- `[review:backend:2]` An Action calls another Action through its `handle()`, injected through the constructor; a step two Actions share goes in a third Action that both call, never in a trait. Why: a shared step written once is changed once.
- `[review:backend:3]` `handle()` takes no `bool` that switches between two behaviours: those are two Actions (`ArchiveTask` and `RestoreTask`). Why: one Action is one operation. Not a break: a yes or no the user entered (a validated field such as "notify the customer") is data and may be passed.

## Business conditions

- `[review:backend:1]` An Action checks every business precondition of its operation (stock, record status, limits, eligibility) itself, by calling its owner (an enum method, a model method or a scope). Why: a precondition that one entry point skips lets that entry point write what the rule forbids.

A refusal throws `ValidationException::withMessages([...])`, keyed by the field the user should fix.

## Writes

- `[review:backend:1]` Writes that must succeed together run in one `DB::transaction()`. Why: a failure halfway leaves half an operation in the database.
- `[review:backend:2]` A bulk operation, or one that repeats another (complete all overdue tasks), loops over the single-record Action instead of copying its checks and writes (docs/reference/tasks/app/Actions/Tasks/CompleteOverdueTasks.php). Why: copied checks drift apart, and then the bulk path lets through what the single one refuses.
- `[review:backend:1]` A row is locked only where concurrent writes to it can collide (stock, balances, a number counter); reading never locks, also not a row an Action only reads to check it. Why: a lock nobody needs makes every other writer of that row wait. Not a break: saving an order checks "is the supplier active?" without locking the supplier, because deactivating it a moment later gives the same result.
- `[review:backend:1]` `lockForUpdate()`, `sharedLock()` and `lock()` are called only inside the `DB::transaction()` of the operation that writes the row (tests/ excepted); pages and read-only operations never lock. Why: a lock outside the writing transaction is released before the write it should protect.
- `[review:backend:1]` When two or more Actions lock the row of the same model, the lock is written once, in a shared Action `Lock{Model}` (`LockOrder`) that each writing Action calls first inside its transaction; that Action may lock without writing. Why: two lock sites drift apart, and the one that changes stops protecting the other.
- `[review:backend:1]` Money is stored as integer cents, multiplied with `Brick\Math\BigDecimal` and rounded with its `RoundingMode`, never with `round()`, `floor()`, `ceil()` or `number_format()`. Why: floating-point money is off by a cent.

## External services

An external service is called through a class in `app/Integrations/`, injected into the Action (.claude/rules/app.md). Retrying it: .claude/rules/fixes.md. Its tests fake every request with `Http::fake()` and `Http::preventStrayRequests()`.

- `[review:backend:1]` A request to an external service sets a timeout (`Http::timeout()` and `connectTimeout()`) and turns a failed answer into an exception (`->throw()`). Why: otherwise a hanging service hangs the request, and a failed answer passes as a success.
