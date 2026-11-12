import { render, screen, within } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import ResponsiveTable, {
    type TableColumn,
} from '@/components/shared/responsive-table';
import { useIsMobile } from '@/hooks/use-mobile';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: vi.fn() }));

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

function table() {
    return (
        <ResponsiveTable
            columns={columns}
            rows={rows}
            getRowKey={(row) => row.id}
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

test('a status shown without its label on a card has no label there', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);

    render(table());
    const card = screen.getAllByRole('listitem')[0];

    expect(within(card).queryByText('Status')).toBeNull();
    expect(within(card).getByText('Open')).toBeTruthy();
});
