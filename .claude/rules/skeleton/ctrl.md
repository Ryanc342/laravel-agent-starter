---
paths:
  - "app/Http/Controllers/**"
  - "app/Http/OperationGate.php"
  - "app/Http/UrlRecords.php"
---

# CTRL: Controllers

Location: `app/Http/Controllers/ (parts: OperationController, ListingController, RecordController, DownloadController, WebhookController; app/Http/OperationGate.php and app/Http/UrlRecords.php, which they use, belong here too)`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **CTRL-1** A hand-written controller takes only the requests the parts do not handle (for example a download or showing a page): it checks the Policy, reads data through the model's query scopes, and responds; besides reading input, it does only these things. `reviewer`
  - Check: Look at: app/Http/Controllers. Break: a controller that handles a write, a list page or a page about the record in its URL; does more than read input, authorize, read through scopes and respond; responds with data without a Policy check. Not: authorize, a scoped read, then render, redirect or download; records through Resource::from(). Cite: the line, or the method without authorize.

## May use

- **CTRL-2** FormRequests, Policies, models, Resources; an operation only by its name, to ask OperationGate whether it can run now. `note`

## Must not

- **CTRL-3** Use the database directly (DB). `machine` (PHPStan: disallowedNamespaces DB, Schema, ConnectionInterface, Connection, DatabaseManager and disallowedMethodCalls getConnection() in app/Http/Controllers)

## Proof

- **CTRL-4** Every hand-written HTTP entry point has a success test, plus a test for each of these that can happen to it: a guest request, another user's record, invalid input. For a route a part opens, the test part (tests/Feature/PartRoutesTest.php) checks the guest and the missing record; its operation or its page still tests another user's record and invalid input. `reviewer`
  - Check: Look at: each new or changed entry point and tests/Feature. Break: one of these missing: a guest (redirected to login), success, another user's record (403 or 404, nothing changed), invalid input (a validation error, nothing changed). Not: tested through the route or through run(); one file for several entry points; a case that cannot happen to it. Cite: the entry point and the missing case.

## Also applies here

- **HTTP>WEB-2** (from HTTP>WEB) Every record goes through ResourceData::from() or the listing part; a change that adds a field adds its reader, and a change that removes the last reader removes the field. `reviewer`
- **ACT>NOTE-1** (from ACT>NOTE) Messages for users; only Actions send notifications and mail. `machine` (PHPStan: disallowedNamespaces for mail and notifications (allowIn app/Actions) and disallowedMethodCalls notify() and notifyNow())
- **ROUTE-2** (from ROUTE) Every request that changes data goes through Route::operation() (a call another system sends here through Route::webhook()), every list page and its CSV export through Route::listing(), every page about one record named in the URL through Route::record(), every download of a stored file through Route::download(); Fortify's own routes are the exception. `reviewer`

## Arrows

- HTTP>WEB: Backend → pages (data) ([card](./http-to-web.md))
- ROUTE>CTRL: Routes → controllers ([card](./route-to-ctrl.md))
- FREQ>CTRL: FormRequests → controllers ([card](./freq-to-ctrl.md))
- ENTRY>POL: Entry points → Policies ([card](./entry-to-pol.md))
- ENTRY>ACT: Entry points → operations ([card](./entry-to-act.md))
- CTRL>MODEL: Controllers → models (reads) ([card](./ctrl-to-model.md))
