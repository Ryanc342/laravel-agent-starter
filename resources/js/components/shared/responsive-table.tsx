import type { ReactNode } from 'react';
import ColumnMenu, {
    type ColumnMenuConfig,
} from '@/components/shared/column-menu';
import RowActions, { type RowAction } from '@/components/shared/row-actions';
import RegionState, {
    type RegionError,
} from '@/components/shared/region-state';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useIsMobile } from '@/hooks/use-mobile';

export type TableColumn<Row> = {
    header: string;
    cell: (row: Row) => ReactNode;
    /** On a phone the card shows this value without its label: a status badge says what it is. */
    hideLabelOnCard?: boolean;
    /** Classes for this column's cells on desktop, such as a maximum width with `truncate`. */
    className?: string;
    /** Makes the header a button that opens this column's sort, search, tick list or range. */
    menu?: ColumnMenuConfig;
};

/**
 * Ticking rows: a checkbox column first on desktop,
 * a checkbox on each card on a phone. The header checkbox ticks the rows of
 * this page; `useRowSelection` (hooks) holds the state.
 */
export type TableSelection<Row> = {
    pageState: 'none' | 'some' | 'all';
    onTogglePage: () => void;
    isSelected: (row: Row) => boolean;
    onToggle: (row: Row) => void;
    /** Names a row's checkbox for screen readers ("Select Buy milk"). */
    rowLabel: (row: Row) => string;
};

type Props<Row> = {
    /** The first column is the record's name or number: the card's title on a phone. */
    columns: TableColumn<Row>[];
    rows: Row[];
    getRowKey: (row: Row) => string | number;
    /** The line shown inside the table (its header row stays) or the box when there are no rows. */
    emptyMessage: string;
    /** The data is still arriving: the skeleton sits inside the frame, under the header row. */
    loading?: boolean;
    /** The data could not load: the failure sits inside the frame, under the header row. */
    error?: RegionError;
    selection?: TableSelection<Row>;
    /**
     * A list whose records have no detail page: each row's
     * own actions, in one three-dot menu in the last column (on a phone, at the
     * top right of the card).
     */
    rowActions?: (row: Row) => RowAction[];
};

/**
 * A list of records: a table on desktop (horizontal row lines only), one card
 * per record on a phone, so the page never scrolls sideways. Empty, loading and
 * failed all keep the header row (on a phone the box) and draw the state inside
 * it, centred.
 */
export default function ResponsiveTable<Row>({
    columns,
    rows,
    getRowKey,
    emptyMessage,
    loading = false,
    error,
    selection,
    rowActions,
}: Props<Row>) {
    const isMobile = useIsMobile();
    const showState = loading || error !== undefined || rows.length === 0;
    const state = (
        <RegionState
            emptyMessage={emptyMessage}
            loading={loading}
            error={error}
        />
    );

    if (isMobile) {
        const [title, ...others] = columns;

        if (showState) {
            return <div className="rounded-lg border">{state}</div>;
        }

        return (
            <ul className="grid gap-3">
                {rows.map((row) => (
                    <li
                        key={getRowKey(row)}
                        className="grid gap-3 rounded-lg border p-4"
                    >
                        <div
                            data-slot="card-top"
                            className="flex min-w-0 items-start gap-3"
                        >
                            {selection && (
                                <Checkbox
                                    aria-label={selection.rowLabel(row)}
                                    checked={selection.isSelected(row)}
                                    onCheckedChange={() =>
                                        selection.onToggle(row)
                                    }
                                />
                            )}
                            <div className="min-w-0 flex-1 font-medium break-words">
                                {title.cell(row)}
                            </div>
                            {rowActions && (
                                <RowActions actions={rowActions(row)} />
                            )}
                        </div>
                        <dl className="grid gap-2 text-sm">
                            {others.map((column) =>
                                column.hideLabelOnCard ? (
                                    <div key={column.header}>
                                        <dt className="sr-only">
                                            {column.header}
                                        </dt>
                                        <dd>{column.cell(row)}</dd>
                                    </div>
                                ) : (
                                    <div
                                        key={column.header}
                                        className="flex items-baseline justify-between gap-4"
                                    >
                                        <dt className="text-muted-foreground">
                                            {column.header}
                                        </dt>
                                        <dd className="min-w-0 text-right break-words">
                                            {column.cell(row)}
                                        </dd>
                                    </div>
                                ),
                            )}
                        </dl>
                    </li>
                ))}
            </ul>
        );
    }

    return (
        <div className="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        {selection && (
                            <TableHead className="w-10">
                                <Checkbox
                                    aria-label="Select all rows on this page"
                                    checked={
                                        selection.pageState === 'all'
                                            ? true
                                            : selection.pageState === 'some'
                                              ? 'indeterminate'
                                              : false
                                    }
                                    onCheckedChange={selection.onTogglePage}
                                />
                            </TableHead>
                        )}
                        {columns.map((column) => (
                            <TableHead key={column.header}>
                                {column.menu ? (
                                    <ColumnMenu
                                        header={column.header}
                                        menu={column.menu}
                                    />
                                ) : (
                                    column.header
                                )}
                            </TableHead>
                        ))}
                        {rowActions && (
                            <TableHead className="w-12">
                                <span className="sr-only">Actions</span>
                            </TableHead>
                        )}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {showState && (
                        <TableRow>
                            <TableCell
                                colSpan={
                                    columns.length +
                                    (selection ? 1 : 0) +
                                    (rowActions ? 1 : 0)
                                }
                                className="p-0"
                            >
                                {state}
                            </TableCell>
                        </TableRow>
                    )}
                    {!showState &&
                        rows.map((row) => (
                            <TableRow
                                key={getRowKey(row)}
                                data-state={
                                    selection?.isSelected(row)
                                        ? 'selected'
                                        : undefined
                                }
                            >
                                {selection && (
                                    <TableCell className="w-10">
                                        <Checkbox
                                            aria-label={selection.rowLabel(row)}
                                            checked={selection.isSelected(row)}
                                            onCheckedChange={() =>
                                                selection.onToggle(row)
                                            }
                                        />
                                    </TableCell>
                                )}
                                {columns.map((column) => (
                                    <TableCell
                                        key={column.header}
                                        className={column.className}
                                    >
                                        {column.cell(row)}
                                    </TableCell>
                                ))}
                                {rowActions && (
                                    <TableCell className="w-12 text-right">
                                        <RowActions actions={rowActions(row)} />
                                    </TableCell>
                                )}
                            </TableRow>
                        ))}
                </TableBody>
            </Table>
        </div>
    );
}
