# List page

Use for a list of records: orders, products, customers, tasks. A stream of messages is a feed (feed.md), not a list.

## Layout, top to bottom

1. Header row: `<Heading>` on the left; on the right the page's actions. The main one is a `Button` that opens the create dialog (form.md), named in full ("Add task").
2. Filters: one row above the table, the search box first (its label `sr-only`), then selects. The row wraps onto more lines on a phone.
3. The table: `ResponsiveTable`. Horizontal row lines only: no vertical lines and no zebra stripes. Columns in this order: the record's name or number (a link to its detail page), its status (`Badge`), its amount, then the rest. A row has no button column; the actions are on the detail page.
4. `Pagination` under the table: the page links, and next to them the rows-per-page choice (10, 20, 50 or 100; 20 by default). The choice lives only in the URL (`?per_page=50`), so leaving the list and coming back shows 20 again; any other value counts as 20. Changing it keeps the filters.

## Phone

Each row becomes one card: the name first, then the status, then the other columns as label and value. The page never scrolls sideways.

## States

- No records: the table still shows, its header row (on a phone its box) with the `EmptyState` line inside. The create button stays in the header row, so the empty state does not repeat it; pass it as `action` only on a page whose header has no create button.
- Filters match nothing: the same table with an `EmptyState` line saying so.
- Data arriving later: `LoadingState`. Failed to load: `ErrorState` (states.md).

## Rules

- Creating is a dialog, never a form above the table.
- Statuses, flags and amounts come from the backend; dates and amounts are shown through `lib/format.ts`.

## Sample and demo

Copy `docs/reference/tasks/resources/js/pages/tasks/index.tsx` and `docs/reference/tasks/resources/js/components/tasks/task-table.tsx` (the columns handed to `ResponsiveTable`). Demo: `resources/js/components/style/list-demo.tsx`, shown on the style overview page.
