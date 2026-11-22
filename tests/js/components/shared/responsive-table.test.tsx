import { fireEvent, render, screen, within } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import ResponsiveTable, {
    type TableColumn,
    type TableSelection,
} from '@/components/shared/responsive-table';
import { TooltipProvider } from '@/components/ui/tooltip';
import { useIsMobile } from '@/hooks/use-mobile';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: vi.fn() }));
vi.mock('@inertiajs/react', () => ({
    router: { get: vi.fn() },
    usePage: () => ({ url: '/tasks' }),
}));

afterEach(() => {
    vi.mocked(useIsMobile).mockReset();
});

type Row = { id: number; title: string; status: string; due: string };

const rows: Row[] = [
    { id: 1, title: 'Buy milk', status: 'Open', due: '2026-11-04' },
    { id: 2, title: 'Send quote', status: 'Done', due: '2026-11-09' },
];

const columns: TableColumn<Row>[] = [
    { header: 'Title', cell: (row) => row.title },
    { header: 'Status', cell: (row) => row.status, hideLabelOnCard: true },
    { header: 'Due date', cell: (row) => row.due },
];

function table(records: Row[] = rows) {
    return (
        <ResponsiveTable
            columns={columns}
            rows={records}
            getRowKey={(row) => row.id}
            emptyMessage="You have no tasks yet."
        />
    );
}

test('on desktop the records are a table with a header row and one row each', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(table());

    expect(
        screen.getAllByRole('columnheader').map((cell) => cell.textContent),
    ).toEqual(['Title', 'Status', 'Due date']);
    expect(screen.getAllByRole('row')).toHaveLength(rows.length + 1);
    expect(screen.queryAllByRole('listitem')).toHaveLength(0);
    expect(screen.getByText('Buy milk')).toBeTruthy();
});

test('on a phone each record is one card and there is no table', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);

    render(table());

    expect(screen.queryByRole('table')).toBeNull();
    expect(screen.getAllByRole('listitem')).toHaveLength(rows.length);
});

test('a card shows the name first, the status next, then the other columns with their label', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);

    render(table());
    const card = screen.getAllByRole('listitem')[0];

    const text = card.textContent ?? '';
    expect(text.indexOf('Buy milk')).toBeLessThan(text.indexOf('Open'));
    expect(text.indexOf('Open')).toBeLessThan(text.indexOf('Due date'));
    expect(within(card).getByText('Due date')).toBeTruthy();
    expect(within(card).getByText('2026-11-04')).toBeTruthy();
});

test('a status shown without its label on a card keeps the label for screen readers only', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);

    render(table());
    const card = screen.getAllByRole('listitem')[0];

    expect(within(card).getByText('Status').tagName).toBe('DT');
    expect(within(card).getByText('Status').className).toBe('sr-only');
    expect(within(card).getByText('Open')).toBeTruthy();
});

test('on desktop an empty table keeps its header row and the empty line sits inside it', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(table([]));
    const grid = screen.getByRole('table');

    expect(
        screen.getAllByRole('columnheader').map((cell) => cell.textContent),
    ).toEqual(['Title', 'Status', 'Due date']);
    expect(within(grid).getByText('You have no tasks yet.')).toBeTruthy();
    expect(screen.getAllByRole('cell')).toHaveLength(1);
    expect(screen.getAllByRole('cell')[0].getAttribute('colspan')).toBe('3');
});

test('on a phone an empty list is still a box with the empty line inside', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);

    const { container } = render(table([]));
    const box = container.firstElementChild as HTMLElement;

    expect(within(box).getByText('You have no tasks yet.')).toBeTruthy();
    expect(screen.queryAllByRole('listitem')).toHaveLength(0);
});

test('a table with records shows no empty line', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(table());

    expect(screen.queryByText('You have no tasks yet.')).toBeNull();
});

test('on desktop a loading table keeps its header row and shows the skeleton inside it', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(
        <ResponsiveTable
            columns={columns}
            rows={[]}
            getRowKey={(row) => row.id}
            emptyMessage="You have no tasks yet."
            loading
        />,
    );
    const grid = screen.getByRole('table');

    expect(
        screen.getAllByRole('columnheader').map((cell) => cell.textContent),
    ).toEqual(['Title', 'Status', 'Due date']);
    expect(within(grid).getByRole('status')).toBeTruthy();
    expect(within(grid).queryByText('You have no tasks yet.')).toBeNull();
});

