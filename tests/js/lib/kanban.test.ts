import { expect, test } from 'vite-plus/test';
import {
    dropTarget,
    kanbanAnnouncements,
    kanbanInstructions,
    type KanbanColumn,
} from '@/lib/kanban';

const columns: KanbanColumn[] = [
    {
        id: 'open',
        label: 'Open',
        cards: [
            { id: 'a', title: 'A' },
            { id: 'b', title: 'B' },
            { id: 'c', title: 'C' },
        ],
    },
    { id: 'doing', label: 'Doing', cards: [{ id: 'd', title: 'D' }] },
    { id: 'done', label: 'Done', cards: [] },
];

test('dropping on a card in another column lands in that card’s place', () => {
    expect(dropTarget(columns, 'a', 'd')).toEqual({
        cardId: 'a',
        toColumn: 'doing',
        toIndex: 0,
    });
});

test('dropping on an empty column, or on a column itself, lands at its end', () => {
    expect(dropTarget(columns, 'a', 'done')).toEqual({
        cardId: 'a',
        toColumn: 'done',
        toIndex: 0,
    });
    expect(dropTarget(columns, 'a', 'doing')).toEqual({
        cardId: 'a',
        toColumn: 'doing',
        toIndex: 1,
    });
});

test('dropping on another card of the same column reorders it', () => {
    expect(dropTarget(columns, 'a', 'c')).toEqual({
        cardId: 'a',
        toColumn: 'open',
        toIndex: 2,
    });
});

test('dropping a card where it was is not a move', () => {
    expect(dropTarget(columns, 'b', 'b')).toBeNull();
    expect(dropTarget(columns, 'c', 'open')).toBeNull();
});

test('a card or a place that does not exist is not a move', () => {
    expect(dropTarget(columns, 'zzz', 'done')).toBeNull();
    expect(dropTarget(columns, 'a', 'nowhere')).toBeNull();
});

// What a screen reader hears while a card is dragged with the keyboard: the
// card's title and the column's status, never dnd-kit's ids.
const say = kanbanAnnouncements(columns);
const at = (activeId: string, overId?: string) => ({
    active: { id: activeId },
    over: overId === undefined ? null : { id: overId },
});

test('picking a card up says its title and its column', () => {
    expect(say.onDragStart?.({ active: { id: 'b' } } as never)).toBe(
        'Picked up B from Open, position 2 of 3.',
    );
});

test('moving over a card in another column says the column and the place', () => {
    expect(say.onDragOver?.(at('a', 'd') as never)).toBe(
        'A is over Doing, position 1 of 1.',
    );
});

test('moving over an empty column says the column', () => {
    expect(say.onDragOver?.(at('a', 'done') as never)).toBe(
        'A is over Done, which is empty.',
    );
});

test('moving over nothing says so', () => {
    expect(say.onDragOver?.(at('a') as never)).toBe('A is not over a column.');
});

test('dropping says where the card landed, and dropping outside says it did not move', () => {
    expect(say.onDragEnd?.(at('a', 'd') as never)).toBe(
        'Dropped A in Doing, position 1 of 1.',
    );
    expect(say.onDragEnd?.(at('a') as never)).toBe(
        'A was dropped outside the columns and did not move.',
    );
});

test('cancelling says the card is back where it was', () => {
    expect(say.onDragCancel?.(at('c') as never)).toBe(
        'Move cancelled. C is still in Open.',
    );
});

test('the instructions say how to lift, move, drop and cancel with the keyboard', () => {
    expect(kanbanInstructions).toContain('Space');
    expect(kanbanInstructions).toContain('arrow keys');
    expect(kanbanInstructions).toContain('Escape');
});
