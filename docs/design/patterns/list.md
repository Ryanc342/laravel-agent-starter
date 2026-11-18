# List page

Use for a list of records: orders, products, customers, tasks. A stream of messages is a feed (feed.md), not a list.

## Layout, top to bottom

1. Header row: `<Heading>` on the left; on the right the page's actions. The main one is a `Button` that opens the create dialog (form.md), named in full ("Add task").
2. Search: one box above the table (its label `sr-only`) that searches the list's main text. On a phone, a "Sort & filter" `Button` (`outline`) sits next to it. Nothing else sits above the table: sort and filters live in the column headers.
3. The table: `ResponsiveTable`. Horizontal row lines only: no vertical lines and no zebra stripes. Columns in this order: the record's name or number (a link to its detail page), its status (`Badge`), its amount, then the rest. When the records have a detail page, a row has no button column: the actions are on the detail page. When they have none, the last column holds the row's own actions (below). A column that can be sorted or filtered has a header that opens a menu (below).
4. `Pagination` under the table: the page links, and next to them the rows-per-page choice (10, 20, 50 or 100; 20 by default). The choice lives only in the URL (`?per_page=50`), so leaving the list and coming back shows 20 again; any other value counts as 20. Changing it keeps the sort, the filters and the search.

Each row becomes one card on a phone: the name first, then the status, then the other columns as label and value. The page never scrolls sideways.

## Column menus

A column header that can be sorted or filtered is a button that opens a `Popover` under it (`ColumnMenu`, `components/shared`). Every menu ends with "Clear", which removes that column's sort and filter.

The menu is one column of rows, like a dropdown menu. The `Popover` uses the dropdown menu's padding and every choice is one row with the `DropdownMenuItem` look: the same height, padding, text size, gap and corner, an icon slot on the left, hover `bg-accent` and press `bg-accent-pressed`. All rows start on the same left edge and carry `data-slot="column-menu-row"`. No bordered or full-width button is left in the menu. A row that holds an input (the search box, From, To) uses `Input` with `size="sm"` and pads it so that the row is as high as every other row; its left padding is the same as every other row's.

1. The sort rows: "Sort ascending" (`ArrowUp`) and "Sort descending" (`ArrowDown`). The row in use has the selected colour (`bg-accent-pressed`, the selected-toggle rule in foundations.md), so the current order is visible inside the menu as well as to a screen reader (`aria-pressed`).
2. The column's filter: the search box, the tick list or the range, as the table below says.
3. "Clear" (`X`), the last row.

A thin `Separator`, edge to edge, divides the groups: sort, the column's filter, Clear. In a tick list the rows "Select all" (`ListChecks`) and "Unselect all" (`ListX`) are a group of their own, one row each, and a line divides them from the options.

| Column | The menu offers |
| --- | --- |
| Text (a title) | Sort ascending, sort descending, and a search box inside that column, in a row with the same left padding as the other rows |
| Fixed options (a status) | Sort, and a tick list where several options can be ticked: one row per option (its checkbox in the icon slot, the whole row clickable), after the rows "Select all" and "Unselect all" |
| Date or amount | Sort, and a from-to range (a `DatePicker` for a date, a number input for an amount; both ends included): "From" and "To" are one row each, the label on the left of its input |

- A header that can be clicked always shows a faint grey up-down arrow next to its name (`text-muted-foreground`), so the user sees it is clickable before the mouse arrives; a header that cannot be clicked shows nothing. A sorted column replaces the arrow with an up or down arrow in `text-primary`; a filtered column adds a `text-primary` funnel.
- Sort, filters and search live only in the URL (`?sort=due_on&direction=desc&status[]=open&due_from=2026-11-01&search=x`), like rows per page. Changing any of them goes back to page 1 and keeps `per_page`; leaving the list and coming back shows the default order. A value that is not allowed is ignored, as an unknown `per_page` is.
- An amount range is typed in major units (12.50), the way the user reads money, and the backend converts it to minor units (cents) before it filters; the URL carries what was typed. Every amount shown comes back through `formatMoney` (foundations.md).
- The allowed columns, directions and ranges are decided by the list's FormRequest. The tick list's options arrive from the backend as `{ value, label }` pairs (the same enum method that builds a form's Select).

## A row's own actions

A list whose records have no detail page (categories, the lines of a setting) puts each row's actions in one menu: `RowActions` (`components/shared`), passed to `ResponsiveTable` as `rowActions`. The last column holds one icon `Button` (`ghost`) with three dots, a `Tooltip` and the `aria-label` "More actions"; it opens a `DropdownMenu` with the row's actions. No other button stands in a row, and a single action is in the menu too.

- The actions keep their order; the destructive one (Delete, Remove) is the last item, after a line, in the destructive colour, and still opens its `ConfirmDialog`. Edit opens the edit dialog (form.md).
- On a phone the button sits at the top right of the row's card, beside the name.
- The lines of a form (`LineItems`) are not a list: they keep their own pattern.

## More on a list

Ticking rows and acting on many at once is bulk-actions.md; moving a whole list in or out as a file is import-export.md.

## Phone

The "Sort & filter" `Button` opens a `Sheet` from the bottom with the same choices, one section per column, and "Clear". Each section shows the column menu's rows and lines (without its own Clear: the sheet keeps its one Clear in the footer). The cards have no header row, so there are no header menus.

## States

- No records: the table still shows, its header row (on a phone its box) with the `EmptyState` inside, centred. The create button stays in the header row, so the empty state does not repeat it; pass it as `action` only on a page whose header has no create button.
- Search or filters match nothing: the same table with an `EmptyState` line saying so.
- Data arriving later: `LoadingState`. Failed to load: `ErrorState`, also inside the table (states.md).

## Rules

- Creating is a dialog, never a form above the table.
- Statuses, flags and amounts come from the backend; dates and amounts are shown through `lib/format.ts`.

## Sample and demo

Copy `docs/reference/tasks/resources/js/pages/tasks/index.tsx` and `docs/reference/tasks/resources/js/components/tasks/task-table.tsx` (the columns and their menus handed to `ResponsiveTable`). Demos: `resources/js/components/style/list-demo.tsx`, and `resources/js/components/style/row-actions-demo.tsx` for a list without a detail page, shown on the style overview page.
