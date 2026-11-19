---
paths:
  - "app/Http/Controllers/**"
  - "app/Http/Fortify/**"
  - "routes/**"
---

# Controllers and routes

The Fortify adapters in `app/Http/Fortify/` follow these rules too: they check input shape and call one Action, like a controller; Fortify's routes decide who may call them. A rule names its check in its tag, with its lane and level, and says why in one sentence (docs/golden-principles.md); text without a tag explains how to follow them.

## A controller method does only three things

1. Authorize with `Gate::authorize('ability', $model)` (the base controller has no `authorize()` helper), before calling the Action. This is the only permission check; the Action does not repeat it. Do not invent a new role restriction while doing this.
2. Call one Action (writes), or run scoped queries and wrap them in Resources (reads).
3. Return a redirect or `Inertia::render(...)`.

docs/reference/tasks/app/Http/Controllers/TaskController.php, `store()` and `index()`, shows the three steps. Show flash messages with `Inertia::flash('toast', [...])`; the layout already displays them.

- `[review:backend:2]` A controller method authorizes, then a write calls one Action and a read runs scoped queries into Resources, then it returns; it holds no business `if`. Why: a business rule or a write in a controller is missed by every other entry point to the operation. Not a break: a read that runs several scoped queries (the list and its form choices).
- `[review:backend:1]` List, create and bulk endpoints ask the Policy too (`viewAny`, `create` or a named bulk ability), even when every signed-in user is allowed today. Why: an endpoint that asks nothing stays open when the Policy later restricts it.

## Input comes from a FormRequest

- `[review:backend:1]` A controller reads input only from a FormRequest: `$request->validated()`, `safe()` or a method the FormRequest declares (`$request->status()` for an enum or a date). Why: input read around the FormRequest reaches the Action unchecked. Not a break: `user()`, `session()`, `route()`, `routeIs()`, `header()`, `ip()`, `expectsJson()`, `url()` and other methods that read no input; an endpoint with no input (mark all as read) needs no FormRequest.
- `[review:backend:2]` Input is validated by the `rules()` of `app/Http/Requests/{Domain}/{Verb}{Model}Request.php`, type-hinted in the controller: never `$request->validate()` or `validateWithBag()`, and the `Validator` facade and `validator()` only in the Fortify adapters in app/Http/Fortify. Why: rules written in two places accept different input.

To read an enum from the request, give the FormRequest a method and call it from the controller: `$request->type()` returns `$this->enum('type', ShipmentType::class)`. `$request->string()`, `integer()` and `boolean()` go inside the FormRequest's own methods too.

## Reads

docs/reference/tasks/app/Http/Controllers/TaskController.php, `index()`, shows a read: a query through model scopes, paged, shaped through a Resource.

- `[review:backend:1]` Every list page is paginated with the page size its FormRequest reads from `per_page` through `ReadsPerPage` (10, 20, 50 or 100; anything else 20), never `take()`, and shows `<Pagination>` from components/shared with the rows-per-page choice. Why: an unpaged list grows until the page fails. Not a break: a short list inside another page (the user's passkeys on the security page), and form choices, which get the whole list with `get()`.
- `[review:backend:1]` A read loads the relations and counts its Resources read in the same query (`with()`, `withCount()`), and a command that walks a large table reads it in pieces (`chunkById()`, `lazyById()`). Why: a relation loaded per row (N+1), or a whole table in memory, makes the page or the command fail as the data grows. Not a break: the owner calls behind a row's flags and `can.*`, a few small queries per row (.claude/rules/frontend.md).

The page links keep the query string (`withQueryString()`).

Form choices (the options of a select) are reads too. Build them from the same scope the Action uses to accept the chosen value, so the list and the check cannot disagree: the controller lists the options through a scope (`Location::query()->selectableFor($user, $type)`), and the Action accepts the chosen value only through the same scope with `whereKey($id)->exists()`. Do not write role, type or active conditions in the controller.

## Non-CRUD operations get their own controller

- `[review:backend:3]` A controller has only the standard methods (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `__invoke`, and a constructor; methods from traits count); any other operation gets its own controller (`TaskCompletionController@store`, `NotificationReadController@store`). Why: every other controller is built that way.
- `[review:backend:3]` app/Http holds only Controllers, Requests, Resources, Middleware and Fortify. Why: every other HTTP class lives in one of them.

## Routes

- `[review:backend:2]` One route file per domain (`routes/{domain}.php`, like docs/reference/tasks/routes/tasks.php, required from `routes/web.php`). Why: the routes of a domain are changed in one place.
- `[review:backend:2]` Routes bind models by their URL parameter (route model binding, `{task}`); records are never looked up by id by hand. Why: a hand-written lookup is a second way to find a record.
- `[review:backend:2]` Every app route is named (`tasks.index`, `tasks.completion.store`) and no route is a closure in routes/. Why: Wayfinder and the links reach a route by its name only. Not a break: `Route::inertia` for a page with no data, and package routes such as Fortify's.
- `[review:backend:1]` A fixed path comes before a wildcard path of the same method that would catch it (`quotes/create` before `quotes/{quote}`). Why: otherwise the wildcard catches the fixed path and the page fails. Not a break: the fallback route.
