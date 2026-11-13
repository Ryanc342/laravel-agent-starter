import { act, fireEvent, render, screen, within } from '@testing-library/react';
import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
import ColumnMenu from '@/components/shared/column-menu';

const state = vi.hoisted(() => ({ url: '/tasks', get: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    router: { get: state.get },
    usePage: () => ({ url: state.url }),
}));

const statuses = [
    { value: 'open', label: 'Open' },
    { value: 'done', label: 'Done' },
    { value: 'archived', label: 'Archived' },
];

function title() {
    return (
        <ColumnMenu
            header="Title"
            menu={{ sortKey: 'title', search: { param: 'search' } }}
        />
    );
}

function status() {
    return (
        <ColumnMenu
            header="Status"
            menu={{
                sortKey: 'status',
                options: { param: 'status', choices: statuses },
            }}
        />
    );
}

function due() {
    return (
        <ColumnMenu
            header="Due date"
            menu={{
                sortKey: 'due_on',
                range: {
                    fromParam: 'due_from',
                    toParam: 'due_to',
                    type: 'date',
                },
            }}
        />
    );
}

function open(name: string) {
    fireEvent.click(
        screen.getByRole('button', { name: new RegExp(`^${name}`) }),
    );
}

function visited(): URL {
    const calls = state.get.mock.calls;

    return new URL(calls[calls.length - 1][0], 'http://localhost');
}

beforeEach(() => {
    state.url = '/tasks';
    state.get.mockReset();
});

afterEach(() => {
    vi.useRealTimers();
});

test('a text column menu offers both sorts and a search inside the column', () => {
    render(title());
    open('Title');

    expect(screen.getByRole('button', { name: 'Sort ascending' })).toBeTruthy();
    expect(
        screen.getByRole('button', { name: 'Sort descending' }),
    ).toBeTruthy();
    expect(
        screen.getByRole('searchbox', { name: 'Search Title' }),
    ).toBeTruthy();
    expect(screen.getByRole('button', { name: 'Clear' })).toBeTruthy();
});

test('sorting asks for the list sorted, keeps rows per page and goes back to page 1', () => {
    state.url = '/tasks?page=3&per_page=50';
    render(title());
    open('Title');
    fireEvent.click(screen.getByRole('button', { name: 'Sort descending' }));

    const address = visited();
    expect(address.pathname).toBe('/tasks');
    expect(address.searchParams.get('sort')).toBe('title');
    expect(address.searchParams.get('direction')).toBe('desc');
    expect(address.searchParams.get('per_page')).toBe('50');
    expect(address.searchParams.has('page')).toBe(false);
    expect(state.get.mock.calls[0][2]).toMatchObject({ preserveState: true });
});

test('a sorted column shows an arrow in the primary colour, another column shows none', () => {
    state.url = '/tasks?sort=title&direction=asc';
    const { container } = render(
        <>
            {title()}
            {status()}
        </>,
    );
    const arrows = container.querySelectorAll('[data-slot="sort-indicator"]');

    expect(arrows).toHaveLength(1);
    expect(arrows[0].getAttribute('class')).toContain('text-primary');
    expect(
        within(screen.getByRole('button', { name: /^Title/ })).getByText(
            /sorted ascending/,
        ),
    ).toBeTruthy();
});

test('a header that can be clicked shows a faint grey up-down arrow at rest', () => {
    state.url = '/tasks';
    const { container } = render(
        <>
            {title()}
            {status()}
        </>,
    );
    const arrows = container.querySelectorAll(
        '[data-slot="sortable-indicator"]',
    );

    expect(arrows).toHaveLength(2);
    expect(arrows[0].getAttribute('class')).toContain('text-muted-foreground');
    expect(arrows[0].getAttribute('class')).not.toContain('text-primary');
    expect(
        container.querySelectorAll('[data-slot="sort-indicator"]'),
    ).toHaveLength(0);
    expect(
        container.querySelectorAll('[data-slot="filter-indicator"]'),
    ).toHaveLength(0);
});

test('a sorted header swaps the up-down arrow for the blue direction', () => {
    state.url = '/tasks?sort=title&direction=desc';
    const { container } = render(
        <>
            {title()}
            {status()}
        </>,
    );
    const titleButton = screen.getByRole('button', { name: /^Title/ });
    const statusButton = screen.getByRole('button', { name: /^Status/ });

    expect(
        titleButton.querySelector('[data-slot="sortable-indicator"]'),
    ).toBeNull();
    expect(
        titleButton.querySelector('[data-slot="sort-indicator"]'),
    ).not.toBeNull();
    expect(
        statusButton.querySelector('[data-slot="sortable-indicator"]'),
    ).not.toBeNull();
    expect(
        container.querySelectorAll('[data-slot="sortable-indicator"]'),
    ).toHaveLength(1);
});

test('a filtered header keeps the up-down arrow and adds the blue funnel', () => {
    state.url = '/tasks?status[]=open';
    render(status());
    const button = screen.getByRole('button', { name: /^Status/ });

    expect(
        button.querySelector('[data-slot="sortable-indicator"]'),
    ).not.toBeNull();
    expect(
        button
            .querySelector('[data-slot="filter-indicator"]')
            ?.getAttribute('class'),
    ).toContain('text-primary');
});