test('on desktop a failed table keeps its header row and shows the centred failure inside it', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    const retry = vi.fn();

    render(
        <ResponsiveTable
            columns={columns}
            rows={[]}
            getRowKey={(row) => row.id}
            emptyMessage="You have no tasks yet."
            error={{ message: 'Could not load your tasks.', onRetry: retry }}
        />,
    );
    const grid = screen.getByRole('table');

    expect(
        screen.getAllByRole('columnheader').map((cell) => cell.textContent),
    ).toEqual(['Title', 'Status', 'Due date']);
    expect(within(grid).getByRole('alert').textContent).toContain(
        'Could not load your tasks.',
    );
    expect(within(grid).queryByText('You have no tasks yet.')).toBeNull();
});

test('on a phone a loading or failed list is a box with the state inside', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);

    const { container, rerender } = render(
        <ResponsiveTable
            columns={columns}
            rows={[]}
            getRowKey={(row) => row.id}
            emptyMessage="You have no tasks yet."
            loading
        />,
    );
    const box = container.firstElementChild as HTMLElement;

    expect(within(box).getByRole('status')).toBeTruthy();

    rerender(
        <ResponsiveTable
            columns={columns}
            rows={[]}
            getRowKey={(row) => row.id}
            emptyMessage="You have no tasks yet."
            error={{ message: 'Could not load your tasks.' }}
        />,
    );

    expect(within(container).getByRole('alert')).toBeTruthy();
    expect(
        container.querySelectorAll('[data-slot="loading-card"]'),
    ).toHaveLength(0);
});

test('a column with a menu shows its header as the menu button, one without stays plain text', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(
        <ResponsiveTable
            columns={[
                {
                    header: 'Title',
                    cell: (row: Row) => row.title,
                    menu: { sortKey: 'title' },
                },
                { header: 'Status', cell: (row: Row) => row.status },
            ]}
            rows={rows}
            getRowKey={(row) => row.id}
            emptyMessage="You have no tasks yet."
        />,
    );

    expect(
        within(screen.getAllByRole('columnheader')[0]).getByRole('button', {
            name: 'Title',
        }),
    ).toBeTruthy();
    expect(
        within(screen.getAllByRole('columnheader')[1]).queryByRole('button'),
    ).toBeNull();
});

function selectableTable(
    selection: Partial<TableSelection<Row>> = {},
    records: Row[] = rows,
) {
    const full: TableSelection<Row> = {
        pageState: 'none',
        onTogglePage: vi.fn(),
        isSelected: () => false,
        onToggle: vi.fn(),
        rowLabel: (row) => `Select ${row.title}`,
        ...selection,
    };

    render(
        <ResponsiveTable
            columns={columns}
            rows={records}
            getRowKey={(row) => row.id}
            emptyMessage="You have no tasks yet."
            selection={full}
        />,
    );

    return full;
}

test('with a selection the table has a checkbox column first and a checkbox in each row', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    selectableTable();

    const header = screen.getAllByRole('columnheader')[0];

    expect(
        within(header).getByRole('checkbox', {
            name: 'Select all rows on this page',
        }),
    ).toBeTruthy();
    expect(screen.getAllByRole('checkbox')).toHaveLength(rows.length + 1);
    expect(
        screen.getByRole('checkbox', { name: 'Select Buy milk' }),
    ).toBeTruthy();
});

test('a table without a selection has no checkbox', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    render(table());

    expect(screen.queryAllByRole('checkbox')).toHaveLength(0);
});

test('ticking a row and the header checkbox ask the owner of the selection', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    const selection = selectableTable();

    fireEvent.click(screen.getByRole('checkbox', { name: 'Select Buy milk' }));
    expect(selection.onToggle).toHaveBeenCalledWith(rows[0]);

    fireEvent.click(
        screen.getByRole('checkbox', { name: 'Select all rows on this page' }),
    );
    expect(selection.onTogglePage).toHaveBeenCalledTimes(1);
});

