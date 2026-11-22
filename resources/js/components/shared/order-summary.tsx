import { formatMoney } from '@/lib/format';

type SummaryRow = { label: string; amount: number };

/**
 * The totals of an order: the backend's
 * subtotal, tax and the like as a `dl`, and the total last, bold. Amounts are
 * minor units and go through `formatMoney`; nothing is added up here.
 */
export default function OrderSummary({
    rows,
    total,
    currency,
}: {
    rows: SummaryRow[];
    total: SummaryRow;
    currency: string;
}) {
    return (
        <dl className="grid gap-3 text-sm">
            {rows.map((row) => (
                <div key={row.label} className="flex justify-between gap-4">
                    <dt className="text-muted-foreground">{row.label}</dt>
                    <dd className="tabular-nums">
                        {formatMoney(row.amount, currency)}
                    </dd>
                </div>
            ))}
            <div className="flex justify-between gap-4 border-t pt-3 font-semibold">
                <dt>{total.label}</dt>
                <dd className="tabular-nums">
                    {formatMoney(total.amount, currency)}
                </dd>
            </div>
        </dl>
    );
}
