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

Do not call `$request->validate()` inline, even for one field. Every write endpoint takes a FormRequest.

## Reads

```php
// Query through model scopes, shape through a Resource
$tasks = Task::query()->ownedBy($request->user())->listOrder()->get();

return Inertia::render('tasks/index', [
    'tasks' => TaskResource::collection($tasks)->response($request)->getData(true),
]);
```

If the same query is needed in two places, move it into a model scope first.

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

To read an enum from the request in a controller, use `$request->enum('type', ShipmentType::class)`, not `ShipmentType::from(...)` (PHPStan rejects it).

## Non-CRUD operations get their own controller

An operation like "complete a task" or "approve an invoice" gets a small controller (`TaskCompletionController@store`) instead of a custom method on the resource controller.

## Routes

- One route file per domain (`routes/tasks.php`), required from `routes/web.php`.
- Every route is named (`tasks.index`, `tasks.completion.store`). React reaches routes only through Wayfinder imports, never hard-coded URLs.
- Use route model binding (`{task}`); never look records up by id manually.
- Register fixed paths (`tasks/overdue/completion`) before wildcard paths (`tasks/{task}`).
