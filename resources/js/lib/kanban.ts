export type KanbanCard = { id: string; title: string };
export type KanbanColumn<Card extends KanbanCard = KanbanCard> = {
    id: string;
    label: string;
    cards: Card[];
};

export type KanbanMove = {
    cardId: string;
    toColumn: string;
    /** The place in the column the card lands, counted from 0. */
    toIndex: number;
};

/**
 * Where a drag ended: `overId` is a card or an (empty) column. Returns the move
 * to ask the backend for, or null when the card dropped where it was.
 */
export function dropTarget<Card extends KanbanCard>(
    columns: KanbanColumn<Card>[],
    activeId: string,
    overId: string,
): KanbanMove | null {
    const from = columns.find((column) =>
        column.cards.some((card) => card.id === activeId),
    );
    const overColumn = columns.find(
        (column) =>
            column.id === overId ||
            column.cards.some((card) => card.id === overId),
    );

    if (!from || !overColumn) {
        return null;
    }

    const fromIndex = from.cards.findIndex((card) => card.id === activeId);
    const overIndex = overColumn.cards.findIndex((card) => card.id === overId);
    // Dropped on the column itself: the end of it. Dropped on a card: that card's place.
    const toIndex =
        overIndex === -1
            ? overColumn.cards.length - (overColumn === from ? 1 : 0)
            : overIndex;

    return from.id === overColumn.id && fromIndex === toIndex
        ? null
        : { cardId: activeId, toColumn: overColumn.id, toIndex };
}

/** Where a card, or a whole column, is on the board. */
function place<Card extends KanbanCard>(
    columns: KanbanColumn<Card>[],
    id: string,
) {
    for (const column of columns) {
        const index = column.cards.findIndex((card) => card.id === id);

        if (index !== -1) {
            return { column, card: column.cards[index], index };
        }
    }

    const column = columns.find((item) => item.id === id);

    return column ? { column, card: null, index: -1 } : null;
}

type Dragged = { active: { id: string | number } };
type DraggedOver = Dragged & { over: { id: string | number } | null };

/**
 * What a screen reader hears while a card is dragged with the keyboard: the
 * card's title and the column's status (its label), never dnd-kit's ids
 * ("Draggable item o2 was moved over droppable area o2"). Handed to
 * `DndContext` as `accessibility.announcements`.
 */
export function kanbanAnnouncements<Card extends KanbanCard>(
    columns: KanbanColumn<Card>[],
) {
    const titleOf = (id: string | number) =>
        place(columns, String(id))?.card?.title ?? 'The card';

    const where = (id: string | number) => {
        const found = place(columns, String(id));

        if (!found) {
            return null;
        }

        return found.card
            ? `${found.column.label}, position ${found.index + 1} of ${found.column.cards.length}`
            : found.column.cards.length === 0
              ? `${found.column.label}, which is empty`
              : found.column.label;
    };

    return {
        onDragStart: ({ active }: Dragged) => {
            const found = place(columns, String(active.id));

            return found?.card
                ? `Picked up ${found.card.title} from ${found.column.label}, position ${found.index + 1} of ${found.column.cards.length}.`
                : 'Picked up a card.';
        },
        onDragOver: ({ active, over }: DraggedOver) =>
            over
                ? `${titleOf(active.id)} is over ${where(over.id) ?? ''}.`
                : `${titleOf(active.id)} is not over a column.`,
        onDragEnd: ({ active, over }: DraggedOver) =>
            over
                ? `Dropped ${titleOf(active.id)} in ${where(over.id) ?? ''}.`
                : `${titleOf(active.id)} was dropped outside the columns and did not move.`,
        onDragCancel: ({ active }: Dragged) => {
            const found = place(columns, String(active.id));

            return found?.card
                ? `Move cancelled. ${found.card.title} is still in ${found.column.label}.`
                : 'Move cancelled.';
        },
    };
}

/** Read to a screen reader when focus is on a card's drag handle. */
export const kanbanInstructions =
    'To pick up a card, press Space. Use the arrow keys to move it to another place or column, press Space again to drop it, or press Escape to cancel.';
