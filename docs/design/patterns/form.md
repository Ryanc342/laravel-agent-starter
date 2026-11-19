# Form

Use for creating or editing something.

## Where a form lives

- A record (create or edit) opens in a `ResponsiveDialog`, however long the form is. A long form scrolls inside the dialog; the title and the buttons stay in view. A form with a table of lines (an invoice with its lines) uses the `wide` dialog; on a phone it is the same full-width sheet.
- A page whose purpose is the form is that page: sign-in (auth.md), settings (settings.md), contact (contact.md), checkout (checkout.md) and a wizard (wizard.md). Creating or editing a record that the user opened from a list is the dialog above.
- Search and filters are not forms of this kind (list.md).

## Layout, top to bottom

1. The dialog title names the action in full ("Add task", "Edit task"), with no description under it.
2. The fields in one column, each a `FormField`: it decides from the width of its form, the label to the left of the input when the form is wide and above it when the form is narrow; a field with an error has a red border and no text under it. A label is hidden (`sr-only`) only when the layout already says what the field is; it is never left out.
3. The buttons last, as `Actions` (components.md): Cancel (`outline`) first, then the main one ("Save changes", "Add task") last. While sending, the main button is disabled and shows a `Spinner`.

## After sending

- Success: the dialog closes, the page shows the new data and a toast says what happened ("Task created."); the controller flashes it.
- Validation errors: the dialog stays open and each error is a toast. The dialog scrolls smoothly to the first field with an error and puts the cursor in it (it jumps there under reduced motion).
- A network or server error: a toast.

## Rules

- Use Inertia's `<Form>` with `noValidate` and Wayfinder routes, so the browser shows no message of its own (an empty required field reaches the server, whose message comes back in the app's language), and let the app's validation-error toasts and `FormField` show errors (.claude/rules/frontend.md). Validation lives in the FormRequest; React adds only HTML attributes.
- One `…-form-dialog.tsx` component is both the create and the edit dialog: it owns `open` and closes it in the form's `onSuccess`. One `…-fields.tsx` component holds the fields it shows.
- Choices: `Select` for a list, `Checkbox` for yes or no, `ToggleGroup` for two to seven modes.
- A date is a `DatePicker` (`components/shared`), never `type="date"`: a button showing the date that opens a `Popover` with a month calendar (shadcn `Calendar`), the same in every browser. It has a label like any field, the red border for an error, works with the keyboard, and reads and writes `YYYY-MM-DD`, so URLs and requests are unchanged. It also serves the from and to rows of a column menu (list.md). A number is a number `Input`.

## Sample and demo

Copy `docs/reference/tasks/resources/js/components/tasks/task-form-dialog.tsx` and `task-fields.tsx` next to it. The list page (`pages/tasks/index.tsx`) opens the dialog from its header button, and the task's actions (`components/tasks/task-actions.tsx`) open it filled with the task. Demo: `resources/js/components/style/form-demo.tsx`.
