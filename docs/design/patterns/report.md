# Charts and reports

Use for a page whose job is to show numbers over time or across categories (sales, tasks done, sign-ups). A dashboard (dashboard.md) links to a report; it does not draw charts of its own.

## Layout, top to bottom

1. Header row: `<Heading>`; on the right `ReportRange` (`components/shared`).
2. A row of number tiles: each a label and one figure in `tabular-nums` inside a bordered box (the one place a row of stat boxes is allowed, because the page asks for it). A tile shows no chart of its own.
3. The charts below, one per `<Heading variant="small">`, each in a bordered box, at full width; two small charts may sit side by side on a computer.

## The range

`ReportRange` is a row of presets: "7 days", "30 days", "90 days" and "Custom". Custom shows two date pickers (From, To; form.md). The chosen range lives only in the URL (`?range=30d`, or `?from=2026-10-01&to=2026-10-31`), so it survives a reload. The default is 30 days.

## Charts

- Built with the chart block (`components/ui/chart.tsx`, recharts). A project does not use another chart library.
- One series uses `chart-1` (the primary blue). Several series take `chart-1` to `chart-5` in that fixed order and always show a legend; a single series needs none. Colours come from the tokens, never from a hex value; a status colour is never used as a series colour.
- Axes and grid lines are `muted-foreground` and `border`. The tooltip shows the exact figures. A chart has an `aria-label` and a table with the same numbers for screen readers.
- Numbers on the axes and in the tooltips go through `lib/format.ts`.

## Rules

- Every figure comes from the backend for the chosen range. React draws and never adds up, averages or picks the range's boundaries.

## States

A range with no data shows `EmptyState` inside the chart's box; a chart that failed to load shows `ErrorState`; one still loading shows `LoadingState` (states.md). The other charts still show.

## Phone

The tiles wrap two to a row. Charts take the full width and keep a readable height; the presets scroll sideways inside their own row.

## Demo

`resources/js/components/style/report-demo.tsx`, shown on the style overview page.
