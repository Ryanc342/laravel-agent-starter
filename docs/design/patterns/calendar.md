# Calendar

Use for records that happen on a date (appointments, deliveries, deadlines) when the user wants to see them by day. It is a month view only.

## Layout, top to bottom

1. Header row: `<Heading>` with the month ("November 2026"), and on the right "Previous month" and "Next month" as icon `Button`s (`outline`, each with a `Tooltip` and an `aria-label`) and "Today" (`outline`).
2. `MonthCalendar` (`components/shared`): seven columns, the weekday names above, six rows of days at most. A day outside the month is `muted-foreground`; today has a `primary` day number. Each day lists its events, each a one-line button; more than three show "+2 more", which opens that day's events in the dialog.
3. The month lives only in the URL (`?month=2026-11`).

## An event

An event opens in a `ResponsiveDialog` (form.md) with its facts as a `dl` and its actions; creating or editing one follows form.md. The title is the event's name.

## Rules

- The month's events come from the backend, already grouped by date; React places them and never decides which events belong to a day.
- Dates are formatted through `lib/format.ts`; the grid is built with `Intl` and `Date`, with no date library.
- The first day of the week and weekday names follow the language; every day cell is a button, so the keyboard works.

## Phone

The month grid becomes a list ordered by date: one row per day that has events, the date as a small heading and its events below. Days with no events are left out, and `EmptyState` shows when the month has none.

## Demo

`resources/js/components/style/calendar-demo.tsx`, shown on the style overview page.
