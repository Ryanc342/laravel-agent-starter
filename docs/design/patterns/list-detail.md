# List beside detail

Use when the user goes through many records one after another and should not lose their place: an inbox, support tickets, conversations. A normal list (list.md) that opens a detail page is still the default; use this only when switching between records is the main work.

## Layout, top to bottom

1. Header row: `<Heading>`; on the right the page's actions.
2. `ListDetailLayout` (`components/shared`): on a computer the list takes about a third of the width on the left and the selected record's detail the rest. Each list row is the record's name, its status `Badge` and one short line; the selected row uses the selected colour (`bg-accent-pressed`). The list has its own search box on top (list.md).
3. The detail on the right follows detail.md (header row, facts, related records) without its own breadcrumb.

## Rules

- The selected record's id is in the URL (`/tickets/42`, or `?selected=42`): reloading or sharing the link shows the same record. With nothing selected the first row is selected.
- Rows are links, so the keyboard and screen readers work. Hover and press follow foundations.md.

## Phone

The list comes first and fills the screen. A row opens the detail as its own screen with a back link at the top left ("Tickets"); the browser's back button does the same.

## States

No records: the list box shows `EmptyState` (states.md) and the detail area is empty. A record that does not exist is a 404 page (error-page.md).

## Demo

`resources/js/components/style/list-detail-demo.tsx`, shown on the style overview page.
