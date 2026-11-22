---
paths:
  - "app/Actions/**"
---

# ACT: Operations (Actions)

Location: `app/Actions/<Domain>/<VerbNoun>.php (parts in app/Actions/: Operation, UserlessOperation, OperationSteps, After; locking, file storing, reading the rows of a CSV file, prepare and afterCommit are steps of the operation part)`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **ACT-1** Each Action has one ability() and one set of writes; it does not choose different writes by an input value (approve and reject are two Actions), and no two Actions do the same writes under the same ability. One record and many records may be two Actions; the many-records one calls the one-record one through run(). `reviewer`
  - Check: Look at: new Actions, ability(), perform() and callers. Break: two Actions doing the same operation; one Action choosing different writes by an input value; an entry point calling two Actions for one operation. Not: one-record and all-records as two Actions; an Action running another through run(). Cite: the files or the branching line.
- **ACT-2** All business checks of the operation are in refusal(), and those that depend on the input in inputRefusal(); when one fails, the operation is refused. `reviewer`
  - Check: Look at: refusal(), inputRefusal() and perform(); checks for the same operation in controllers, FormRequests and commands. Break: perform() checks a business condition and throws, aborts or returns early; a business check outside the Action; refusal() lets through a case the plan refuses. Not: assert(instanceof); shape rules in rules(); who-may in the Policy. Cite: the line.
- **ACT-3** Every database save, notification, mail, stored or deleted file and outside-service call starts in an Action's perform(), directly or through a model method only perform() calls; session, cache and cookies are not writes here. `reviewer`
  - Check: Look at: write calls outside app/Actions (save, create, update, delete, increment, attach, sync, detach, DB inserts, notify, Notification::send, Mail::, Storage::put, delete and move, ->store(), outside calls). Break: such a call in a controller, middleware, Fortify adapter, command, job, model, Resource, Policy, listener or provider. Not: inside perform(); reads; flash, toasts and response cookies; logs; tests, factories, seeders and migrations. Cite: the write line.

## May use

- **ACT-4** Models, enums, notifications, other Actions, outside services, file storage. `note`

## Hands on

- **ACT-5** When refused: the reason; the operation part turns what refusal() returns into a ValidationException. `part`
- **ACT-6** When it succeeds: perform() returns only what a caller in this change or after() uses (the record created, the number of records handled); otherwise null. `reviewer`
  - Check: Look at: perform()'s return, after() and the callers of run(). Break: it returns something no caller uses; a true or false success flag; nothing when a caller needs the record or the count. Not: null when nothing is needed; a count after() shows. Cite: the return line and the caller.

## Must

- **ACT-7** Every class in app/Actions except the part itself (Operation) extends the operation part and states its Policy ability, input rules, refusal reasons and writes. `machine` (Structure: CodeTest, PlacementChecks::actionsAreOperations())
- **ACT-8** Inside the transaction and before refusal() and inputRefusal() run, the operation part locks the record in the URL and every record named in records(); the checks see the values stored now. `part`
- **ACT-9** A row whose stored value refusal() or inputRefusal() checks, or that perform() writes back from a value it read (stock, balance, counter, status), is the record in the URL or a record named in records(), so the operation part locks it before the checks. `reviewer`
  - Check: Look at: stored values refusal() and inputRefusal() check, values perform() reads and writes back, and records(). Break: such a row is neither the record in the URL nor named in records(), so it is not locked; a lock taken by hand. Not: overwriting with input only; a one-statement increment or decrement with no check. Cite: the read line and the write line.
- **ACT-10** Writes that must succeed together are in one transaction; the operation part wraps all writes of an operation in one transaction. `part`
- **ACT-11** When it loops over the rows of a table that grows with use, it reads them with chunkById() or lazyById(); ->get() or ->all() before a loop only on a query capped with limit(). `reviewer`
  - Check: Look at: loops over query results in Actions, commands and jobs. Break: ->get() or ->all() on a table that grows, then foreach, each or map with work per record. Not: chunkById, chunk, lazyById, lazy, the builder's each(); one bulk query; a query capped with limit(). Cite: the query and the loop.

## Must not

- **ACT-12** Use the HTTP layer, or touch the request, the response, the session, the signed-in user or cookies (the classes and functions PHPStan blocks). `machine` (PHPStan: disallowedNamespaces and disallowedFunctionCalls for the HTTP layer, the request, session, guard and auth helpers in app/Actions)
- **ACT-13** Fetch the request, the session or the signed-in user from the container by name; injecting their classes is blocked by PHPStan (ACT-12). `reviewer`
  - Check: Look at: container calls in Actions, Policies, Notifications, Models and Enums. Break: app('auth'|'request'|'session'|'auth.driver'), resolve() of those names, app()->make() or Container::getInstance()->make() fetching the request, the session, a guard or the user. Not: the User run() passes; app(OtherAction::class); config(), now(), app()->isProduction(); an injected class (PHPStan blocks it). Cite: the line.
- **ACT-14** Dispatch events or register listeners, observers or model event callbacks in app code (app/, bootstrap/, config/); events Laravel or a package fires on its own are not covered, and tests may listen to model events. `reviewer`
  - Check: Look at: Actions, models, providers and config: event(), Event::dispatch, ::dispatch() on events, Event::listen, observe(), #[ObservedBy], booted() with static::creating or saved, $dispatchesEvents. Break: any of them in app code. Not: events Laravel or a package fires on its own; tests. Cite: the line.

## Proof

- **ACT-15** Each distinct message refusal() can return has a test that triggers it and asserts nothing changed; success has a test; a change to refusal() changes its tests. `reviewer`
  - Check: Look at: each branch of a new or changed refusal() and inputRefusal(), and the tests. Break: success untested, or a distinct refusal message with no test that triggers it and asserts nothing changed. Not: tested through the route (422 on the field) or through run(). Cite: the refusal line.
- **ACT-16** The operation part's own test proves the lock holds (tests/Feature/Parts/OperationLockTest.php); an operation writes no lock test of its own. `part`

## Also applies here

- **FREQ-1** (from FREQ) Shape checks of input: present, type, length, format, allowed values, and against the database (exists, unique). For an operation they are written in the Action's rules() (for each row of an imported CSV file, in csvRows()); for other HTTP entry points, in a FormRequest; a Fortify adapter follows its own rule. `reviewer`
- **FREQ-2** (from FREQ) Every file input is named in the operation's files() (an imported CSV file in csvRows()), and its rules include an explicit type list (mimes:, mimetypes: or File::types([...])) and a max:. `reviewer`
- **FREQ-3** (from FREQ) Make business checks: a shape check looks only at the input and at whether a referenced row exists or is unique; any condition on a row's state (status, owner, dates) is a business check and belongs in refusal(). `reviewer`
- **FREQ-5** (from FREQ) The values a rule accepts for a choice come from the same enum as the options the page shows, and a test sends one offered and one unknown value; an unknown URL or cookie value counts as not chosen. `reviewer`

## Arrows

- ENTRY>ACT: Entry points → operations ([card](./entry-to-act.md))
- ACT>MODEL: Operations → models (writes) ([card](./act-to-model.md))
- ACT>ACT: Operation → operation ([card](./act-to-act.md))
- ACT>NOTE: Operations → notifications ([card](./act-to-note.md))
- ACT>FILES: Operations → file storage ([card](./act-to-files.md))
- ACT>EXT: Operations → outside services ([card](./act-to-ext.md))
