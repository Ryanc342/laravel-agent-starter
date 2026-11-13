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
