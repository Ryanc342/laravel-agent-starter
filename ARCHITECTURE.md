# Architecture

Goal: every business rule lives in exactly one place, so a change is made once and nothing drifts.
Every rule, and the check that stops it, is listed once in docs/golden-principles.md. This file explains the layers and where each kind of rule goes.

## Layers

```
React pages and components        resources/js/                 display and submit only
Http                              app/Http/                     Controller, FormRequest, Resource
Actions          Policies         app/Actions/  app/Policies/   operations and writes / who may act
Models                            app/Models/                   scopes and facts about one record
Enums                             app/Enums/                    fixed values and facts about them
```

A layer may use the layers below it, never the ones above it. Side folders:

- `app/Integrations/`: the only entry for external services (payment, SMS, maps). Only Actions call it.
- `app/Support/`: small helpers any layer may use (money with `Brick\Math\BigDecimal`, dates).
- `app/Concerns/`: validation rule traits shared by FormRequests (`PasswordValidationRules`).
- `app/Notifications/`: messages to a user (`PasswordChanged`). Only Actions send them, with `$user->notify()`.

The machine checks live in these files, and `composer check` runs them all:

| Check                                 | What it stops                                                         |
| ------------------------------------- | --------------------------------------------------------------------- |
| `tests/Arch/LayersTest.php`           | a layer using a layer above it (principle 2)                          |
| `tests/Arch/ClassesTest.php`          | a class doing another class's job, wrong names, extra layers          |
| `phpstan.neon` (disallowed calls)     | writes outside Actions, validation outside FormRequests, queries in FormRequests, whole models in Resources |
| `tests/Structure/ConventionsTest.php` | an Action without a test, a `can.*` Resource without a buttons test, a model without a factory |
| `.dependency-cruiser.cjs`             | a domain component importing another domain's components              |
| `tests/Structure/DocsTest.php`        | AGENTS.md over 100 lines, a path in the docs that does not exist, an ExecPlan missing a section |

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

## The eight principles

The rules are grouped under eight principles. docs/golden-principles.md lists every rule under them and says which check stops it.

1. Every business rule is written once.
2. Layers only depend downwards.
3. Every class does only its own job.
4. The frontend never does the backend's job.
5. Input is checked once, at the edge.
6. Keep it simple: no Service, Repository or DTO layers.
7. Files go where the conventions say.
8. Every feature is tested.

## Request flow

```
React <Form> (Wayfinder) -> route -> Controller
    FormRequest validates shape
    Gate::authorize() asks the Policy (the only permission check)
    Action::handle() checks business state and writes
    redirect, or Inertia::render() with Resources (fields, is_* flags, can.*)
-> React displays what it received
```

## Starter kit code

The starter kit's auth and settings code follows the same rules, with no exceptions in any check:

| Starter kit piece                                    | Where it lives                    | Rule it shows                          |
| ---------------------------------------------------- | --------------------------------- | -------------------------------------- |
| Register, update profile, set password, delete account | `app/Actions/Users/`            | one write is one Action                |
| "A changed email is unverified again"                | `UpdateProfile`                   | a business rule lives in its Action    |
| Settings page and password reset both set a password | `UpdatePassword`, called by both  | two entry points call the same Action  |
| Fortify's register and reset hooks                   | `app/Http/Fortify/`               | check input shape, call one Action     |
| Profile and security pages                           | `app/Http/Controllers/Settings/`  | thin controllers                       |
| Only the owner may change or delete an account       | `app/Policies/UserPolicy.php`     | every endpoint asks the Policy         |
| `auth.user` on every page, the passkey list          | `UserResource`, `PasskeyResource` | React gets data only through Resources |
| Theme cookie: light, dark or system                  | `app/Enums/Appearance.php`        | fixed values and their rule in an Enum |

## Notifications: a live example

In-app notifications are part of the app and follow the same rules. They show what the starter kit
alone cannot, because it has only one record per user:

| Pattern                                    | Where                                                                      |
| ------------------------------------------ | -------------------------------------------------------------------------- |
| Scopes; one record asks a scope            | `Notification::ownedBy()`, `unread()`; `isUnread()` uses `whereKey()->exists()` |
| Records that belong to other users         | `NotificationPolicy::markRead()` asks `isOwnedBy()`                        |
| Non-CRUD controller                        | `NotificationReadController` marks one notification as read               |
| Bulk Action that reuses the single Action  | `MarkAllNotificationsRead` calls `MarkNotificationRead`                    |
| State rule refused in the Action           | "already read" in `MarkNotificationRead`                                   |
| `can.*` from the Policy and the model      | `NotificationResource`, `can.mark_read`                                    |
| Button consistency test                    | `tests/Feature/Notifications/NotificationButtonsTest.php`                  |
| Domain components                          | `resources/js/components/notifications/`                                   |
| Notifications sent from Actions            | `CreateUser`, `UpdateProfile`, `UpdatePassword`                            |

Create and edit forms, an Enum with several methods and a business limit refused with
`ValidationException` are in `docs/reference/tasks/`. New features copy the structure of both.
