---
paths:
  - "app/Http/Middleware/**"
---

# MIDW: Middleware

Location: `app/Http/Middleware/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **MIDW-1** Every request passes through here first: shared data is sent, light or dark is set, and /style is limited to local use. `note`

## Must

- **MIDW-2** A cookie or a request header is first turned into a known value through its enum (the choice-reading part, fromValue()), and only then used. `reviewer`
  - Check: Look at: cookie(), hasCookie(), header(), getLanguages() and server() in middleware. Break: the raw value used before it becomes a known value. Not: an enum method first (Appearance::fromCookie()) or a strict comparison with a fixed value. Cite: the line.

## Also applies here

- **HTTP>WEB-2** (from HTTP>WEB) Every record goes through ResourceData::from() or the listing part; a change that adds a field adds its reader, and a change that removes the last reader removes the field. `reviewer`
- **HTTP>WEB-4** (from HTTP>WEB) A change to a page's props, the shared data or the paginated wrapper changes the hand-written TypeScript type in the same change, field by field (name, type, null, optional). `reviewer`
- **RES-2** (from RES) An operation's name, only to ask OperationGate whether it can run now; the answer becomes a can flag. `machine` (PHPStan: disallowedMethodCalls Operation::run() and UserlessOperation::run() (allowIn OperationController, app/Actions, app/Http/Fortify, app/Console))
- **RES-3** (from RES) Run an operation: operations run only in the operation part and in Actions. (A guard test protects this check.) `machine` (PHPStan: disallowedMethodCalls App\Actions\Operation::run(); proved by template/tests/Lint/SkeletonChecksTest.php)

## Arrows

- HTTP>WEB: Backend → pages (data) ([card](./http-to-web.md))
