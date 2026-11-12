import type { ReactNode } from 'react';
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
};

type Props<Row> = {
    /** The first column is the record's name or number: the card's title on a phone. */
    columns: TableColumn<Row>[];
    rows: Row[];
    getRowKey: (row: Row) => string | number;
};

/**
 * A list of records: a table on desktop (horizontal row lines only), one card
 * per record on a phone, so the page never scrolls sideways.
 */
export default function ResponsiveTable<Row>({
    columns,
    rows,
    getRowKey,
}: Props<Row>) {
    const isMobile = useIsMobile();

    if (isMobile) {
        const [title, ...others] = columns;

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
                                {column.header}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.map((row) => (
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
