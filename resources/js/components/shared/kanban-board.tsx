import {
    DndContext,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
    closestCorners,
    useDroppable,
    useSensor,
    useSensors,
    type DragEndEvent,
    type DragStartEvent,
} from '@dnd-kit/core';
import {
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { GripVertical, MoveRight } from 'lucide-react';
import { useId, useState, type ReactNode } from 'react';
import EmptyState from '@/components/shared/empty-state';
import Heading from '@/components/heading';
import IconButton from '@/components/shared/icon-button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    dropTarget,
    kanbanAnnouncements,
    kanbanInstructions,
    type KanbanCard,
    type KanbanColumn,
    type KanbanMove,
} from '@/lib/kanban';
import { cn } from '@/lib/utils';

type Props<Card extends KanbanCard> = {
    columns: KanbanColumn<Card>[];
    /** What a card shows: the record's name as a link, then a fact or two. */
    renderCard: (card: Card) => ReactNode;
    /** Ask the backend for the move. The board shows what the backend sends back. */
    onMove: (move: KanbanMove) => void;
};

function CardShell<Card extends KanbanCard>({
    card,
    columns,
    column,
    renderCard,
    onMove,
}: { card: Card; column: string; columns: KanbanColumn<Card>[] } & Pick<
    Props<Card>,
    'renderCard' | 'onMove'
>) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: card.id });
    const others = columns.filter((other) => other.id !== column);

    return (
        <li
            ref={setNodeRef}
            style={{
                transform: transform
                    ? `translate3d(${transform.x}px, ${transform.y}px, 0)`
                    : undefined,
                transition,
            }}
            className={cn(
                'flex items-start gap-1 rounded-lg border bg-background p-3',
                isDragging && 'opacity-50',
            )}
        >
            <button
                type="button"
                aria-label={`Drag ${card.title}`}
                className="mt-0.5 shrink-0 touch-none rounded-md text-muted-foreground transition-colors hover:bg-accent active:bg-accent-pressed"
                {...attributes}
                {...listeners}
            >
                <GripVertical className="size-4" />
            </button>
            <div className="grid min-w-0 flex-1 gap-1 text-sm">
                {renderCard(card)}
            </div>
            <DropdownMenu>
                <IconButton
                    label={`Move ${card.title} to`}
                    trigger={DropdownMenuTrigger}
                    size="icon-sm"
                    className="shrink-0"
                >
                    <MoveRight />
                </IconButton>
                <DropdownMenuContent align="end">
                    <DropdownMenuLabel>Move to</DropdownMenuLabel>
                    {others.map((other) => (
                        <DropdownMenuItem
                            key={other.id}
                            onSelect={() =>
                                onMove({
                                    cardId: card.id,
                                    toColumn: other.id,
                                    toIndex: other.cards.length,
                                })
                            }
                        >
                            {other.label}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
        </li>
    );
}

function Column<Card extends KanbanCard>({
    column,
    columns,
    renderCard,
    onMove,
}: { column: KanbanColumn<Card>; columns: KanbanColumn<Card>[] } & Pick<
    Props<Card>,
    'renderCard' | 'onMove'
>) {
    const { setNodeRef } = useDroppable({ id: column.id });

    return (
        <section
            aria-label={column.label}
            className="grid w-4/5 shrink-0 content-start gap-3 rounded-lg border bg-muted p-3 md:w-auto md:shrink"
        >
            <Heading
                variant="small"
                title={`${column.label} (${column.cards.length})`}
            />
            <SortableContext
                items={column.cards.map((card) => card.id)}
                strategy={verticalListSortingStrategy}
            >
                <ul
                    ref={setNodeRef}
                    className="grid min-h-16 content-start gap-2"
                >
                    {column.cards.length === 0 ? (
                        <li className="py-4">
                            <EmptyState message="No cards." />
                        </li>
                    ) : (
                        column.cards.map((card) => (
                            <CardShell
                                key={card.id}
                                card={card}
                                column={column.id}
                                columns={columns}
                                renderCard={renderCard}
                                onMove={onMove}
                            />
                        ))
                    )}
                </ul>
            </SortableContext>
        </section>
    );
}

/**
 * The columns side by side, one per status, and cards that move between them
 * by dragging (the pointer, or the keyboard: Space to lift, the arrows to move,
 * Space to drop) or with the "Move to" menu on each card. The columns, their
 * order and which moves are allowed come from the backend; the board only asks
 * (`onMove`). On a phone the columns scroll sideways inside the board, each a
 * little narrower than the screen so the start of the next one shows.
 */
export default function KanbanBoard<Card extends KanbanCard>({
    columns,
    renderCard,
    onMove,
}: Props<Card>) {
    const [dragging, setDragging] = useState<Card | null>(null);
    // A stable id, so the server and the browser name the drag instructions alike.
    const dndId = useId();
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const start = (event: DragStartEvent) =>
        setDragging(
            columns
                .flatMap((column) => column.cards)
                .find((card) => card.id === event.active.id) ?? null,
        );

    const end = (event: DragEndEvent) => {
        setDragging(null);

        const move = event.over
            ? dropTarget(
                  columns,
                  String(event.active.id),
                  String(event.over.id),
              )
            : null;

        if (move) {
            onMove(move);
        }
    };

    return (
        <DndContext
            id={dndId}
            sensors={sensors}
            collisionDetection={closestCorners}
            accessibility={{
                announcements: kanbanAnnouncements(columns),
                screenReaderInstructions: { draggable: kanbanInstructions },
            }}
            onDragStart={start}
            onDragEnd={end}
            onDragCancel={() => setDragging(null)}
        >
            <div
                className="flex gap-4 overflow-x-auto md:grid md:overflow-visible"
                style={{
                    gridTemplateColumns: `repeat(${columns.length}, minmax(0, 1fr))`,
                }}
            >
                {columns.map((column) => (
                    <Column
                        key={column.id}
                        column={column}
                        columns={columns}
                        renderCard={renderCard}
                        onMove={onMove}
                    />
                ))}
            </div>
            <DragOverlay>
                {dragging ? (
                    <div className="rounded-lg border bg-background p-3 text-sm shadow-md">
                        {renderCard(dragging)}
                    </div>
                ) : null}
            </DragOverlay>
        </DndContext>
    );
}
