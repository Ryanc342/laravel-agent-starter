import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import KanbanBoard from '@/components/shared/kanban-board';
import { Button } from '@/components/ui/button';
import type { KanbanColumn, KanbanMove } from '@/lib/kanban';
import { t } from '@/lib/i18n';
import Actions from '@/components/shared/actions';

export type ExampleCard = { id: string; title: string; owner: string };

/** Apply a move to the demo's own copy of the columns. A real board asks the backend and shows what it sends back. */
function applyMove(
    columns: KanbanColumn<ExampleCard>[],
    move: KanbanMove,
): KanbanColumn<ExampleCard>[] {
    const card = columns
        .flatMap((column) => column.cards)
        .find((item) => item.id === move.cardId);

    if (!card) {
        return columns;
    }

    return columns.map((column) => {
        const cards = column.cards.filter((item) => item.id !== move.cardId);

        if (column.id === move.toColumn) {
            cards.splice(Math.min(move.toIndex, cards.length), 0, card);
        }

        return { ...column, cards };
    });
}

/**
 * Records in status columns that cards move between: by dragging (the pointer
 * or the keyboard) or with the "Move to" menu on each card. On a phone the
 * columns scroll sideways, each narrower than the screen.
 */
export default function KanbanDemo({
    columns: initial,
}: {
    columns: KanbanColumn<ExampleCard>[];
}) {
    const [columns, setColumns] = useState(() =>
        initial.map((column) => ({ ...column, label: t(column.label) })),
    );

    return (
        <div className="grid gap-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading title={t('Orders')} />
                <Actions>
                    <Button>{t('Add order')}</Button>
                </Actions>
            </div>
            <KanbanBoard
                columns={columns}
                onMove={(move) => {
                    setColumns(applyMove(columns, move));
                    toast.success(t('Order moved.'), { id: 'kanban-move' });
                }}
                renderCard={(card) => (
                    <>
                        <a
                            href="#kanban"
                            className="font-medium break-words underline-offset-4 hover:underline active:text-primary-pressed"
                        >
                            {card.title}
                        </a>
                        <span className="text-xs text-muted-foreground">
                            {card.owner}
                        </span>
                    </>
                )}
            />
        </div>
    );
}
