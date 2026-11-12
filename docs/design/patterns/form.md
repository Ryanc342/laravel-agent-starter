# Form

Use for creating or editing something.

## Where a form lives

- A record (create or edit) opens in a `ResponsiveDialog`, however long the form is. A long form scrolls inside the dialog; the title and the buttons stay in view.
- Account settings, sign-in and other forms about the user's own session sit on their page (settings.md, auth.md). They are not records.
- Search and filters are not forms of this kind (list.md).

## Layout, top to bottom

1. The dialog title names the action in full ("Add task", "Edit task").
2. The fields in one column, each a `FormField`: the label on the left of the input on desktop and above it on a phone, `InputError` under the input. A label is hidden (`sr-only`) only when the layout already says what the field is; it is never left out.
3. The buttons last: the main one first ("Save changes", "Add task"), then Cancel (`outline`). While sending, the main button is disabled and shows a `Spinner`.

## After sending

- Success: the dialog closes, the page shows the new data and a toast says what happened ("Task added").
- Validation errors: the dialog stays open and each error shows next to its field.
- A network or server error: a toast.

## Rules

- Use Inertia's `<Form>` with Wayfinder routes, and show errors with `InputError` (docs/rules/frontend.md). Validation lives in the FormRequest; React adds only HTML attributes.
- One `…-fields.tsx` component holds the fields and is used by both the create and the edit dialog.
- Choices: `Select` for a list, `Checkbox` for yes or no, `ToggleGroup` for two to seven modes.

## Sample and demo

Copy `docs/reference/tasks/resources/js/components/tasks/task-fields.tsx`, used inside dialogs by `docs/reference/tasks/resources/js/pages/tasks/index.tsx` and `show.tsx`. Demo: `resources/js/components/style/form-demo.tsx`.
