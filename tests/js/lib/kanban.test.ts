import { expect, test } from 'vite-plus/test';
import { dropTarget, type KanbanColumn } from '@/lib/kanban';

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
