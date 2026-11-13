import { Trash2 } from 'lucide-react';
import { useId } from 'react';
import FormField from '@/components/shared/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
 * The items of a cart (docs/design/patterns/checkout.md): name and unit price,
 * a quantity box and the line total, one row per item, and an icon button to
 * remove it. The quantity inputs are named `items[0][quantity]` (`field` is
 * the array's name), so Laravel's `items.0.quantity` error finds its field
 * (`useValidationErrorToasts`); an error here is keyed the way Laravel sends
 * it. The input ids are unique to each list, so two lists on a page do not
 * share labels. The page sends a changed
 * quantity to the server, which answers with new totals: nothing is multiplied
 * here. On a phone each row stacks.
 */
export default function LineItems({
    items,
    currency,
    errors = {},
    field = 'items',
    labels = { quantityOf: 'Quantity of', remove: 'Remove' },
    onQuantityChange,
    onRemove,
}: {
    items: LineItem[];
    currency: string;
    errors?: Record<string, string>;
    /** The name of the array the inputs belong to, as the FormRequest calls it. */
    field?: string;
    labels?: { quantityOf: string; remove: string };
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
                    <FormField
                        hideLabel
                        label={`${labels.quantityOf} ${item.name}`}
                        htmlFor={`${uid}-${index}-quantity`}
                        error={errors[`${field}.${index}.quantity`]}
                        className="md:w-24"
                    >
                        <Input
                            id={`${uid}-${index}-quantity`}
                            name={`${field}[${index}][quantity]`}
                            type="number"
                            inputMode="numeric"
                            min={1}
                            value={item.quantity}
                            onChange={(event) =>
                                onQuantityChange(item.id, event.target.value)
                            }
                        />
                    </FormField>
                    <div className="flex items-center justify-between gap-4 md:contents">
                        <p className="text-sm font-medium tabular-nums md:w-28 md:text-right">
                            {formatMoney(item.total, currency)}
                        </p>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`${labels.remove} ${item.name}`}
                                    onClick={() => onRemove(item.id)}
                                >
                                    <Trash2 />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>{labels.remove}</TooltipContent>
                        </Tooltip>
                    </div>
                </li>
            ))}
        </ul>
    );
}
