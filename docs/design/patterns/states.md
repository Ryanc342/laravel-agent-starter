# Empty, error and loading states

Use whenever a region of a page has nothing to show, failed to load, or is still loading. A region is a list, a table, a card or the body of a page.

| State | Component | Content |
| --- | --- | --- |
| Empty | `EmptyState` | one short line in `text-muted-foreground` saying what is missing ("You have no tasks yet."); the create button only when the page has none elsewhere (list.md keeps it in the header) |
| Loading | `LoadingState` | `Skeleton` shaped like what is coming: rows for a list, a block for a card |
| Error | `ErrorState` | what went wrong and how to fix it, and a retry `Button` when trying again can help |

## Rules

- Never a blank region. Never a spinner in place of content: a `Spinner` belongs inside a button that is sending.
- The empty line is the only explaining text on an empty page: no heading above it and nothing else.
- Results of actions and network errors are toasts, not an `ErrorState` (foundations.md, Messages). `ErrorState` is for a region that could not load.
- A page that does not exist, or that the user may not open, is an error page (error-page.md), not a state.

## Phone

The same blocks, full width. A skeleton row matches the phone layout of its list (a card), not the desktop one.

## Demo

`resources/js/components/style/states-demo.tsx`, shown on the style overview page. The notifications page (`resources/js/pages/notifications/index.tsx`) shows the empty line in use.
