---
paths:
  - "app/Models/**"
---

# MODEL: Models

Location: `app/Models/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **MODEL-1** "Which records match a condition" (query scopes) and "the state of this record" (model methods) are written only here. `reviewer`
  - Check: Look at: conditions on a model's columns outside app/Models. Break: where('status'), whereNull('read_at') or whereDate() outside a scope; `$task->due_at < now()` or `$n->read_at === null` outside a model method. Not: scopes and model methods; with(), paginate(); enum methods on a cast value. Cite: the line.
- **MODEL-2** "Which records this user can see" is the visibleTo query scope; a Policy that decides whether a user can see one record uses it too, with no condition of its own. `reviewer`
  - Check: Look at: new scopes, list queries and Policy methods about seeing a record. Break: "records this user can see" in a scope not named visibleTo, or in a controller or Action query; a Policy see-check with a condition of its own. Not: visibleTo calling another scope; abilities that are not about seeing. Cite: the scope or Policy line.

## Must

- **MODEL-3** Every model has a factory. `reviewer`
  - Check: Look at: new files in app/Models and database/factories. Break: a new model without database/factories/<Model>Factory.php or without `use HasFactory` and its @use line. Not: a changed model with an existing factory. Cite: the model path.

## Must not

- **MODEL-4** Use Policies, Actions, the HTTP layer or Gate. `machine` (PHPStan: disallowedNamespaces App\Policies, App\Actions, App\Http and Gate, and disallowedMethodCalls can(), cannot() and cant() in app/Models)

## Also applies here

- **ROUTE-4** (from ROUTE) New IDs in URLs and in page data are UUIDs; an integer ID already sent is existing debt. `reviewer`
- **ACT-12** (from ACT) Use the HTTP layer, or touch the request, the response, the session, the signed-in user or cookies (the classes and functions PHPStan blocks). `machine` (PHPStan: disallowedNamespaces and disallowedFunctionCalls for the HTTP layer, the request, session, guard and auth helpers in app/Actions)
- **ACT-13** (from ACT) Fetch the request, the session or the signed-in user from the container by name; injecting their classes is blocked by PHPStan (ACT-12). `reviewer`
- **ACT-14** (from ACT) Dispatch events or register listeners, observers or model event callbacks in app code (app/, bootstrap/, config/); events Laravel or a package fires on its own are not covered, and tests may listen to model events. `reviewer`

## Arrows

- CTRL>MODEL: Controllers → models (reads) ([card](./ctrl-to-model.md))
- ACT>MODEL: Operations → models (writes) ([card](./act-to-model.md))
- MIGR>MODEL: Migrations → models ([card](./migr-to-model.md))
- FACT>MODEL: Factories → tests and seeders ([card](./fact-to-model.md))
