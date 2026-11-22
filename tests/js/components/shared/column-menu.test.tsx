import { act, fireEvent, render, screen, within } from '@testing-library/react';
import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
import ColumnMenu, { ColumnMenuContent } from '@/components/shared/column-menu';
import { toDateValue } from '@/lib/date-value';

const state = vi.hoisted(() => ({ url: '/tasks', get: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    router: { get: state.get },
    usePage: () => ({
        url: state.url,
        props: { today: { date: '2026-11-14', year: 2026 } },
    }),
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
        screen.getByRole('searchbox', { name: 'Search title' }),
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
        button.querySelector('[data-slot="filter-indicator"]'),
    ).not.toBeNull();
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
    expect(
        screen.getByRole('button', { name: /^Status/ }).contains(funnels[0]),
    ).toBe(true);
});

test('typing in the column search waits for a pause, then asks for it', () => {
    vi.useFakeTimers();
    render(title());
    open('Title');
    const box = screen.getByRole('searchbox', { name: 'Search title' });

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
    const box = screen.getByRole('searchbox', { name: 'Search title' });

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

// Opens the picker behind a label and picks the 15th of the month it shows
// (the picker opens on the month of its value, or this month when it is empty).
function pickThe15th(label: string) {
    fireEvent.click(screen.getByLabelText(label));
    fireEvent.click(within(screen.getByRole('grid')).getByText('15'));
}

test('a range asks for from and to, and the ends are picked as YYYY-MM-DD', () => {
    const month = toDateValue(new Date()).slice(0, 7);
    const { rerender } = render(due());
    open('Due date');

    pickThe15th('From');
    expect(visited().searchParams.get('due_from')).toBe(`${month}-15`);

    state.url = `/tasks?due_from=${month}-15`;
    rerender(due());
    pickThe15th('To');
    expect(visited().searchParams.get('due_from')).toBe(`${month}-15`);
    expect(visited().searchParams.get('due_to')).toBe(`${month}-15`);
});

test('a picked date can be cleared from the picker', () => {
    state.url = '/tasks?due_from=2026-11-04';
    render(due());
    open('Due date');

    fireEvent.click(screen.getByLabelText('From'));
    const pickers = document.querySelectorAll('[data-slot="popover-content"]');

    fireEvent.click(
        within(pickers[pickers.length - 1] as HTMLElement).getByRole('button', {
            name: 'Clear',
        }),
    );

    expect(visited().searchParams.has('due_from')).toBe(false);
});

test('no native date input is left in the range', () => {
    render(due());
    open('Due date');

    expect(menu().querySelector('input[type="date"]')).toBeNull();
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

function menu(): HTMLElement {
    return document.querySelector('[data-slot="popover-content"]')!;
}

function separators(): Element[] {
    return Array.from(menu().querySelectorAll('[data-slot="separator-root"]'));
}

/** Whether `a` comes before `b` in the document. */
function before(a: Element, b: Element): boolean {
    return Boolean(
        a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING,
    );
}

test.each([
    ['a text menu', title, 'Title'],
    ['a tick list menu', status, 'Status'],
    ['a range menu', due, 'Due date'],
])(
    '%s: every choice sits in a row with the same marker',
    (_name, build, header) => {
        render(build());
        open(header);

        const choices = menu().querySelectorAll(
            'button, [role="checkbox"], input',
        );

        expect(choices.length).toBeGreaterThan(0);

        for (const choice of Array.from(choices)) {
            expect(
                choice.closest('[data-slot="column-menu-row"]'),
            ).not.toBeNull();
        }
    },
);

test('the rows of a tick list menu come in the agreed order, divided by lines', () => {
    render(status());
    open('Status');

    const rows = Array.from(
        menu().querySelectorAll('[data-slot="column-menu-row"]'),
    ).map((row) => row.textContent?.trim());

    expect(rows).toEqual([
        'Sort ascending',
        'Sort descending',
        'Select all',
        'Unselect all',
        'Open',
        'Done',
        'Archived',
        'Clear',
    ]);

    const lines = separators();
    const row = (name: string) =>
        screen.getByRole('button', { name }) as HTMLElement;

    expect(lines).toHaveLength(3);
    expect(
        before(row('Sort descending'), lines[0]) &&
            before(lines[0], row('Select all')),
    ).toBe(true);
    expect(
        before(row('Unselect all'), lines[1]) &&
            before(lines[1], screen.getByRole('checkbox', { name: 'Open' })),
    ).toBe(true);
    expect(
        before(screen.getByRole('checkbox', { name: 'Archived' }), lines[2]) &&
            before(lines[2], row('Clear')),
    ).toBe(true);
});

test('a text menu and a range menu have a line between sort, the filter and Clear', () => {
    const { unmount } = render(title());
    open('Title');
    const lines = separators();
    const search = screen.getByRole('searchbox', { name: 'Search title' });

    expect(lines).toHaveLength(2);
    expect(
        before(
            screen.getByRole('button', { name: 'Sort descending' }),
            lines[0],
        ) && before(lines[0], search),
    ).toBe(true);
    expect(
        before(search, lines[1]) &&
            before(lines[1], screen.getByRole('button', { name: 'Clear' })),
    ).toBe(true);
    unmount();

    render(due());
    open('Due date');

    expect(separators()).toHaveLength(2);
    expect(
        menu().querySelectorAll('[data-slot="column-menu-row"]'),
    ).toHaveLength(5);
});

test('the range ends are one row each, a box with its label inside', () => {
    render(due());
    open('Due date');

    for (const label of ['From', 'To']) {
        const input = screen.getByLabelText(label);
        const row = input.closest('[data-slot="column-menu-row"]')!;

        expect(
            row.querySelectorAll('input, [data-slot="date-picker"]'),
        ).toHaveLength(1);
        expect(
            before(input, row.querySelector(`label[for="${input.id}"]`)!),
        ).toBe(true);
    }
});

test('a tick row is clickable all over: its label wraps the checkbox', () => {
    render(status());
    open('Status');

    const checkbox = screen.getByRole('checkbox', { name: 'Done' });
    const row = checkbox.closest('[data-slot="column-menu-row"]')!;

    expect(row.tagName).toBe('LABEL');
    expect(row.contains(checkbox)).toBe(true);
});

test('only the sort row in use has the selected colour', () => {
    state.url = '/tasks?sort=title&direction=desc';
    render(title());
    open('Title');

    const ascending = screen.getByRole('button', { name: 'Sort ascending' });
    const descending = screen.getByRole('button', { name: 'Sort descending' });

    expect(descending.getAttribute('aria-pressed')).toBe('true');
    expect(ascending.getAttribute('aria-pressed')).toBe('false');
});

test('without Clear the menu ends with its filter and has no trailing line', () => {
    render(
        <ColumnMenuContent
            header="Status"
            menu={{
                sortKey: 'status',
                options: { param: 'status', choices: statuses },
            }}
            clearable={false}
        />,
    );

    expect(screen.queryByRole('button', { name: 'Clear' })).toBeNull();
    expect(
        document.querySelectorAll('[data-slot="separator-root"]'),
    ).toHaveLength(2);
});

test('a typed amount asks for the list after a pause of 400 ms, not on each key', () => {
    vi.useFakeTimers();

    try {
        render(
            <ColumnMenu
                header="Amount"
                menu={{
                    sortKey: 'amount',
                    range: {
                        fromParam: 'amount_from',
                        toParam: 'amount_to',
                        type: 'number',
                    },
                }}
            />,
        );
        open('Amount');

        const from = screen.getByLabelText('From');

        fireEvent.change(from, { target: { value: '1' } });
        act(() => {
            vi.advanceTimersByTime(300);
        });
        fireEvent.change(from, { target: { value: '12' } });
        act(() => {
            vi.advanceTimersByTime(399);
        });

        expect(state.get).not.toHaveBeenCalled();

        act(() => {
            vi.advanceTimersByTime(1);
        });

        expect(state.get).toHaveBeenCalledTimes(1);
        expect(visited().searchParams.get('amount_from')).toBe('12');
    } finally {
        vi.useRealTimers();
    }
});

test('Enter asks for a typed amount at once', () => {
    vi.useFakeTimers();

    try {
        render(
            <ColumnMenu
                header="Amount"
                menu={{
                    sortKey: 'amount',
                    range: {
                        fromParam: 'amount_from',
                        toParam: 'amount_to',
                        type: 'number',
                    },
                }}
            />,
        );
        open('Amount');

        const to = screen.getByLabelText('To');

        fireEvent.change(to, { target: { value: '90' } });
        fireEvent.keyDown(to, { key: 'Enter' });

        expect(visited().searchParams.get('amount_to')).toBe('90');
    } finally {
        vi.useRealTimers();
    }
});
