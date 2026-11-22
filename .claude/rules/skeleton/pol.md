---
paths:
  - "app/Policies/**"
---

# POL: Policies

Location: `app/Policies/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **POL-1** The conditions of "who may do what" are written only here, except "which records this user can see", which is the model's visibleTo query scope. `reviewer`
  - Check: Look at: lines outside Policies (PHP and TypeScript) deciding who may do what. Break: a role, ownership or permission condition outside a Policy ($user->id === $task->user_id, $user->is_admin, abort_unless, a hand-built can flag, user.role === 'admin'). Not: Gate::authorize, $user->can(), OperationGate::allows(), received can flags; visibleTo; state rules in refusal(). Cite: the line.

## Must not

- **POL-2** Use Actions, the HTTP layer or the request. `machine` (PHPStan: disallowedNamespaces App\Actions, App\Http, the request, guard and session classes in app/Policies)

## Also applies here

- **MODEL-2** (from MODEL) "Which records this user can see" is the visibleTo query scope; a Policy that decides whether a user can see one record uses it too, with no condition of its own. `reviewer`
- **ACT-12** (from ACT) Use the HTTP layer, or touch the request, the response, the session, the signed-in user or cookies (the classes and functions PHPStan blocks). `machine` (PHPStan: disallowedNamespaces and disallowedFunctionCalls for the HTTP layer, the request, session, guard and auth helpers in app/Actions)
- **ACT-13** (from ACT) Fetch the request, the session or the signed-in user from the container by name; injecting their classes is blocked by PHPStan (ACT-12). `reviewer`

## Arrows

- ENTRY>POL: Entry points → Policies ([card](./entry-to-pol.md))
- POL>RES: Policies → Resources (can flags) ([card](./pol-to-res.md))
