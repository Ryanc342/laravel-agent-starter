---
paths:
  - "app/Http/Requests/**"
  - "app/Concerns/**"
---

# Form Requests

A rule names its check in its tag, with its lane and level, and says why in one sentence (docs/golden-principles.md); text without a tag explains how to follow them. How a controller reads a FormRequest: .claude/rules/controllers.md.

## Only check the shape of the input

A FormRequest answers one question: _is this input well-formed?_ Types, required or optional, lengths, formats, `exists:` and `unique:` lookups, enum values. `exists` only means "this record exists", not "this record is eligible".

- `[review:backend:2]` A FormRequest checks the shape of the input only: no database queries (model, query builder, relation or `DB` calls) in FormRequests or app/Concerns, apart from the `exists` and `unique` rules, and no business condition chained onto `Rule::exists` (active or expired state, ownership, remaining stock). Why: business state checked in the FormRequest is skipped by every entry point that does not use it; the Action checks it and throws `ValidationException::withMessages()`. Not a break: a value that must be unique among the user's own records, checked by a `unique` rule limited to the records of the signed-in user.
- `[review:backend:1]` A record the user must own that is picked in a form (an id in the input) is looked up through its owner scope in the Action, with one message for "missing" and "not yours"; the FormRequest has no `exists` rule for it. Why: two messages, or an `exists` rule, tell anyone which ids belong to other users. Not a break: a record in the URL (route model binding, `/invoices/{invoice}`), which the Policy checks (403).
- `[review:backend:2]` A FormRequest has no `authorize()`. Why: permission is checked once, in the controller (.claude/rules/app.md).
- `[review:backend:1]` An uploaded file has the rules `file` (or `image`), its allowed types with `mimes:` and a `max:` size in kilobytes (docs/reference/tasks/app/Http/Requests/Tasks/ImportTaskRequest.php), and a file that is kept is stored under a name the app makes (`store()`), never the name the user sent. Why: an unchecked upload lets any file of any size in, and the user's file name can overwrite another file.

| Check                                   | Where                                         |
| --------------------------------------- | --------------------------------------------- |
| `quantity` is an integer of at least 1  | FormRequest                                   |
| `status` is a valid `TaskStatus` value  | FormRequest (`Rule::enum(TaskStatus::class)`) |
| `due_on` is a valid date                | FormRequest                                   |
| There is enough stock for this quantity | Action                                        |
| This task is not already completed      | Action                                        |
| The user has fewer than 100 open tasks  | Action                                        |

A FormRequest that checks business state (the stock of a product, the status of a record) breaks this table: the check goes in the Action. docs/reference/tasks/app/Http/Requests/Tasks/StoreTaskRequest.php checks shape only.

## Methods the controller calls

A list page with filters gets a FormRequest for its query string, and an enum or a date comes from a method of it:

docs/reference/tasks/app/Http/Requests/Tasks/IndexTaskRequest.php shows it (`sort()`, `direction()`).

## Sharing rules and naming

If two FormRequests need the same field rules, put them in a trait in `app/Concerns/` (see `docs/reference/tasks/app/Concerns/TaskValidationRules.php`) instead of copying them. A FormRequest is `{Index|Store|Update}{Model}Request` in `app/Http/Requests/{Domain}/`.
