import { msg, t } from '@/lib/i18n';

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
        place(columns, String(id))?.card?.title ?? t('The card');

    const where = (id: string | number) => {
        const found = place(columns, String(id));

        if (!found) {
            return null;
        }

        return found.card
            ? t(':column, position :position of :total', {
                  column: found.column.label,
                  position: found.index + 1,
                  total: found.column.cards.length,
              })
            : found.column.cards.length === 0
              ? t(':column, which is empty', { column: found.column.label })
              : found.column.label;
    };

    return {
        onDragStart: ({ active }: Dragged) => {
            const found = place(columns, String(active.id));

            return found?.card
                ? t(
                      'Picked up :title from :column, position :position of :total.',
                      {
                          title: found.card.title,
                          column: found.column.label,
                          position: found.index + 1,
                          total: found.column.cards.length,
                      },
                  )
                : t('Picked up a card.');
        },
        onDragOver: ({ active, over }: DraggedOver) =>
            over
                ? t(':title is over :place.', {
                      title: titleOf(active.id),
                      place: where(over.id) ?? '',
                  })
                : t(':title is not over a column.', {
                      title: titleOf(active.id),
                  }),
        onDragEnd: ({ active, over }: DraggedOver) =>
            over
                ? t('Dropped :title in :place.', {
                      title: titleOf(active.id),
                      place: where(over.id) ?? '',
                  })
                : t(
                      ':title was dropped outside the columns and did not move.',
                      { title: titleOf(active.id) },
                  ),
        onDragCancel: ({ active }: Dragged) => {
            const found = place(columns, String(active.id));

            return found?.card
                ? t('Move cancelled. :title is still in :column.', {
                      title: found.card.title,
                      column: found.column.label,
                  })
                : t('Move cancelled.');
        },
    };
}

/** Read to a screen reader when focus is on a card's drag handle. */
export const kanbanInstructions = msg(
    'To pick up a card, press Space. Use the arrow keys to move it to another place or column, press Space again to drop it, or press Escape to cancel.',
);
