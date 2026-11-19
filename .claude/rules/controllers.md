---
paths:
  - "app/Http/Controllers/**"
  - "app/Http/Fortify/**"
  - "routes/**"
---

# Controllers and routes

The Fortify adapters in `app/Http/Fortify/` follow these rules too: they check input shape and call one Action, like a controller; Fortify's routes decide who may call them. A rule names its check in its tag (docs/golden-principles.md); text without a tag explains how to follow them.

## A controller method does only three things

- `[review:backend:2]` A controller method authorizes, calls one Action (or runs a scoped query into a Resource) and returns. No business `if`.

1. Authorize with `Gate::authorize('ability', $model)` (the base controller has no `authorize()` helper), before calling the Action. This is the only permission check; the Action does not repeat it. List, create and bulk endpoints ask the Policy too (`viewAny`, `create` or a named bulk ability), even when every signed-in user is currently allowed. Do not invent a new role restriction while doing this.
2. Call one Action (writes) or run a scoped query and wrap it in a Resource (reads).
3. Return a redirect or `Inertia::render(...)`.

docs/reference/tasks/app/Http/Controllers/TaskController.php, `store()`, shows the three steps; a business rule or a write in the controller belongs in the Action.

Show flash messages with `Inertia::flash('toast', [...])`; the layout already displays them.

## Input comes from a FormRequest

- `[review:backend:1]` A controller reads input only from a FormRequest: `$request->validated()`, `safe()` or a method the FormRequest declares. `input()`, `all()`, `query()`, `has()`, `boolean()`, `enum()`, `validationData()`, `$request->field`, `$request['field']`, `request('field')` and the Request facade are rejected in controllers; `user()`, `session()`, `route()`, `routeIs()`, `header()`, `ip()`, `expectsJson()`, `url()` and other methods that read no input are allowed. An enum or a date comes from a FormRequest method (`$request->status()`). An endpoint with no input (mark all as read) needs no FormRequest; a list page with filters gets one.
- `[review:backend:2]` `$request->validate()` and `validateWithBag()` are rejected everywhere; the `Validator` facade and `validator()` are rejected except in the Fortify adapters in app/Http/Fortify. Input is validated by the `rules()` of `app/Http/Requests/{Domain}/{Verb}{Model}Request.php`, type-hinted in the controller.

To read an enum from the request, give the FormRequest a method and call it from the controller: `public function type(): ?ShipmentType { return $this->enum('type', ShipmentType::class); }`, then `$request->type()`. `$request->string()`, `integer()` and `boolean()` go inside the FormRequest's own methods too.

## Reads

docs/reference/tasks/app/Http/Controllers/TaskController.php, `index()`, shows it: a query through model scopes, paged, shaped through a Resource.

- `[review:backend:1]` Every list page is paginated with the page size its FormRequest reads from `per_page` through `ReadsPerPage` (10, 20, 50 or 100; anything else 20), never `take()`, and shows `<Pagination>` from components/shared with the rows-per-page choice. A short list inside another page, such as the user's passkeys on the security page, is not a list page.

The page links keep the query string (`withQueryString()`). Form choices are not a list page: a select gets the whole list with `get()`.

Load the relations and counts the Resource reads in the same query, so no row loads them again (N+1): relations with `with()` (`Task::query()->with('project')`), a number of related rows with `withCount()`. The owner calls behind a row's flags and `can.*` stay as they are, a few small queries per row (.claude/rules/frontend.md). A command that walks a large table reads it in pieces, with `chunkById()` or `lazyById()`.

Form choices (the options of a select) are reads too. Build them from the same scope the Action uses to accept the chosen value, so the list and the check cannot disagree. Do not write role, type or active conditions in the controller.

The controller lists the options through a scope (`Location::query()->selectableFor($user, $type)`), and the Action accepts the chosen value only through the same scope with `whereKey($id)->exists()`.

## Non-CRUD operations get their own controller

- `[review:backend:3]` Controllers live in app/Http/Controllers and have only the standard methods (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `__invoke`, and a constructor; methods from traits count); app/Http holds only Controllers, Requests, Resources, Middleware and Fortify. Any other operation gets its own controller (`TaskCompletionController@store`, `NotificationReadController@store`).

## Routes

- `[review:backend:2]` One route file per domain (`routes/{domain}.php`, like docs/reference/tasks/routes/tasks.php, required from `routes/web.php`), and routes bind models by their URL parameter (route model binding, `{task}`); records are never looked up by id by hand.
- `[review:backend:2]` Every app route is named (`tasks.index`, `tasks.completion.store`) and no route is a closure in routes/ (`Route::inertia` for a page with no data is fine; package routes such as Fortify's are excepted), and a fixed path comes before a wildcard path of the same method that would catch it (`quotes/create` before `quotes/{quote}`; the fallback route excepted).
