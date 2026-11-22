---
paths:
  - "app/Notifications/**"
---

# NOTE: Notifications

Location: `app/Notifications/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **NOTE-1** Messages sent to users. `note`

## Must not

- **NOTE-2** Use Actions, the HTTP layer or the request. `machine` (PHPStan: disallowedNamespaces App\Actions, App\Http and the request classes in app/Notifications)

## Also applies here

- **ACT-12** (from ACT) Use the HTTP layer, or touch the request, the response, the session, the signed-in user or cookies (the classes and functions PHPStan blocks). `machine` (PHPStan: disallowedNamespaces and disallowedFunctionCalls for the HTTP layer, the request, session, guard and auth helpers in app/Actions)
- **ACT-13** (from ACT) Fetch the request, the session or the signed-in user from the container by name; injecting their classes is blocked by PHPStan (ACT-12). `reviewer`

## Arrows

- ACT>NOTE: Operations → notifications ([card](./act-to-note.md))
