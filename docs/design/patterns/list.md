# List page

Use for a list of records: orders, products, customers, tasks. A stream of messages is a feed (feed.md), not a list.

## Layout, top to bottom

1. Header row: `<Heading>` on the left; on the right the page's actions. The main one is a `Button` that opens the create dialog (form.md), named in full ("Add task").
2. Search: one box above the table (its label `sr-only`) that searches the list's main text. On a phone, a "Sort & filter" `Button` (`outline`) sits next to it. Nothing else sits above the table: sort and filters live in the column headers.
3. The table: `ResponsiveTable`. Horizontal row lines only: no vertical lines and no zebra stripes. Columns in this order: the record's name or number (a link to its detail page), its status (`Badge`), its amount, then the rest. A row has no button column; the actions are on the detail page. A column that can be sorted or filtered has a header that opens a menu (below).
4. `Pagination` under the table: the page links, and next to them the rows-per-page choice (10, 20, 50 or 100; 20 by default). The choice lives only in the URL (`?per_page=50`), so leaving the list and coming back shows 20 again; any other value counts as 20. Changing it keeps the sort, the filters and the search.

Each row becomes one card on a phone: the name first, then the status, then the other columns as label and value. The page never scrolls sideways.

## Column menus

A column header that can be sorted or filtered is a button that opens a `Popover` under it (`ColumnMenu`, `components/shared`). Every menu ends with "Clear", which removes that column's sort and filter.

| Column | The menu offers |
| --- | --- |
| Text (a title) | Sort ascending, sort descending, and a search box inside that column |
| Fixed options (a status) | Sort, and a tick list where several options can be ticked, with "Select all" and "Unselect all" |
| Date or amount | Sort, and a from-to range (native date or number inputs; both ends included) |

- A header that can be clicked always shows a faint grey up-down arrow next to its name (`text-muted-foreground`), so the user sees it is clickable before the mouse arrives; a header that cannot be clicked shows nothing. A sorted column replaces the arrow with an up or down arrow in `text-primary`; a filtered column adds a `text-primary` funnel.
- Sort, filters and search live only in the URL (`?sort=due_on&direction=desc&status[]=open&due_from=2026-11-01&search=x`), like rows per page. Changing any of them goes back to page 1 and keeps `per_page`; leaving the list and coming back shows the default order. A value that is not allowed is ignored, as an unknown `per_page` is.
- The allowed columns, directions and ranges are decided by the list's FormRequest. The tick list's options arrive from the backend as `{ value, label }` pairs (the same enum method that builds a form's Select).

## Phone

The "Sort & filter" `Button` opens a `Sheet` from the bottom with the same choices, one section per column, and "Clear". The cards have no header row, so there are no header menus.

## States

- No records: the table still shows, its header row (on a phone its box) with the `EmptyState` inside, centred. The create button stays in the header row, so the empty state does not repeat it; pass it as `action` only on a page whose header has no create button.
- Search or filters match nothing: the same table with an `EmptyState` line saying so.
- Data arriving later: `LoadingState`. Failed to load: `ErrorState`, also inside the table (states.md).

## Rules

- Creating is a dialog, never a form above the table.
- Statuses, flags and amounts come from the backend; dates and amounts are shown through `lib/format.ts`.

## Sample and demo

Copy `docs/reference/tasks/resources/js/pages/tasks/index.tsx` and `docs/reference/tasks/resources/js/components/tasks/task-table.tsx` (the columns and their menus handed to `ResponsiveTable`). Demo: `resources/js/components/style/list-demo.tsx`, shown on the style overview page.
