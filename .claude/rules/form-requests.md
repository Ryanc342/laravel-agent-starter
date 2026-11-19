---
paths:
  - "app/Http/Requests/**"
  - "app/Concerns/**"
---

# Form Requests

A rule names its check in its tag (docs/golden-principles.md); text without a tag explains how to follow them. How a controller reads a FormRequest: .claude/rules/controllers.md.

## Only check the shape of the input

- `[review:backend:2]` A FormRequest checks the shape of the input only: no database queries (model, query builder, relation or `DB` calls) in FormRequests or app/Concerns, apart from the `exists` and `unique` rules. Business state (stock, status, ownership, limits) is checked in the Action, which throws `ValidationException::withMessages()`.
- `[review:backend:1]` A record the user must own that is picked in a form (an id in the input) is looked up through its owner scope in the Action, with one message for "missing" and "not yours", so nobody can find out which ids belong to other users; the FormRequest has no `exists` rule for it. A record in the URL (route model binding, `/invoices/{invoice}`) is checked by the Policy (403).
- `[review:backend:2]` FormRequests have no `authorize()`. Permission is checked in the controller.

A FormRequest answers one question: _is this input well-formed?_ Types, required or optional, lengths, formats, `exists:` and `unique:` lookups, enum values. `exists` only means "this record exists", not "this record is eligible": do not chain business conditions onto `Rule::exists` or `Rule::unique` (active or expired state, ownership and remaining stock belong in the Action, or the Policy for who may act), also not in shared validation traits.

An uploaded file is input too: its rules are `file` (or `image`), the allowed types with `mimes:` and a `max:` size in kilobytes, as in `docs/reference/tasks/app/Http/Requests/Tasks/ImportTaskRequest.php`. A file that is kept is stored under a name the app makes (`store()`), never under the name the user sent.

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
