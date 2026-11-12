# Feed

Use for a stream of messages the user reads and clears: notifications, activity, comments. A feed is not a table: one message per row, no columns.

## Layout, top to bottom

1. Header row: `<Heading>` on the left; on the right the page's action ("Mark all as read"), shown from a backend flag.
2. The rows, newest first, in a `ul`. Each row is a box with a border (`rounded-lg border`): an unread dot on the left, the message (`text-sm`, medium weight while unread), its time (`text-xs`, `text-muted-foreground`), and the row's action on the right. An unread row has a `muted` background.
3. `Pagination` under the rows.

## Rules

- The message text and the flags that say whether a row's action is possible come from the backend; React shows them.
- The same row component serves the page (the `list` variant) and the bell's `Popover` (the `menu` variant, the newest few rows). `FeedRow` (`components/shared`) draws the row; a domain wraps it with its own action, as `NotificationRow` does.

## Phone

Rows stay one column. A long message wraps and the action stays on the right.

## States

No messages: `EmptyState` ("You have no notifications."). Failed to load: `ErrorState` (states.md).

## Sample and demo

Copy `resources/js/pages/notifications/index.tsx`, `resources/js/components/notifications/notification-row.tsx` and the `FeedRow` it uses (`resources/js/components/shared/feed-row.tsx`). Demo: `resources/js/components/style/feed-demo.tsx`.
