---
paths:
  - "app/Enums/**"
---

# ENUM: Enums

Location: `app/Enums/ (part: Concerns/ReadsChoice.php)`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **ENUM-1** A fixed set of values and their facts (labels, order, which are final) are declared only in an enum; app and frontend code use its cases or the values the backend sends. Generated types and test inputs are not copies. `reviewer`
  - Check: Look at: literals of a fixed set and their labels or facts outside app/Enums, PHP and TypeScript. Break: in:open,done; ['draft', 'sent']; { paid: 'Paid' }; status === 'paid'; [10, 20, 50, 100]. Not: enum cases and methods; values and labels the backend sends; generated.d.ts. Cite: the literal line.

## Must

- **ENUM-2** When a URL value, a cookie, a header or a stored setting is read as a choice, the enum uses the choice-reading part (fromValue()): an unknown value counts as not chosen, and the default is decided in one method of the enum. `reviewer`
  - Check: Look at: code reading a URL parameter, a cookie, a header or a stored setting as a choice. Break: an unknown value errors or passes through: Enum::from($value); tryFrom() with no default; a rule that rejects it (422); match with no default arm. Not: the choice-reading part's fromValue() with the default in one enum method; model-bound route parameters (404 is right); form fields (FREQ-5). Cite: the line.

## Must not

- **ENUM-3** Use models, Policies, Actions or the HTTP layer. `machine` (PHPStan: disallowedNamespaces App\Models, App\Policies, App\Actions, App\Http and Gate in app/Enums)

## Also applies here

- **HTTP>WEB-4** (from HTTP>WEB) A change to a page's props, the shared data or the paginated wrapper changes the hand-written TypeScript type in the same change, field by field (name, type, null, optional). `reviewer`
- **ACT-12** (from ACT) Use the HTTP layer, or touch the request, the response, the session, the signed-in user or cookies (the classes and functions PHPStan blocks). `machine` (PHPStan: disallowedNamespaces and disallowedFunctionCalls for the HTTP layer, the request, session, guard and auth helpers in app/Actions)
- **ACT-13** (from ACT) Fetch the request, the session or the signed-in user from the container by name; injecting their classes is blocked by PHPStan (ACT-12). `reviewer`
