import { act, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
import ListToolbar from '@/components/shared/list-toolbar';
import { useIsMobile } from '@/hooks/use-mobile';

const state = vi.hoisted(() => ({
    url: '/tasks',
    get: vi.fn(),
    listeners: new Set<() => void>(),
}));

// Like Inertia's page context: a component that reads the page re-renders when the address changes.
vi.mock('@inertiajs/react', async () => {
    const { useSyncExternalStore } = await import('react');

    return {
        router: { get: state.get },
        usePage: () => ({
            props: { today: { date: '2026-11-14', year: 2026 } },
            url: useSyncExternalStore(
                (listener) => {
                    state.listeners.add(listener);

                    return () => state.listeners.delete(listener);
                },
                () => state.url,
            ),
        }),
    };
});
vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: vi.fn() }));

const columns = [
    {
        header: 'Title',
        menu: { sortKey: 'title', search: { param: 'search' } },
    },
    {
        header: 'Status',
        menu: {
            sortKey: 'status',
            options: {
                param: 'status',
                choices: [
                    { value: 'open', label: 'Open' },
                    { value: 'done', label: 'Done' },
                ],
            },
        },
    },
    {
        header: 'Due date',
        menu: {
            sortKey: 'due_on',
            range: {
                fromParam: 'due_from',
                toParam: 'due_to',
                type: 'date' as const,
            },
        },
    },
];

function visited(): URL {
    const calls = state.get.mock.calls;

    return new URL(calls[calls.length - 1][0], 'http://localhost');
}

beforeEach(() => {
    state.url = '/tasks';
    state.get.mockReset();
    vi.mocked(useIsMobile).mockReturnValue(false);
});

afterEach(() => {
    vi.useRealTimers();
});

test('the search box has a hidden label and asks after a pause in typing', () => {
    vi.useFakeTimers();
    render(<ListToolbar columns={columns} searchLabel="Search tasks" />);

    fireEvent.change(screen.getByRole('searchbox', { name: 'Search tasks' }), {
        target: { value: 'milk' },
    });
    expect(state.get).not.toHaveBeenCalled();

    act(() => {
        vi.advanceTimersByTime(500);
    });

    expect(visited().searchParams.get('search')).toBe('milk');
    expect(state.get.mock.calls[0][2]).toMatchObject({ preserveState: true });
});

test('the search box shows what the address already searches for', () => {
    state.url = '/tasks?search=quote';
    render(<ListToolbar columns={columns} searchLabel="Search tasks" />);

    expect(
        (
            screen.getByRole('searchbox', {
                name: 'Search tasks',
            }) as HTMLInputElement
        ).value,
    ).toBe('quote');
});

test('the search box follows the address when something else clears it', () => {
    state.url = '/tasks?search=quote';
    render(<ListToolbar columns={columns} searchLabel="Search tasks" />);

    act(() => {
        state.url = '/tasks';
        state.listeners.forEach((listener) => listener());
    });

    expect(
        (
            screen.getByRole('searchbox', {
                name: 'Search tasks',
            }) as HTMLInputElement
        ).value,
    ).toBe('');
});

test('on desktop there is no Sort & filter button', () => {
    render(<ListToolbar columns={columns} searchLabel="Search tasks" />);

    expect(screen.queryByRole('button', { name: 'Sort & filter' })).toBeNull();
});

test('on a phone a Sort & filter button opens a sheet with a section per column', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    render(<ListToolbar columns={columns} searchLabel="Search tasks" />);

    fireEvent.click(screen.getByRole('button', { name: 'Sort & filter' }));
    const sheet = screen.getByRole('dialog');

    for (const header of ['Title', 'Status', 'Due date']) {
        expect(sheet.textContent).toContain(header);
    }
    expect(
        screen.getAllByRole('button', { name: 'Sort ascending' }),
    ).toHaveLength(3);
    expect(screen.getByRole('checkbox', { name: 'Open' })).toBeTruthy();
    expect(screen.getByLabelText('From')).toBeTruthy();
});

test('a choice in the sheet changes the address like the column menu does', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    state.url = '/tasks?page=2&per_page=50';
    render(<ListToolbar columns={columns} searchLabel="Search tasks" />);

    fireEvent.click(screen.getByRole('button', { name: 'Sort & filter' }));
    fireEvent.click(screen.getByRole('checkbox', { name: 'Done' }));

    const address = visited();
    expect(address.searchParams.getAll('status[]')).toEqual(['done']);
    expect(address.searchParams.get('per_page')).toBe('50');
    expect(address.searchParams.has('page')).toBe(false);
});

test('Clear in the sheet removes every sort, filter and search but keeps rows per page', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    state.url =
        '/tasks?sort=title&direction=desc&status[]=open&due_from=2026-11-01&search=milk&per_page=50';
    render(<ListToolbar columns={columns} searchLabel="Search tasks" />);

    fireEvent.click(screen.getByRole('button', { name: 'Sort & filter' }));
    fireEvent.click(screen.getByRole('button', { name: 'Clear' }));

    expect(visited().search).toBe('?per_page=50');
});
