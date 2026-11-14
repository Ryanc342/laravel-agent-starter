# Dashboard

The page the user lands on after login (`/dashboard`, set by `home` in config/fortify.php). It shows what needs the user's attention now, not a welcome page.

## Layout, top to bottom

1. Header row: `<Heading>` "Dashboard".
2. Blocks, each with a `<Heading variant="small">` and no description. On desktop they sit side by side, the wider one for the feed and the narrower one for the quick links; on a phone they stack in the same order.
    - Latest notifications: the newest rows (feed.md) and a link to the notifications page. They load after the page opens, like the bell's (`LoadingState` while waiting); the same bordered box with an `EmptyState` line inside when there are none.
    - Number tiles, only when the plan asks for them: `StatTiles` (`components/shared`, report.md), with figures from the backend and no placeholders.
    - Quick links: one row per page the user comes back to, each an icon and the page name as a link. The dashboard itself is not listed, and a project adds its main pages as it grows.

## Rules

- Show only real things. Every number, status and list on a dashboard comes from the backend as a prop (a Resource); React never calculates a figure. Something that does not exist yet is not shown: no placeholders, no made-up numbers, no `PlaceholderPattern`.
- A project adds its own blocks (what is overdue, what waits for me) in the same style: a small heading, then a list or a figure that links to the list it counts.
- Charts belong to the charts and reports pattern, not to this one.

## States

A block with nothing in it uses `EmptyState`; a block that failed to load uses `ErrorState` (states.md). The other blocks still show.

## Sample and demo

The page is `resources/js/pages/dashboard/index.tsx`; its blocks are `resources/js/components/notifications/latest-notifications.tsx` (loading, empty and error states included; its rows are `notification-row.tsx`) and `resources/js/components/shared/quick-links.tsx` (the page hands it the list of links). Demo: `resources/js/components/style/dashboard-demo.tsx`.
