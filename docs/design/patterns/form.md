# Form

Use for creating or editing something.

## Where a form lives

- A record (create or edit) opens in a `ResponsiveDialog`, however long the form is. A long form scrolls inside the dialog; the title and the buttons stay in view.
- Account settings, sign-in and other forms about the user's own session sit on their page (settings.md, auth.md). They are not records.
- Search and filters are not forms of this kind (list.md).
- One exception: a long task made of steps (setting up a team, onboarding) is a multi-step wizard on its own page (wizard.md).

## Layout, top to bottom

1. The dialog title names the action in full ("Add task", "Edit task"), with no description under it.
2. The fields in one column, each a `FormField`: the label on the left of the input on desktop and above it on a phone; a field with an error has a red border and no text under it. A label is hidden (`sr-only`) only when the layout already says what the field is; it is never left out.
3. The buttons last, at the right end of the row: Cancel (`outline`) first, then the main one ("Save changes", "Add task") as the rightmost button. While sending, the main button is disabled and shows a `Spinner`. On a phone the buttons are full width and stacked, the main one on top.

## After sending

- Success: the dialog closes, the page shows the new data and a toast says what happened ("Task created."); the controller flashes it.
- Validation errors: the dialog stays open and each error is a toast. The dialog scrolls smoothly to the first field with an error and puts the cursor in it (it jumps there under reduced motion).
- A network or server error: a toast.

## Rules

- Use Inertia's `<Form>` with Wayfinder routes, and let the app's validation-error toasts and `FormField` show errors (docs/rules/frontend.md). Validation lives in the FormRequest; React adds only HTML attributes.
- One `…-form-dialog.tsx` component is both the create and the edit dialog: it owns `open` and closes it in the form's `onSuccess`. One `…-fields.tsx` component holds the fields it shows.
- Choices: `Select` for a list, `Checkbox` for yes or no, `ToggleGroup` for two to seven modes.
- A date is a `DatePicker` (`components/shared`), never `type="date"`: a button showing the date that opens a `Popover` with a month calendar (shadcn `Calendar`), the same in every browser. It has a label like any field, the red border for an error, works with the keyboard, and reads and writes `YYYY-MM-DD`, so URLs and requests are unchanged. It also serves the from and to rows of a column menu (list.md). A number is a number `Input`.

## Sample and demo

Copy `docs/reference/tasks/resources/js/components/tasks/task-form-dialog.tsx` and `task-fields.tsx` next to it. The list page (`pages/tasks/index.tsx`) opens the dialog from its header button, and the task's actions (`components/tasks/task-actions.tsx`) open it filled with the task. Demo: `resources/js/components/style/form-demo.tsx`.