test('a filtered column shows a funnel in the primary colour', () => {
    state.url = '/tasks?status[]=open';
    const { container } = render(
        <>
            {title()}
            {status()}
        </>,
    );
    const funnels = container.querySelectorAll(
        '[data-slot="filter-indicator"]',
    );

    expect(funnels).toHaveLength(1);
    expect(funnels[0].getAttribute('class')).toContain('text-primary');
    expect(
        screen.getByRole('button', { name: /^Status/ }).contains(funnels[0]),
    ).toBe(true);
});

test('typing in the column search waits for a pause, then asks for it', () => {
    vi.useFakeTimers();
    render(title());
    open('Title');
    const box = screen.getByRole('searchbox', { name: 'Search Title' });

    fireEvent.change(box, { target: { value: 'milk' } });
    expect(state.get).not.toHaveBeenCalled();

    act(() => {
        vi.advanceTimersByTime(500);
    });

    expect(visited().searchParams.get('search')).toBe('milk');
});

test('pressing Enter in the column search asks at once', () => {
    render(title());
    open('Title');
    const box = screen.getByRole('searchbox', { name: 'Search Title' });

    fireEvent.change(box, { target: { value: 'quote' } });
    fireEvent.keyDown(box, { key: 'Enter' });

    expect(visited().searchParams.get('search')).toBe('quote');
});

test('a tick list ticks several options together', () => {
    state.url = '/tasks?status[]=open';
    render(status());
    open('Status');

    expect(
        (
            screen.getByRole('checkbox', { name: 'Open' }) as HTMLButtonElement
        ).getAttribute('aria-checked'),
    ).toBe('true');

    fireEvent.click(screen.getByRole('checkbox', { name: 'Done' }));

    expect(visited().searchParams.getAll('status[]')).toEqual(['open', 'done']);
});

test('unticking the last option removes the filter', () => {
    state.url = '/tasks?status[]=open';
    render(status());
    open('Status');
    fireEvent.click(screen.getByRole('checkbox', { name: 'Open' }));

    expect(visited().searchParams.has('status[]')).toBe(false);
});

test('Select all ticks every option and Unselect all ticks none', () => {
    const { rerender } = render(status());
    open('Status');
    fireEvent.click(screen.getByRole('button', { name: 'Select all' }));

    expect(visited().searchParams.getAll('status[]')).toEqual([
        'open',
        'done',
        'archived',
    ]);

    state.url = '/tasks?status[]=open&status[]=done&per_page=20';
    state.get.mockReset();
    rerender(status());
    fireEvent.click(screen.getByRole('button', { name: 'Unselect all' }));

    expect(visited().searchParams.has('status[]')).toBe(false);
    expect(visited().searchParams.get('per_page')).toBe('20');
});

test('a range asks for from and to, and the ends are sent as typed', () => {
    const { rerender } = render(due());
    open('Due date');

    fireEvent.change(screen.getByLabelText('From'), {
        target: { value: '2026-11-01' },
    });
    expect(visited().searchParams.get('due_from')).toBe('2026-11-01');

    state.url = '/tasks?due_from=2026-11-01';
    rerender(due());
    fireEvent.change(screen.getByLabelText('To'), {
        target: { value: '2026-11-30' },
    });
    expect(visited().searchParams.get('due_from')).toBe('2026-11-01');
    expect(visited().searchParams.get('due_to')).toBe('2026-11-30');
});

test("Clear removes this column's sort and filters and nothing else", () => {
    state.url =
        '/tasks?sort=status&direction=asc&status[]=open&search=milk&per_page=50&page=2';
    render(status());
    open('Status');
    fireEvent.click(screen.getByRole('button', { name: 'Clear' }));

    const address = visited();
    expect(address.searchParams.has('sort')).toBe(false);
    expect(address.searchParams.has('direction')).toBe(false);
    expect(address.searchParams.has('status[]')).toBe(false);
    expect(address.searchParams.get('search')).toBe('milk');
    expect(address.searchParams.get('per_page')).toBe('50');
    expect(address.searchParams.has('page')).toBe(false);
});

test('Clear keeps the sort of another column', () => {
    state.url = '/tasks?sort=title&direction=asc&status[]=open';
    render(status());
    open('Status');
    fireEvent.click(screen.getByRole('button', { name: 'Clear' }));

    const address = visited();
    expect(address.searchParams.get('sort')).toBe('title');
    expect(address.searchParams.get('direction')).toBe('asc');
    expect(address.searchParams.has('status[]')).toBe(false);
});

test('a blank search or a status the menu does not offer does not light the funnel', () => {
    state.url = '/tasks?search=%20%20&status[]=bogus';
    const { container } = render(
        <>
            {title()}
            {status()}
        </>,
    );

    expect(
        container.querySelectorAll('[data-slot="filter-indicator"]'),
    ).toHaveLength(0);
});
