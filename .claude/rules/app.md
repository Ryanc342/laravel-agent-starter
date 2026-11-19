---
paths:
  - "app/**"
  - "database/**"
  - "routes/**"
---

# The backend as a whole

The rules for every folder of app/, database/ and routes/. The rules of one kind of class are in its own file (actions.md, controllers.md, form-requests.md, models.md, frontend.md for Resources). A rule names its check in its tag (docs/golden-principles.md); text without a tag explains how to follow them.

## Layers only depend downwards

phpstan.neon holds these layer rules; its error message says what to do instead.

- `[PHPStan]` Enums use no models, policies, actions, http or the request.
- `[PHPStan]` Models use no policies, actions, http, the request or the Gate.
- `[PHPStan]` Policies use no actions, http or the request.
- `[PHPStan]` Actions use no HTTP classes or helpers (request, response, session, Inertia, `request()`, `auth()`, `abort()`, `redirect()`).
- `[PHPStan]` Resources and FormRequests call no Actions.
- `[PHPStan]` Notifications use no actions, http or the request.
- `[PHPStan]` Controllers use no `DB` facade.
- `[PHPStan]` Integrations are used only by Actions; the HTTP client (`Http::`) only inside app/Integrations.
- `[PHPStan]` The app never uses seeders, test code, or a factory outside its model's `HasFactory` annotation.

## Every business rule is written once

- `[review:backend]` Query conditions (`where…`, `orWhere…`) are written only in model scopes (database/ and tests/ excepted). Everywhere else calls the scope by name; `whereKey()` and `whereKeyNot()` are allowed for one record, and one record asks the same scope with `whereKey($id)->exists()`. If a list needs a new condition, add a scope first.
- `[review:backend]` Outside app/Models and app/Enums nothing compares a model field, an enum case or an enum's value (`===`, `<`, `match`, `switch`, `in_array`, `isPast()`), including `$this->field` in a Resource. Policies, Resources, Actions and controllers call the model or enum method instead.
- `[review:backend]` No copied blocks of code: a copied block becomes one Action, scope, model method or shared component.
- `[review:backend]` Two entry points that do the same thing call the same Action.
- `[review:backend]` Two conditions that mean the same thing are never written in two different ways.
- `[review:backend]` Only Actions write: `save*`, `update*`, `delete*`, `create*`, `firstOrCreate`, `updateOrCreate`, `insert*`, `upsert`, `increment`, `touch`, `truncate`, `destroy`, `sync*`, `attach`, `detach`, `toggle`, `updateExistingPivot`, `associate`, `dissociate`, `DB::statement()`, `Mail::send()`, `Mail::queue()`, `Notification::send()`, `notify()`, `notifyNow()`, `markAsRead()`, `markAsUnread()` and the rest are rejected everywhere else (database/ and tests/ excepted). Move the write into `app/Actions/{Domain}/{Verb}{Noun}.php` and call it.

## Input and permission

- `[review:backend]` Permission is checked once, in the controller, through the Policy. Actions never call the Gate, `can()`, `canAny()`, `cannot()` or `cant()`; enums, models, policies, notifications and Actions never use the Gate facade or contract or throw `AuthorizationException`. A business state an Action needs is a model method and a `ValidationException`.
- `[review:backend]` `Enum::from()` and `tryFrom()` only where input arrives (FormRequests, Actions, console commands, the enum itself). A controller gets the enum from a FormRequest method that calls `$this->enum()`; one enum becomes another through an enum method, never through shared values.

## Keep it simple

- `[Structure]` app/ holds only Actions, Concerns, Console, Enums, Http, Integrations, Models, Notifications, Policies, Providers and Support.
- `[review:backend]` No classes whose name ends in Service, Repository or DTO (in any case) and no interfaces, unless the user asks for them: write an Action, query through model scopes, and pass validated arrays, models and enums.
- `[review:backend]` No events or listeners: `event()`, the `Event` facade and the event dispatcher, model event callbacks (`creating()`, `saved()`, `observe()` and the like), `#[ObservedBy]`, `$dispatchesEvents` and classes in app/Events, app/Listeners or app/Observers are rejected. An Action may call another Action.
- `[review:backend]` No code for a state nothing can reach yet: no refusal or branch for a status the app cannot create, and no test stand-in for it. The feature that adds the state adds its check and a real test.
- `[review:backend]` Use what PHP, Laravel and installed packages already provide: dates, money, parsing and formatting are never hand-written.
- `[review:backend]` Class names end in `Controller`, `Request`, `Resource` and `Policy` in their folders.
