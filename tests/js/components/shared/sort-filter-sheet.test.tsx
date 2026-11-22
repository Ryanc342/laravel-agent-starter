import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import SortFilterSheet from '@/components/shared/sort-filter-sheet';
import { TooltipProvider } from '@/components/ui/tooltip';

const state = vi.hoisted(() => ({ url: '/tasks', get: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    router: { get: state.get },
    usePage: () => ({
        url: state.url,
        props: { today: { date: '2026-11-14', year: 2026 } },
    }),
}));

beforeEach(() => {
    state.get.mockClear();
});

const columns = [
    {
        header: 'Title',
        menu: { sortKey: 'title', search: { param: 'search' } },
    },
    {
        header: 'Status',
        menu: {
            options: {
                param: 'status',
                choices: [{ value: 'open', label: 'Open' }],
            },
        },
    },
    { header: 'Amount' },
];

function sheet(url: string) {
    state.url = url;
    render(
        <TooltipProvider>
            <SortFilterSheet columns={columns} />
        </TooltipProvider>,
    );
    fireEvent.click(screen.getByRole('button', { name: 'Sort & filter' }));

    return screen.getByRole('dialog');
}

test('the sheet holds one part per column that has a menu', () => {
    const dialog = sheet('/tasks');

    expect(
        within(dialog)
            .getAllByRole('region')
            .map((part) => part.getAttribute('aria-label')),
    ).toEqual(['Title', 'Status']);
});

test('one Clear empties the sort, the search and every filter at once', () => {
    const dialog = sheet(
        '/tasks?sort=title&direction=asc&search=x&status=open',
    );

    fireEvent.click(within(dialog).getByRole('button', { name: 'Clear' }));

    expect(state.get).toHaveBeenCalledTimes(1);
    const asked = new URL(state.get.mock.calls[0][0], 'http://x').searchParams;
    expect([...asked.keys()]).toEqual([]);
});

test('a list without column menus has no sheet', () => {
    state.url = '/tasks';
    const { container } = render(
        <SortFilterSheet columns={[{ header: 'Amount' }]} />,
    );

    expect(container.innerHTML).toBe('');
});
