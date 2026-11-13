# Kanban board

Use for records that move through a few statuses and that people want to see side by side (a sales pipeline, a task board). A list with a status column (list.md) is still the default.

## Layout, top to bottom

1. Header row: `<Heading>`; on the right the page's actions (the create button, form.md).
2. `KanbanBoard` (`components/shared`): the columns side by side, one per status. A column has its status name and a count in a `<Heading variant="small">`, then its cards. A column is a bordered `rounded-lg` box with a `muted` background; a card is a white bordered box (one level only: a card in a column is the allowed repeated item, never a card inside a card).
3. A card shows the record's name as a link to its detail page, then one or two facts (the owner's `Avatar`, a due date). Nothing else.

## Moving a card

- By dragging (dnd-kit): drop the card in another column or in another place in the same column. The keyboard works too: focus a card, press Space, move with the arrow keys and press Space again; screen readers hear the move.
- With the "Move to" menu on each card (`DropdownMenu`, an icon `Button` `ghost` with a `Tooltip` and an `aria-label`), which lists the other columns. This is the way on a phone, and a way without dragging for everyone.
- A move is saved at once; a toast says what happened, and a refusal (the backend says the move is not allowed) puts the card back and shows the reason as a toast.

## Rules

- The columns, their order and which moves are allowed come from the backend (a status enum). React never decides a status change.
- Hover and press on a card follow foundations.md; a card being dragged has the popover shadow.

## Phone

The columns scroll sideways inside the board, each a little narrower than the screen so the start of the next one shows. The page itself never scrolls sideways.

## States

A column with no cards shows one `EmptyState` line inside it. A board that failed to load shows `ErrorState` (states.md).

## Demo

`resources/js/components/style/kanban-demo.tsx`, shown on the style overview page.
