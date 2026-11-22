---
paths:
  - "routes/**"
---

# ROUTE: Routes

Location: `routes/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **ROUTE-1** Maps URLs to entry points: a request that changes data goes through Route::operation() to the operation part, a list page through Route::listing() to the listing part, a page about one record through Route::record() to the record part, anything else to a controller or straight to a page. `note`

## Must

- **ROUTE-2** Every request that changes data goes through Route::operation() (a call another system sends here through Route::webhook()), every list page and its CSV export through Route::listing(), every page about one record named in the URL through Route::record(), every download of a stored file through Route::download(); Fortify's own routes are the exception. `reviewer`
  - Check: Look at: routes/*.php. Break: a request that changes data through post, put, patch, delete, match or any instead of Route::operation(); a GET rendering a paginated list, or sending a list as a CSV file, not through Route::listing(); a GET rendering a page about the record in its URL not through Route::record(); a stored file sent without Route::download(); a GET handler that writes. Not: Route::operation, listing, record, download, webhook and inertia; a page about the signed-in user (settings); redirects; the fallback; Fortify's routes. Cite: the route line.
- **ROUTE-3** A route parameter that is not a model is checked by the route's own constraint (->where()) or by a FormRequest. `reviewer`
  - Check: Look at: {param} in new URIs and the handlers' parameters. Break: a parameter no model binds, without where(), whereIn(), whereNumber() or whereUuid() and without a FormRequest rule (for Route::operation: any parameter after the first). Not: model-bound parameters; the operation's subject. Cite: the route line and the parameter.
- **ROUTE-4** New IDs in URLs and in page data are UUIDs; an integer ID already sent is existing debt. `reviewer`
  - Check: Look at: new key columns, HasUuids, id and *_id in Resources, props and TypeScript types, URLs built from IDs. Break: an integer key reaching a URL or page data. Not: page numbers, per_page, counts. Cite: the line.

## Also applies here

- **CTRL-4** (from CTRL) Every hand-written HTTP entry point has a success test, plus a test for each of these that can happen to it: a guest request, another user's record, invalid input. For a route a part opens, the test part (tests/Feature/PartRoutesTest.php) checks the guest and the missing record; its operation or its page still tests another user's record and invalid input. `reviewer`

## Arrows

- WEB>HTTP: Frontend → backend (requests) ([card](./web-to-http.md))
- ROUTE>CTRL: Routes → controllers ([card](./route-to-ctrl.md))
