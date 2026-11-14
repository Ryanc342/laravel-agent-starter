# Import and export

Use for moving many records in or out of a list as a file. Files are CSV only. It adds to a list page (list.md).

## Export

- An "Export" `Button` (`outline`) in the list's header `Actions`, before the create button (the main button is last). It downloads the list as the user sees it: the same search, sort and filters, every page, not only the one showing.
- The file is UTF-8 with a byte order mark, so Excel shows Chinese and other non-English text correctly, and its first line holds the column names.
- It is a plain download link to the backend (`<a href>` built with a Wayfinder route and the list's query string), not an Inertia visit.

## Import

- An "Import" `Button` (`outline`) opens a `ResponsiveDialog` titled "Import tasks": a file field for a CSV file, a "Download template" `TextLink` that gives the header row to fill in, and the `Actions` Cancel and "Import" (`default`, last), disabled with a `Spinner` while sending.
- Upload then import in one step: the backend checks every row. If any row has an error, nothing is imported and the request returns every row error, each naming the row and the problem ("Row 3: Due date is not a date"), and the validation toasts show the first five and then one "And 2 more errors" when there are more, as for any form (foundations.md, Messages). The dialog stays open.
- On success the dialog closes, the list shows the new records and one toast says how many ("Imported 41 tasks").

## Rules

- The row rules are the same rules the create form uses (one owner, a shared rules class); the import never has a second copy.
- File type and size are checked by the FormRequest. A wrong header, an empty file and a file that is not CSV are errors with a message that says how to fix them.
- All or nothing: one transaction.

## Phone

The dialog is a full-width sheet from the bottom (form.md). Export and Import stay in the header row, which wraps.

## Sample and demo

The task list: `docs/reference/tasks/resources/js/pages/tasks/index.tsx` and `components/tasks/task-import-dialog.tsx`. Demo: `resources/js/components/style/import-export-demo.tsx`.
