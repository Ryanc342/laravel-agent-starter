# Feed

Use for a stream of messages the user reads and clears: notifications, activity, comments. A feed is not a table: one message per row, no columns.

## Layout, top to bottom

1. Header row: `<Heading>` on the left; on the right the page's action ("Mark all as read"), shown from a backend flag; it keeps the user on the page too.
2. The rows, newest first, in one `ul` that is one bordered box (`rounded-lg border`), its rows separated by lines. A row is not a card of its own and has no background colour. Each row has an unread dot on the left, the message, its time (smaller, `text-muted-foreground`), and nothing else: a row has no button. An unread row has the blue dot and darker text, and the whole row is a button: clicking it marks it as read (and keeps the user on the page). A read row has no dot, quieter text and is not clickable.
3. `Pagination` under the rows, with the rows-per-page choice next to the page links (list.md).

## Rules

- The message text and the flags that say whether a row can be clicked come from the backend; React shows them.
- The same row component serves the page (the `list` variant) and the bell's `Popover` (the `menu` variant, the newest few rows). `FeedRow` (`components/shared`) draws the row and `FeedList` the bordered box around the rows (it also shows the empty line); a domain wraps `FeedRow` with its own click action, as `NotificationRow` does.

## Phone

Rows stay one column. A long message wraps.

## States

No messages: the same box with a centred `EmptyState` inside ("You have no notifications."). Failed to load: the same box with `ErrorState` (states.md).

## Sample and demo

Copy `resources/js/pages/notifications/index.tsx`, `resources/js/components/notifications/notification-row.tsx` and the `FeedRow` it uses (`resources/js/components/shared/feed-row.tsx`). Demo: `resources/js/components/style/feed-demo.tsx`.
