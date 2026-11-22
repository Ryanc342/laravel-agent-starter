import DetailList from '@/components/shared/detail-list';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import Actions from '@/components/shared/actions';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatMoney } from '@/lib/format';

export type ExampleInvoice = {
    number: string;
    date: string;
    seller: string;
    buyer: string;
    lines: {
        id: number;
        description: string;
        quantity: number;
        unit: number;
        /** The line total in cents, from the backend. */
        total: number;
    }[];
    /** All in cents, from the backend. */
    subtotal: number;
    tax: number;
    total: number;
};

/**
 * A sheet that is printed: the bar above it (hidden when printing), then the
 * sheet with the seller and buyer, the lines as a plain table and the totals
 * on the right. Every figure comes from the backend. In an app the page comes
 * from the record part (Route::record()), and Print is the browser's print.
 */
export default function PrintDemo({ invoice }: { invoice: ExampleInvoice }) {
    const inform = () => toast('On a real print page this prints.');

    return (
        <div className="grid gap-6">
            <Actions data-testid="print-bar" className="print:hidden">
                <Button onClick={inform}>Print</Button>
            </Actions>

            <div className="grid max-w-3xl gap-6 rounded-lg border p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title={`Invoice ${invoice.number}`} />
                    <p className="text-sm text-muted-foreground">
                        {formatDate(invoice.date)}
                    </p>
                </div>

                <DetailList
                    columns={2}
                    details={[
                        { label: 'From', value: invoice.seller },
                        { label: 'To', value: invoice.buyer },
                    ]}
                />

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Description</TableHead>
                            <TableHead className="text-right">Qty</TableHead>
                            <TableHead className="text-right">Price</TableHead>
                            <TableHead className="text-right">Total</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {invoice.lines.map((line) => (
                            <TableRow key={line.id}>
                                <TableCell className="whitespace-normal">
                                    {line.description}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {line.quantity}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatMoney(line.unit)}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatMoney(line.total)}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                <dl className="ml-auto grid w-full max-w-xs gap-2 text-sm">
                    {[
                        ['Subtotal', invoice.subtotal],
                        ['Tax', invoice.tax],
                        ['Total', invoice.total],
                    ].map(([label, cents]) => (
                        <div
                            key={label}
                            className="flex items-baseline justify-between gap-4"
                        >
                            <dt className="text-muted-foreground">{label}</dt>
                            <dd className="font-medium tabular-nums">
                                {formatMoney(Number(cents))}
                            </dd>
                        </div>
                    ))}
                </dl>
            </div>
        </div>
    );
}
