import { toast } from 'sonner';
import Heading from '@/components/heading';
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
 * A sheet that is printed or saved as a PDF: the bar above it (hidden when
 * printing), then the sheet with the seller and buyer, the lines as a plain
 * table and the totals on the right. Every figure comes from the backend. The
 * real print page is the task's (`tasks/print`); this shows the invoice layout.
 */
export default function PrintDemo({ invoice }: { invoice: ExampleInvoice }) {
    const inform = () =>
        toast('On a real print page this prints, or downloads the PDF.');

    return (
        <div className="grid gap-6">
            <div
                data-testid="print-bar"
                className="flex flex-wrap justify-end gap-2 print:hidden"
            >
                <Button variant="outline" onClick={inform}>
                    Download PDF
                </Button>
                <Button onClick={inform}>Print</Button>
            </div>

            <div className="grid max-w-3xl gap-6 rounded-lg border p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title={`Invoice ${invoice.number}`} />
                    <p className="text-sm text-muted-foreground">
                        {formatDate(invoice.date)}
                    </p>
                </div>

                <dl className="grid gap-4 text-sm sm:grid-cols-2">
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">From</dt>
                        <dd>{invoice.seller}</dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">To</dt>
                        <dd>{invoice.buyer}</dd>
                    </div>
                </dl>

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
