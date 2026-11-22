---
paths:
  - "app/Http/Requests/**"
---

# FREQ: FormRequests

Location: `app/Http/Requests/<Domain>/ (part: ListingRequest, in the root)`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **FREQ-1** Shape checks of input: present, type, length, format, allowed values, and against the database (exists, unique). For an operation they are written in the Action's rules() (for each row of an imported CSV file, in csvRows()); for other HTTP entry points, in a FormRequest; a Fortify adapter follows its own rule. `reviewer`
  - Check: Look at: rules() of Actions and FormRequests, and other validation. Break: a shape check in a controller ($request->validate(), Validator::make()), in perform() or refusal(), or in a FormRequest for a Route::operation route. Not: Validator::make() in a Fortify adapter; a FormRequest for a controller route. Cite: the line.

## Must

- **FREQ-2** Every file input is named in the operation's files() (an imported CSV file in csvRows()), and its rules include an explicit type list (mimes:, mimetypes: or File::types([...])) and a max:. `reviewer`
  - Check: Look at: the rules of file inputs. Break: no list of allowed types (mimes, mimetypes, File::types) or no size limit (max). Not: both present. Cite: the rule line.

## Must not

- **FREQ-3** Make business checks: a shape check looks only at the input and at whether a referenced row exists or is unique; any condition on a row's state (status, owner, dates) is a business check and belongs in refusal(). `reviewer`
  - Check: Look at: rules(), after(), withValidator(), closures and custom rules. Break: a check on a record's state or a business condition. Not: plain exists and unique, in:, Rule::enum, formats; authorize() returning true. Cite: the rule line.
- **FREQ-4** Call an Action. `machine` (PHPStan: disallowedNamespaces App\Actions in app/Http/Requests)

## Proof

- **FREQ-5** The values a rule accepts for a choice come from the same enum as the options the page shows, and a test sends one offered and one unknown value; an unknown URL or cookie value counts as not chosen. `reviewer`
  - Check: Look at: in:, Rule::in, Rule::enum and the page's options for the same field, and the tests. Break: the page offers a value the rule refuses; the rule accepts a value not offered; an unknown URL or cookie value errors instead of counting as not chosen; no test sends one offered and one unknown value. Not: options and rule from the same enum; nullable with the enum's fromQuery(). Cite: the rule line and the option line.

## Arrows

- FREQ>CTRL: FormRequests → controllers ([card](./freq-to-ctrl.md))
- CONC>FREQ: Shared validation rules → FormRequests and Fortify adapters ([card](./conc-to-freq.md))
