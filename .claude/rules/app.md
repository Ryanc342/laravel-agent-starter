---
paths:
  - "app/**"
  - "database/**"
  - "routes/**"
---

# The backend as a whole

The rules for every folder of app/, database/ and routes/. The rules of one kind of class are in its own file (actions.md, controllers.md, form-requests.md, models.md, frontend.md for Resources). A rule names its check in its tag, with its lane and level, and says why in one sentence (docs/golden-principles.md); text without a tag explains how to follow them.

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

The rules below come down to one: a condition that means the same thing is written once, in its owner, and every other place asks that owner.

- `[review:backend:2]` Query conditions (`where…`, `orWhere…`) are written only in model scopes (database/ and tests/ excepted); everywhere else calls the scope by name, and one record asks the same scope with `whereKey($id)->exists()`. Why: a condition written outside its scope is missed when the scope changes. Not a break: `whereKey()` and `whereKeyNot()` to pick one record.
- `[review:backend:2]` Outside app/Models and app/Enums nothing compares a model field, an enum case or an enum's value (`===`, `<`, `match`, `switch`, `in_array`, `isPast()`), `$this->field` in a Resource included: Policies, Resources, Actions and controllers call the model or enum method. Why: a comparison written outside its owner is missed when the meaning changes.
- `[review:backend:2]` No copied block of code in app/, database/ or routes/: three or more lines of logic that do the same job in two places become one Action, scope, model method or shared component (tests: .claude/rules/tests.md). Why: a change to one copy misses the other. Not a break: display markup that only looks alike.
- `[review:backend:2]` Two entry points that do the same thing (a button and a command, a single and a bulk route) call the same Action. Why: two paths to one operation drift apart.
- `[review:backend:2]` Whatever changes the database, sends mail or sends a notification is in an Action (database/ and tests/ excepted). Why: a write outside an Action skips the Action's checks.

## Input and permission

- `[review:backend:1]` Permission is checked once, in the controller, through the Policy: Actions never call the Gate, `can()`, `canAny()`, `cannot()` or `cant()`, and enums, models, policies, notifications and Actions never use the Gate facade or contract or throw `AuthorizationException`. Why: a permission checked in two places lets one entry point in when the other changes.

A business state an Action needs is a model method and a `ValidationException` (.claude/rules/actions.md).

- `[review:backend:2]` `Enum::from()` and `tryFrom()` are called only where input arrives (FormRequests, Actions, console commands, the enum itself); a controller gets the enum from a FormRequest method that calls `$this->enum()`, and one enum becomes another through an enum method. Why: input turned into an enum in several places is checked in several ways.

## Keep it simple

- `[Structure]` app/ holds only Actions, Concerns, Console, Enums, Http, Integrations, Models, Notifications, Policies, Providers and Support.
- `[review:backend:2]` No classes whose name ends in Service, Repository or DTO (in any case) and no interfaces, unless the user asks for them: write an Action, query through model scopes, and pass validated arrays, models and enums. Why: a second kind of home for business code splits each rule between two places.
- `[review:backend:2]` No events or listeners: `event()`, the `Event` facade and dispatcher, model event callbacks (`creating()`, `saved()`, `observe()` and the like), `#[ObservedBy]`, `$dispatchesEvents` and classes in app/Events, app/Listeners or app/Observers. Why: a write that happens in a listener cannot be seen from the Action that causes it. Not a break: an Action that calls another Action.
- `[review:backend:2]` No code for a state nothing can reach yet: no refusal or branch for a status the app cannot create, and no test stand-in for it. Why: code for an unreachable state is untested and wrong by the time the state arrives. Not a break: the feature that adds the state adds its check and a real test.
- `[review:backend:2]` Dates, money, parsing and formatting use what PHP, Laravel and the installed packages provide, never hand-written code. Why: a hand-written version is a second, weaker copy of one that exists.
- `[review:backend:3]` Class names end in `Controller`, `Request`, `Resource` and `Policy` in their folders. Why: every other class there is named that way.
