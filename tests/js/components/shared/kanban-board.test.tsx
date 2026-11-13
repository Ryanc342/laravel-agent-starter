import { fireEvent, render, screen, within } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import KanbanBoard from '@/components/shared/kanban-board';
import type { KanbanColumn } from '@/lib/kanban';

const columns: KanbanColumn[] = [
    {
        id: 'open',
        label: 'Open',
        cards: [
            { id: 'a', title: 'Send quote' },
            { id: 'b', title: 'Call Mei' },
        ],
    },
    { id: 'doing', label: 'Doing', cards: [{ id: 'c', title: 'Pack order' }] },
    { id: 'done', label: 'Done', cards: [] },
];

function board() {
    const onMove = vi.fn();

    render(
        <KanbanBoard
            columns={columns}
            renderCard={(card) => <span>{card.title}</span>}
            onMove={onMove}
        />,
    );

    return onMove;
}

test('the columns show side by side, each with its name and its count', () => {
    board();

    expect(
        screen
            .getAllByRole('region')
            .map((column) => column.getAttribute('aria-label')),
    ).toEqual(['Open', 'Doing', 'Done']);
    expect(screen.getByRole('heading', { name: 'Open (2)' })).toBeTruthy();
    expect(screen.getByRole('heading', { name: 'Done (0)' })).toBeTruthy();
});

test('each card shows what the page gives it, in its column', () => {
    board();

    const open = screen.getByRole('region', { name: 'Open' });

    expect(within(open).getByText('Send quote')).toBeTruthy();
    expect(within(open).getByText('Call Mei')).toBeTruthy();
    expect(
        within(screen.getByRole('region', { name: 'Doing' })).getByText(
            'Pack order',
        ),
    ).toBeTruthy();
});

test('an empty column says so inside itself', () => {
    board();

    expect(
        within(screen.getByRole('region', { name: 'Done' })).getByText(
            'No cards.',
        ),
    ).toBeTruthy();
});

test('the "Move to" menu lists the other columns and asks for the move to the end of one', async () => {
    const onMove = board();

    fireEvent.pointerDown(
        screen.getByRole('button', { name: 'Move Send quote to' }),
        { button: 0, ctrlKey: false },
    );

    expect(await screen.findByText('Move to')).toBeTruthy();
    expect(screen.queryByRole('menuitem', { name: 'Open' })).toBeNull();

    fireEvent.click(screen.getByRole('menuitem', { name: 'Doing' }));

    expect(onMove).toHaveBeenCalledWith({
        cardId: 'a',
        toColumn: 'doing',
        toIndex: 1,
    });
});

test('every card has a handle for dragging, a button so the keyboard can lift it', () => {
    board();

    const handle = screen.getByRole('button', { name: 'Drag Send quote' });

    expect(handle.tagName).toBe('BUTTON');
    expect(handle.getAttribute('aria-roledescription')).toBe('sortable');
});

test('on a phone the columns scroll sideways inside the board and are narrower than the screen', () => {
    board();

    const open = screen.getByRole('region', { name: 'Open' });

    expect(open.parentElement?.className).toContain('overflow-x-auto');
    expect(open.className).toContain('w-[80%]');
});

test('a screen reader is told how to drag a card with the keyboard, in words, not ids', () => {
    board();

    const handle = screen.getByRole('button', { name: 'Drag Send quote' });
    const instructions = document.getElementById(
        handle.getAttribute('aria-describedby') ?? '',
    );

    expect(instructions?.textContent).toContain('press Space');
    expect(instructions?.textContent).not.toContain('Draggable item');
});
