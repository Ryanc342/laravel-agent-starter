import type { ReactNode } from 'react';
import ColumnMenu, {
    type ColumnMenuConfig,
} from '@/components/shared/column-menu';
import RegionState, {
    type RegionError,
} from '@/components/shared/region-state';
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
                        <div className="min-w-0 font-medium break-words">
                            {title.cell(row)}
                        </div>
                        <dl className="grid gap-2 text-sm">
                            {others.map((column) =>
                                column.hideLabelOnCard ? (
                                    <div key={column.header}>
                                        {column.cell(row)}
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
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {showState && (
                        <TableRow>
                            <TableCell colSpan={columns.length} className="p-0">
                                {state}
                            </TableCell>
                        </TableRow>
                    )}
                    {!showState &&
                        rows.map((row) => (
                            <TableRow key={getRowKey(row)}>
                                {columns.map((column) => (
                                    <TableCell
                                        key={column.header}
                                        className={column.className}
                                    >
                                        {column.cell(row)}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))}
                </TableBody>
            </Table>
        </div>
    );
}
