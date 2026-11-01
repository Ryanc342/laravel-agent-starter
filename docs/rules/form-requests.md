# Form Requests

Applies to `app/Http/Requests/**`.

## Only check the shape of the input

A FormRequest answers one question: _is this input well-formed?_
Types, required or optional, lengths, formats, `exists:` and `unique:` lookups, enum values.

It does **not** check business state. Those checks go in the Action. PHPStan rejects database queries (`Model::query()`, `->where()`, `DB::`) in FormRequests and in app/Concerns.

`exists` only means "this record exists", not "this record is eligible". Do not chain business conditions onto `Rule::exists` or `Rule::unique`: active or expired state, ownership and remaining stock belong in the Action (or the Policy for who may act). This also applies to shared validation traits.

For a record the user must own (a customer picked in a form), leave out `exists`: the Action looks it up through the owner scope and gives one message for "missing" and "not yours", so nobody can find out which ids belong to other users.

| Check                                   | Where                                         |
| --------------------------------------- | --------------------------------------------- |
| `quantity` is an integer of at least 1  | FormRequest                                   |
| `status` is a valid `TaskStatus` value  | FormRequest (`Rule::enum(TaskStatus::class)`) |
| `due_on` is a valid date                | FormRequest                                   |
| There is enough stock for this quantity | Action                                        |
| This task is not already completed      | Action                                        |
| The user has fewer than 100 open tasks  | Action                                        |

```php
// Wrong: business state inside a FormRequest
public function rules(): array
{
    return [
        'quantity' => ['required', 'integer', 'min:1', function ($attr, $value, $fail) {
            if (Product::find($this->product_id)->stock < $value) { $fail('Not enough stock.'); }
        }],
    ];
}
```

## Authorization

Do not write `authorize()` (PHPStan rejects it). Authorization happens in the controller through the Policy, so there is one place to look.

## Sharing rules

If two FormRequests need the same field rules, put them in a trait in `app/Concerns/` (see `docs/reference/tasks/app/Concerns/TaskValidationRules.php`) instead of copying them.

## Naming

`{Store|Update}{Model}Request` in `app/Http/Requests/{Domain}/`.
