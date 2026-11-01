# Controllers and routes

Applies to `app/Http/Controllers/**`, `routes/**` and the Fortify adapters in `app/Http/Fortify/**` (they check input shape and call one Action, like a controller; Fortify's routes decide who may call them).

## A controller method does only three things

1. Authorize with `Gate::authorize('ability', $model)` (the base controller has no `authorize()` helper).
2. Call one Action (writes) or run a scoped query and wrap it in a Resource (reads).
3. Return a redirect or `Inertia::render(...)`.

Authorize before calling the Action. This is the only permission check; the Action does not repeat it. List, create and bulk endpoints ask the Policy too (`viewAny`, `create` or a named bulk ability), even when every signed-in user is currently allowed. Do not invent a new role restriction while doing this.

No business `if` statements, no calculations, no direct model writes (PHPStan rejects model writes outside Actions).

```php
// Correct
public function store(StoreTaskRequest $request, CreateTask $createTask): RedirectResponse
{
    Gate::authorize('create', Task::class);

    $createTask->handle($request->user(), $request->validated());

    Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created.')]);

    return to_route('tasks.index');
}

// Wrong: business rule and model write inside the controller
public function store(Request $request): RedirectResponse
{
    $data = $request->validate(['title' => 'required']);
    if ($request->user()->tasks()->count() >= 100) { ... }   // business rule: belongs in the Action
    $request->user()->tasks()->create($data);                 // write: belongs in the Action
    return back();
}
```

## Always use a FormRequest for input

A controller reads input only from a FormRequest: `$request->validated()`, `$request->safe()` or a method the FormRequest declares. PHPStan rejects `$request->validate()`, `Validator::make()`, `validator()`, `$request->input()`, `all()`, `query()`, `boolean()`, `enum()`, `$request->field` and the Request facade in a controller (lint/phpstan/ControllerInputRule.php), even for one field. An endpoint with no input (mark all as read) needs no FormRequest; a list page with filters gets one for its query string.

## Reads

```php
// Query through model scopes, page it, shape through a Resource
$tasks = Task::query()->ownedBy($request->user())->listOrder()->paginate(20);

return Inertia::render('tasks/index', [
    'tasks' => TaskResource::collection($tasks)->response($request)->getData(true),
]);
```

A list page is paginated (`paginate(20)` unless the plan agreed another size, never `take()`), and the page renders `<Pagination collection={tasks} />` from `components/shared`. A short list inside another page (the user's passkeys on the security page) is not a list page. Form choices below are not a list page: a select gets the whole list with `get()`.

Conditions are written only in model scopes: PHPStan rejects `where…` and `orWhere…` outside app/Models (`whereKey()` is allowed). If a list needs a new condition, add a scope first.

Form choices (the options of a select) are reads too. Build them from the same scope the Action uses to accept the chosen value, so the list and the check cannot disagree. Do not write role, type or active conditions in the controller.

```php
// Controller: the options
'locations' => LocationResource::collection(
    Location::query()->selectableFor($request->user(), $type)->orderBy('name')->get(),
)->response($request)->getData(true),

// Action: the check, through the same scope
if (! Location::query()->selectableFor($user, $type)->whereKey($data['location_id'])->exists()) {
    throw ValidationException::withMessages(['location_id' => __('Choose a location from the list.')]);
}
```

To read an enum from the request, give the FormRequest a method and call it from the controller: `public function type(): ?ShipmentType { return $this->enum('type', ShipmentType::class); }`, then `$request->type()`. `ShipmentType::from(...)` and `$request->enum()` in a controller are rejected. Boost's laravel-best-practices skill suggests `$request->string()`, `integer()` and `boolean()`: in this template those calls go inside the FormRequest's own methods.

## Non-CRUD operations get their own controller

An operation like "complete a task" or "approve an invoice" gets a small controller (`TaskCompletionController@store`, `NotificationReadController@store`) instead of a custom method on the resource controller. PHPStan allows only the standard methods: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy` and `__invoke`.

## Routes

- One route file per domain (`routes/{domain}.php`, like docs/reference/tasks/routes/tasks.php), required from `routes/web.php`.
- Every route is named (`tasks.index`, `tasks.completion.store`). React reaches routes only through Wayfinder imports, never hard-coded URLs.
- Use route model binding (`{task}`); never look records up by id manually.
- Register fixed paths (`tasks/overdue/completion`) before wildcard paths (`tasks/{task}`).
