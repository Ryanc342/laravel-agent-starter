import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import FormField from '@/components/shared/form-field';
import LineItems, { type LineItem } from '@/components/shared/line-items';
import OrderSummary from '@/components/shared/order-summary';
import type {
    PublicHeaderLabels,
    PublicLink,
} from '@/components/shared/public-header';
import WizardLayout from '@/components/shared/wizard-layout';
import PublicFrame from '@/components/style/public-frame';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Summary = {
    rows: { label: string; amount: number }[];
    total: { label: string; amount: number };
};

export type PricedCart = Summary & { items: LineItem[] };

/**
 * Order / checkout (docs/design/patterns/checkout.md): its own page with the
 * steps on the left (Cart, Details, Review), the order summary on the right on
 * a computer and above the buttons on a phone. A real page sends a changed
 * quantity and each step to the server; here `price` and `answer` stand in
 * for its answers (new totals, and the errors keyed the way Laravel sends
 * them, `items.0.quantity`). A failed step fires the event Inertia fires, so
 * the real toasts and the focus on `items[0][quantity]` show. There is no card
 * field: payment is the provider's own element.
 */
export default function CheckoutDemo({
    links,
    barLabels,
    title,
    steps,
    currency,
    items: initialItems,
    field,
    details,
    price,
    answer,
    paymentNote,
    placeLabel,
    placed,
    nextLabel,
    backLabel,
    lineLabels,
}: {
    links: PublicLink[];
    barLabels?: PublicHeaderLabels;
    title: string;
    steps: [string, string, string];
    currency: string;
    items: LineItem[];
    /** The array's name in the form: `items`, so an error is `items.0.quantity`. */
    field: string;
    details: { id: string; label: string }[];
    price: (items: LineItem[]) => PricedCart;
    answer: (
        step: number,
        values: Record<string, string>,
        items: LineItem[],
    ) => Record<string, string>;
    paymentNote: string;
    placeLabel: string;
    placed: string;
    nextLabel: string;
    backLabel: string;
    lineLabels: { quantityOf: string; remove: string };
}) {
    const [cart, setCart] = useState<PricedCart>(() => price(initialItems));
    const [values, setValues] = useState<Record<string, string>>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [current, setCurrent] = useState(0);
    const last = current === steps.length - 1;

    const changeQuantity = (id: LineItem['id'], quantity: string) =>
        setCart(
            price(
                cart.items.map((item) =>
                    item.id === id ? { ...item, quantity } : item,
                ),
            ),
        );

    const next = () => {
        const found = answer(current, values, cart.items);

        setErrors(found);

        if (Object.keys(found).length > 0) {
            document.dispatchEvent(
                new CustomEvent('inertia:error', {
                    detail: { errors: found },
                }),
            );

            return;
        }

        if (last) {
            toast.success(placed);
            setCurrent(0);
            setValues({});
            setCart(price(initialItems));

            return;
        }

        setCurrent(current + 1);
    };

    return (
        <PublicFrame links={links} barLabels={barLabels}>
            <div className="grid gap-6">
                <Heading title={title} />
                <WizardLayout
                    steps={steps}
                    current={current}
                    footer={
                        <>
                            <Button onClick={next}>
                                {last ? placeLabel : nextLabel}
                            </Button>
                            {current > 0 && (
                                <Button
                                    variant="outline"
                                    onClick={() => {
                                        setErrors({});
                                        setCurrent(current - 1);
                                    }}
                                >
                                    {backLabel}
                                </Button>
                            )}
                        </>
                    }
                >
                    <div className="grid gap-8 lg:grid-cols-3">
                        <div className="grid min-w-0 content-start gap-6 lg:col-span-2">
                            {current === 0 && (
                                <LineItems
                                    items={cart.items}
                                    currency={currency}
                                    errors={errors}
                                    field={field}
                                    labels={lineLabels}
                                    onQuantityChange={changeQuantity}
                                    onRemove={(id) =>
                                        setCart(
                                            price(
                                                cart.items.filter(
                                                    (item) => item.id !== id,
                                                ),
                                            ),
                                        )
                                    }
                                />
                            )}
                            {current === 1 &&
                                details.map((field) => (
                                    <FormField
                                        key={field.id}
                                        label={field.label}
                                        htmlFor={`checkout-${field.id}`}
                                        error={errors[field.id]}
                                    >
                                        <Input
                                            id={`checkout-${field.id}`}
                                            name={field.id}
                                            value={values[field.id] ?? ''}
                                            onChange={(event) =>
                                                setValues({
                                                    ...values,
                                                    [field.id]:
                                                        event.target.value,
                                                })
                                            }
                                        />
                                    </FormField>
                                ))}
                            {current === 2 && (
                                <div className="grid gap-6">
                                    <dl className="grid gap-3 text-sm">
                                        {details.map((field) => (
                                            <div
                                                key={field.id}
                                                className="flex justify-between gap-4"
                                            >
                                                <dt className="text-muted-foreground">
                                                    {field.label}
                                                </dt>
                                                <dd className="min-w-0 text-right break-words">
                                                    {values[field.id]}
                                                </dd>
                                            </div>
                                        ))}
                                    </dl>
                                    <p className="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
                                        {paymentNote}
                                    </p>
                                </div>
                            )}
                        </div>
                        <aside className="min-w-0 content-start">
                            <OrderSummary
                                rows={cart.rows}
                                total={cart.total}
                                currency={currency}
                            />
                        </aside>
                    </div>
                </WizardLayout>
            </div>
        </PublicFrame>
    );
}
