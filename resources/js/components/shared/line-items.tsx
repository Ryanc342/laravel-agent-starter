import { Trash2 } from 'lucide-react';
import { useId } from 'react';
import IconButton from '@/components/shared/icon-button';
import TextField from '@/components/shared/text-field';
import { formatMoney } from '@/lib/format';

export type LineItem = {
    id: number | string;
    name: string;
    /** Unit price and line total in minor units, as the backend sends them. */
    unit: number;
    total: number;
    /** What is typed in the quantity box. */
    quantity: string;
};

/**
 * The items of a cart: name and unit price, a quantity box and the line
 * total, one row per item, and a button to remove it. Use it wherever the user
 * changes the quantities of a list of items. The quantity inputs are named
 * `items[0][quantity]` (`field` is the array's name), so Laravel's
 * `items.0.quantity` error finds its field. The page sends a changed quantity
 * to the server, which answers with new totals: nothing is multiplied here. On
 * a phone each row stacks.
 */
export default function LineItems({
    items,
    currency,
    errors = {},
    field = 'items',
    onQuantityChange,
    onRemove,
}: {
    items: LineItem[];
    currency: string;
    errors?: Record<string, string>;
    /** The name of the array the inputs belong to, as the FormRequest calls it. */
    field?: string;
    onQuantityChange: (id: LineItem['id'], quantity: string) => void;
    onRemove: (id: LineItem['id']) => void;
}) {
    const uid = useId();

    return (
        <ul className="divide-y rounded-lg border">
            {items.map((item, index) => (
                <li
                    key={item.id}
                    className="flex flex-col gap-3 p-4 md:flex-row md:items-center md:gap-4"
                >
                    <div className="min-w-0 flex-1">
                        <p className="text-sm font-medium break-words">
                            {item.name}
                        </p>
                        <p className="text-sm text-muted-foreground tabular-nums">
                            {formatMoney(item.unit, currency)}
                        </p>
                    </div>
                    <TextField
                        label="Quantity"
                        aria-label={`Quantity of ${item.name}`}
                        id={`${uid}-${index}-quantity`}
                        name={`${field}[${index}][quantity]`}
                        type="number"
                        inputMode="numeric"
                        min={1}
                        value={item.quantity}
                        onChange={(event) =>
                            onQuantityChange(item.id, event.target.value)
                        }
                        error={errors[`${field}.${index}.quantity`]}
                        className="md:w-28"
                    />
                    <div className="flex items-center justify-between gap-4 md:contents">
                        <p className="text-sm font-medium tabular-nums md:w-28 md:text-right">
                            {formatMoney(item.total, currency)}
                        </p>
                        <IconButton
                            label={`Remove ${item.name}`}
                            onClick={() => onRemove(item.id)}
                        >
                            <Trash2 />
                        </IconButton>
                    </div>
                </li>
            ))}
        </ul>
    );
}
