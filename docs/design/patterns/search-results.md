# Search results

Use when the app searches more than one kind of record at once (tasks, customers, orders). Searching one list is the list's own search box (list.md), not this pattern.

## Layout, top to bottom

1. The search box sits in the top bar (`SearchBox`, `components/shared`): an `Input` with an `sr-only` label and a search icon. It is shown only when a project has something to search; the starter does not mount it in the app layout. Enter opens the results page with the text in the URL (`?q=invoice`); the box shows the text again there.
2. The results page: `<Heading>` "Search results", nothing under it.
3. Results grouped by type, each group under a `<Heading variant="small">` with its count ("Tasks (3)"). A group is a bordered box of line-separated rows (`SearchResults`, `components/shared`, built on `FeedList`): the record's name as a link to its detail page, then its status as a `Badge`. The matched text is highlighted with a `mark` styled by the `accent` token, never a colour of its own.
4. A group shows its first few rows and a "View all" `TextLink` to that type's list, with the search carried over.

## Phone

The box fills the top bar. Rows wrap: name first, then status.

## States

- Nothing matches: one `EmptyState` line ("No results for "invoice"") inside the page, centred.
- The box is empty: Enter does nothing.
- Data arriving later: `LoadingState`; failed to load: `ErrorState` (states.md).

## Rules

- The backend searches, groups and counts; React only shows (and highlights the text it was given).
- The query lives only in the URL.

## Demo

`resources/js/components/style/search-demo.tsx`, shown on the style overview page.
