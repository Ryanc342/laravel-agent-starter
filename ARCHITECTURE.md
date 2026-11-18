# Architecture

Goal: every business rule lives in exactly one place, so a change is made once and nothing drifts.
Every rule, and the check or reviewer lane that stops it, is listed once in docs/golden-principles.md. This file explains the layers and where each kind of rule goes.

## Layers

```
React pages and components        resources/js/                 display and submit only
Http                              app/Http/                     Controller, FormRequest, Resource
Actions          Policies         app/Actions/  app/Policies/   operations and writes / who may act
Models                            app/Models/                   scopes and facts about one record
Enums                             app/Enums/                    fixed values and facts about them
```

A layer may use the layers below it, never the ones above it. Inside React, a page sits in a layout: `AppLayout` (the sidebar) for signed-in work screens, `AuthLayout` for the sign-in pages, and `PublicLayout` (`resources/js/layouts/public-layout.tsx`) for the home page and every page a visitor or a customer sees: a top bar (`PublicHeader`, with the links of the pages that exist, "Log in" and "Get started", or "Dashboard" when signed in), the page, and a footer. A layout reads only the shared props (`auth`, `name`); it decides nothing about the business. Side folders:

- `app/Integrations/`: the only entry for external services (payment, SMS, maps). Only Actions call it.
- `app/Support/`: small helpers any layer may use (money with `Brick\Math\BigDecimal`, dates, reading and writing a CSV file).
- `app/Concerns/`: validation rule traits shared by FormRequests (`PasswordValidationRules`).
- `app/Notifications/`: messages to a user (`PasswordChanged`). Only Actions send them, with `$user->notify()`.

Machines check a skeleton of 18 rules: the layers, the folders of app/, where pages and components go, that code lives where the checks read it, that no check is silenced or turned down, that the design spec keeps its index and names no values, and that `t()` never runs at import time. A linter reads the code without running it, a structure test reads the files, and each stops a broken rule with a message that says what to do instead. Every other rule, structure and business alike, is read by the two reviewers, one lane at a time (docs/golden-principles.md, Review lanes; docs/prompts/review.md). `composer check` runs every machine check:

| Check                                        | What it stops                                                         |
| -------------------------------------------- | --------------------------------------------------------------------- |
| `phpstan.neon` (disallowed namespaces and functions) | a layer using a layer above it (principle 2): enums, models, policies, notifications, Resources and FormRequests using what sits above them, HTTP classes or helpers in Actions, the Gate in models, the `DB` facade in controllers, integrations outside Actions, the HTTP client outside app/Integrations, seeders, factories or test code in the app |
| `vite.config.ts` (`lint`) and `lint/oxlint/ezdev.js` | a component importing another domain's components or a page, components/shared importing a domain, circular imports, `t()` or `tChoice()` at module level |
| `tests/Structure/FoldersTest.php`            | a folder or file outside the fixed layout: app/ and resources/js top level, pages and components folders, non-TypeScript files in resources/js, non-Markdown files in docs/, symlinks, PHP in public/ |
| `tests/Structure/CodeTest.php`               | a silenced check, components/ui importing app code, imports leaving resources/js, PHP required from other files, PHP in views |
| `tests/Structure/ConfigTest.php`             | a check turned down: PHPStan level, paths, extensions or a layer rule and its folders, an oxlint rule off or code ignored, TypeScript not strict, a Pest suite left out, `composer check` changed, another lint config, a `script-shell`, a check without its fixture |
| `tests/Structure/DesignDocsTest.php`         | a pattern file missing from the DESIGN.md index or without its Sample or Demo, a file a pattern names that does not exist, a pixel value or Tailwind size class in DESIGN.md or docs/design/ |
| `tests/Unit/Lint/SkeletonChecksTest.php`     | a kept check that no longer fails: it breaks every machine rule once in a fixture project and runs the real check on it |
| `tests/Structure/RunFinishedTest.php`        | the last test: its file proves the test run reached its end           |
| `scripts/hooks/finish-check.php` (when the AI stops) | code without a plan written in this conversation, a fixed tech-debt line without a QUALITY.md update, a protected file without recorded approval, code without fresh validation (milestone checks only for an incomplete active plan; full `composer check` for final acceptance), a completed plan without its records |
| `scripts/hooks/reviewer-commands.php` (before a reviewer's Bash call) | a reviewer subagent running a command that is not a git command that reads, piped only into filters that read; the refusal is logged in the state folder |

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

## The ten principles

The rules are grouped under ten principles. docs/golden-principles.md lists every rule under them and says which check or reviewer lane stops it.

1. Every business rule is written once.
2. Layers only depend downwards.
3. Every class does only its own job.
4. The frontend never does the backend's job.
5. Input is checked once, at the edge.
6. Keep it simple: no Service, Repository or DTO layers.
7. Files go where the conventions say.
8. Every feature is tested.
9. Work follows the steps: plan, check, review, report (docs/workflow.md). The finish check in scripts/hooks/ sends back a missing step.
10. Screens follow DESIGN.md and the files in docs/design/. The reviewers check them on screenshots at desktop and phone width, and against the style overview page.

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
| `can.*` from the Policy and the model      | `NotificationResource` `can.mark_read`; the shared `notificationSummary.can.mark_all_read` |
| Shared props; one loaded on demand         | `HandleInertiaRequests`: `notificationSummary` on every page, `latestNotifications` (`Inertia::optional`) when the bell opens or the dashboard block loads |
| Button consistency test                    | `tests/Feature/Notifications/NotificationButtonsTest.php`                  |
| Domain components                          | `resources/js/components/notifications/` (the bell in the top bar, the rows, the mark-all button) |
| Paginated list                             | `NotificationController::index()`, `resources/js/components/shared/pagination.tsx` |
| Notifications sent from Actions            | `CreateUser`, `UpdateProfile`, `UpdatePassword`                            |

Create and edit forms, an Enum with several methods and a business limit refused with
`ValidationException` are in `docs/reference/tasks/`. New features copy the structure of both.
