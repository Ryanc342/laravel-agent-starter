import Heading from '@/components/heading';
import DetailList from '@/components/shared/detail-list';
import LineItems, { type LineItem } from '@/components/shared/line-items';
import OrderSummary from '@/components/shared/order-summary';
import type { PublicLink } from '@/components/shared/public-header';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import WizardLayout from '@/components/shared/wizard-layout';
import PublicFrame from '@/components/style/public-frame';
import { DemoState } from '@/components/style/style-section';
import { Button } from '@/components/ui/button';

type Summary = {
    rows: { label: string; amount: number }[];
    total: { label: string; amount: number };
};

/** A cart as the server priced it: each line's total, the subtotal, the tax and the total. */
export type PricedCart = Summary & { items: LineItem[] };

type Field = { id: string; label: string };

/**
 * Checkout: its own page with the steps on the left (Cart, Details, Review)
 * and the order summary beside them on a computer, above the buttons on a
 * phone. It shows three fixed states: the cart, the details as the server sent
 * them back with errors, and the review. Every amount and error is in the demo
 * data. There is no card field: payment is the provider's own element.
 */
export default function CheckoutDemo({
    links,
    title,
    steps,
    currency,
    cart,
    field,
    details,
    typed,
    detailErrors,
    filled,
    paymentNote,
}: {
    links: PublicLink[];
    title: string;
    steps: [string, string, string];
    currency: string;
    cart: PricedCart;
    /** The array's name in the form: `items`, so an error is `items.0.quantity`. */
    field: string;
    details: Field[];
    /** What was typed in Details before the server answered with `detailErrors`. */
    typed: Record<string, string>;
    detailErrors: Record<string, string>;
    /** The details as they stand at Review. */
    filled: Record<string, string>;
    paymentNote: string;
}) {
    const step = (current: number, content: React.ReactNode) => (
        <WizardLayout
            steps={steps}
            current={current}
            footer={
                <>
                    {current > 0 && <Button variant="outline">Back</Button>}
                    {current < steps.length - 1 ? (
                        <Button>Next</Button>
                    ) : (
                        <SubmitButton sending={false}>Pay</SubmitButton>
                    )}
                </>
            }
        >
            <div className="grid gap-8 lg:grid-cols-3">
                <div className="grid min-w-0 content-start gap-6 lg:col-span-2">
                    {content}
                </div>
                <div className="min-w-0 content-start">
                    <OrderSummary
                        rows={cart.rows}
                        total={cart.total}
                        currency={currency}
                    />
                </div>
            </div>
        </WizardLayout>
    );

    return (
        <PublicFrame name="Checkout" links={links}>
            <div className="grid gap-10">
                <Heading title={title} />
                <DemoState label="The cart">
                    {step(
                        0,
                        <LineItems
                            items={cart.items}
                            currency={currency}
                            field={field}
                            onQuantityChange={() => {}}
                            onRemove={() => {}}
                        />,
                    )}
                </DemoState>
                <DemoState label="Details, as the server sent them back with errors">
                    {step(
                        1,
                        details.map((detail) => (
                            <TextField
                                key={detail.id}
                                label={detail.label}
                                id={`checkout-${detail.id}`}
                                name={detail.id}
                                defaultValue={typed[detail.id]}
                                error={detailErrors[detail.id]}
                            />
                        )),
                    )}
                </DemoState>
                <DemoState label="Review">
                    {step(
                        2,
                        <div className="grid gap-6">
                            <DetailList
                                layout="beside"
                                details={details.map((detail) => ({
                                    label: detail.label,
                                    value: filled[detail.id],
                                }))}
                            />
                            <p className="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
                                {paymentNote}
                            </p>
                        </div>,
                    )}
                </DemoState>
            </div>
        </PublicFrame>
    );
}
