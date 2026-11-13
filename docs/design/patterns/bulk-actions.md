# Bulk actions

Use when the user does the same thing to many records of a list (complete, delete, assign). It adds to a list page (list.md); it is not a separate page.

## Layout

1. A checkbox column first in the table. The header checkbox selects the rows of the page that is showing; a half-ticked header means some rows are ticked. On a phone each card has its own checkbox, top left, and no header checkbox exists (see Phone).
2. Once one row is ticked a bar floats at the bottom of the page and stays visible while the user scrolls (`BulkActionBar`, `components/shared`, with `useRowSelection`): "2 selected · Complete · Delete · ✕". The bar is a floating layer, so it has the popover shadow. The main bulk action is `default`, the others `outline`; ✕ clears the selection and has a `Tooltip` and an `aria-label`.
3. When the header checkbox has selected a whole page and more results exist, a line in the bar offers "Select all 83 results". Choosing it selects every result of the list's own search and filters, not only the loaded rows, and the bar says "All 83 selected".
4. Delete, and anything else that cannot be undone, asks in a confirmation dialog first, naming the count ("Delete 12 tasks"), as in detail.md.

## Rules

- Changing the search, sort, filter or page clears the selection.
- The backend takes either the ticked ids or "all results" together with the list's own search and filters, checks the policy for each record, and does it all in one transaction. A record the user may not touch refuses the whole request. React never decides which records are eligible.
- Every bulk action is the same Action a single record uses, called once per record.
- A toast says what happened ("12 tasks completed"), and the list refreshes.

## Phone

The bar spans the width of the screen above the bottom edge; its buttons keep short labels and never wrap onto a second line. Cards have no header checkbox, so as soon as one card is ticked the bar offers "Select this page", and after that "Select all 83 results", as a computer does.

## Sample and demo

The task list: `docs/reference/tasks/resources/js/pages/tasks/index.tsx` and `components/tasks/task-table.tsx`. Demo: `resources/js/components/style/bulk-actions-demo.tsx`.
