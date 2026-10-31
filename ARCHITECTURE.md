# Architecture

Goal: every business rule lives in exactly one place, so a change is made once and nothing drifts.
The rules below are enforced by machines where possible; the last column of each table says how.

## Layers

```
React pages and components        resources/js/                 display and submit only
Http                              app/Http/                     Controller, FormRequest, Resource
Actions          Policies         app/Actions/  app/Policies/   operations and writes / who may act
Models                            app/Models/                   scopes and facts about one record
Enums                             app/Enums/                    fixed values and facts about them
```

A layer may use the layers below it, never the ones above it. Two side folders:

- `app/Integrations/`: the only entry for external services (payment, SMS, maps). Only Actions call it.
- `app/Support/`: small helpers any layer may use (money with `Brick\Math\BigDecimal`, dates).

| Rule                                                                | Enforced by                           |
| ------------------------------------------------------------------- | ------------------------------------- |
| Enums use no models, policies, actions, http or integrations        | `tests/Arch/LayersTest.php`           |
| Models use no policies, actions, http or integrations               | `tests/Arch/LayersTest.php`           |
| Policies use no actions, http or integrations                       | `tests/Arch/LayersTest.php`           |
| Actions are final, expose only `handle()`, use no HTTP and no Gate  | `tests/Arch/LayersTest.php`           |
| Resources and FormRequests call no Actions                          | `tests/Arch/LayersTest.php`           |
| Controllers use no integrations and no `DB` facade                  | `tests/Arch/LayersTest.php`           |
| `Http::` only in app/Integrations; integrations only from Actions   | `tests/Arch/LayersTest.php`           |
| No `*Service` or `*Repository` classes                              | `tests/Arch/LayersTest.php`           |
| Model writes (`save`, `update`, `delete`, `create`, ...) only in Actions | `phpstan.neon` (disallowed calls) |
| `Enum::from()` only where input arrives                             | `phpstan.neon` (disallowed calls)     |
| Domain components do not import other domains' components          | `.dependency-cruiser.cjs`             |
| Files stay under 250 lines                                          | `tests/Structure/FileSizeTest.php`    |

## Where each rule goes

Before writing a business rule, decide what it is about, then write it once, in one form:

| The rule is about                                    | Owner                                          | Example                                          |
| ---------------------------------------------------- | ---------------------------------------------- | ------------------------------------------------ |
| Input shape (required, type, length, `exists`)       | FormRequest `app/Http/Requests/{Domain}/`      | `quantity` is an integer of at least 1           |
| An operation and every database write                | Action `app/Actions/{Domain}/{Verb}{Noun}.php` | `PlaceOrder`, `CancelOrder`                      |
| A fixed value (status, type, role)                   | Enum method `app/Enums/`                       | `OrderStatus::isCancellable()`                   |
| Which records match a condition                      | Model scope                                    | `Order::query()->unpaid()`                       |
| One loaded record, when no query needs the condition | Model method                                   | `$order->isOwnedBy($user)`                       |
| Who may do what                                      | Policy `app/Policies/`                         | `OrderPolicy::cancel()`                          |
| Data sent to React                                   | JsonResource `app/Http/Resources/`             | `OrderResource` with `is_cancellable`, `can.*`   |
| HTTP glue                                            | Controller                                     | authorize, call one Action, return               |
| Display and user input                               | React page or component                        | show the button when `order.can.cancel`          |

- The first six rows may hold business rules. Resources, controllers and React only call owners.
- A single record asks a scope with `whereKey($id)->exists()`. Never write the same condition both as a scope and as a PHP comparison.
- Form choices, `can.*` flags, Policies and factories call the owner. They never restate the condition.
- To change existing behaviour, find the owner, change it there, update its tests, and check that nothing else re-implements it.

## Non-negotiable rules

1. One write is one Action. Controllers, commands, jobs and listeners never write models directly. Two entry points doing the same thing call the same Action.
2. React never decides business rules. The backend sends results (`is_overdue`, `can.complete`) through the Resource.
3. A FormRequest checks shape, not business state. "Is there enough stock?" belongs in the Action, which throws `ValidationException::withMessages([...])`.
4. Controllers stay thin: authorize once, call one Action (or run a scoped query and wrap it in a Resource), return.
5. Keep it simple: no Service classes, Repositories, DTO layers, interfaces or events unless the user asks. An Action may call another Action. Use what PHP, Laravel and installed packages provide. Store money as integer cents.
6. Every Action and every endpoint has a feature test. Buttons and form choices have a consistency test (docs/rules/tests.md).

## Request flow

```
React <Form> (Wayfinder) -> route -> Controller
    FormRequest validates shape
    Gate::authorize() asks the Policy (the only permission check)
    Action::handle() checks business state and writes
    redirect, or Inertia::render() with Resources (fields, is_* flags, can.*)
-> React displays what it received
```

## Scaffolding

The starter kit's own code keeps its shape and is excluded from some checks: `app/Actions/Fortify`,
`app/Http/Controllers/Settings`, `app/Console/Commands/InstallFeaturesCommand.php`, `chisel.php`.
Do not use it as an example. The example to copy is `docs/reference/tasks/`.