test('the header checkbox is ticked for a whole page and half ticked for some rows', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    const state = (pageState: TableSelection<Row>['pageState']) => {
        selectableTable({ pageState });
        const box = screen.getByRole('checkbox', {
            name: 'Select all rows on this page',
        });
        const value = box.getAttribute('data-state');

        document.body.innerHTML = '';

        return value;
    };

    expect(state('none')).toBe('unchecked');
    expect(state('some')).toBe('indeterminate');
    expect(state('all')).toBe('checked');
});

test('a ticked row is marked as selected', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    selectableTable({ isSelected: (row) => row.id === 2 });

    const dataRows = screen.getAllByRole('row').slice(1);

    expect(dataRows[0].getAttribute('data-state')).toBeNull();
    expect(dataRows[1].getAttribute('data-state')).toBe('selected');
});

test('on a phone each card has its own checkbox before the name', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    const selection = selectableTable();

    const card = screen.getAllByRole('listitem')[0];
    const box = within(card).getByRole('checkbox', { name: 'Select Buy milk' });

    expect(
        box.compareDocumentPosition(within(card).getByText('Buy milk')) &
            Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
    expect(screen.queryByRole('table')).toBeNull();

    fireEvent.click(box);
    expect(selection.onToggle).toHaveBeenCalledWith(rows[0]);
});

test('an empty table with a selection still spans every column, the checkbox one too', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    selectableTable({}, []);

    expect(screen.getAllByRole('cell')[0].getAttribute('colspan')).toBe('4');
});

function tableWithActions() {
    render(
        <TooltipProvider>
            <ResponsiveTable
                columns={columns}
                rows={rows}
                getRowKey={(row) => row.id}
                emptyMessage="You have no tasks yet."
                rowActions={(row) => [
                    { label: `Edit ${row.title}`, onSelect: vi.fn() },
                ]}
            />
        </TooltipProvider>,
    );
}

test("on desktop a row's actions are one menu button in the last column", () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    tableWithActions();

    expect(screen.getAllByRole('columnheader')).toHaveLength(
        columns.length + 1,
    );
    expect(screen.getAllByRole('columnheader').at(-1)?.textContent).toBe(
        'Actions',
    );

    for (const row of screen.getAllByRole('row').slice(1)) {
        const cells = within(row).getAllByRole('cell');

        expect(cells).toHaveLength(columns.length + 1);
        expect(within(row).getAllByRole('button')).toHaveLength(1);
        expect(
            within(cells[cells.length - 1]).getByRole('button', {
                name: 'More actions',
            }),
        ).toBeTruthy();
    }
});

test("on a phone the menu button sits in the card's top row, after the name", () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    tableWithActions();

    const card = screen.getAllByRole('listitem')[0];
    const button = within(card).getByRole('button', { name: 'More actions' });

    expect(within(card).getAllByRole('button')).toHaveLength(1);
    expect(button.closest('[data-slot="card-top"]')).not.toBeNull();
    expect(button.closest('[data-slot="card-top"]')).toBe(
        within(card).getByText('Buy milk').closest('[data-slot="card-top"]'),
    );
    expect(
        within(card).getByText('Buy milk').compareDocumentPosition(button) &
            Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
});

test('an empty table with row actions still spans every column', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    render(
        <ResponsiveTable
            columns={columns}
            rows={[]}
            getRowKey={(row) => row.id}
            emptyMessage="You have no tasks yet."
            rowActions={() => []}
        />,
    );

    expect(screen.getAllByRole('cell')[0].getAttribute('colspan')).toBe('4');
});

test.each([
    ['on desktop', false],
    ['on a phone', true],
])("%s each row's menu holds that row's actions", async (_, mobile) => {
    vi.mocked(useIsMobile).mockReturnValue(mobile);
    tableWithActions();

    fireEvent.pointerDown(
        screen.getAllByRole('button', { name: 'More actions' })[1],
        { button: 0, ctrlKey: false },
    );

    expect(
        (await screen.findAllByRole('menuitem')).map(
            (item) => item.textContent,
        ),
    ).toEqual(['Edit Send quote']);
});
