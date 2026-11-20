import { useState } from 'react';
import DatePicker from '@/components/shared/date-picker';
import FormField from '@/components/shared/form-field';
import LineItems, { type LineItem } from '@/components/shared/line-items';
import OrderSummary from '@/components/shared/order-summary';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import type { PricedCart } from '@/components/style/checkout-demo';
import { Specimen } from '@/components/style/style-section';
import type { ExampleTask } from '@/components/style/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';

/** The fields and buttons of a form dialog: fields in the body, buttons in the footer, main button first. */
function DemoForm({
    task,
    error,
    submitLabel,
}: {
    task?: ExampleTask;
    error?: string;
    submitLabel: string;
}) {
    return (
        <form
            noValidate
            onSubmit={(event) => event.preventDefault()}
            className="flex min-h-0 flex-1 flex-col gap-6"
        >
            <ResponsiveDialogBody>
                <FormField
                    label={t('Title')}
                    htmlFor="form-demo-title"
                    error={error}
                >
                    <Input id="form-demo-title" defaultValue={task?.title} />
                </FormField>
                <FormField label={t('Due date')} htmlFor="form-demo-due">
                    <DatePicker
                        id="form-demo-due"
                        defaultValue={task?.due_on ?? ''}
                    />
                </FormField>
            </ResponsiveDialogBody>

            <ResponsiveDialogFooter>
                <ResponsiveDialogClose asChild>
                    <Button type="button" variant="outline">
                        {t('Cancel')}
                    </Button>
                </ResponsiveDialogClose>
                <Button type="submit">{submitLabel}</Button>
            </ResponsiveDialogFooter>
        </form>
    );
}

export type ExampleInvoiceForm = {
    customer: string;
    date: string;
    due: string;
    note: string;
    currency: string;
    lines: LineItem[];
};

/**
 * A long form with lines, in the wide dialog: the invoice's fields, its lines
 * (the shared LineItems) and its totals. The server prices the lines; here
 * `price` stands in for its answer, as in the checkout demo.
 */
function WideDemoForm({
    invoice,
    price,
}: {
    invoice: ExampleInvoiceForm;
    price: (items: LineItem[]) => PricedCart;
}) {
    const [priced, setPriced] = useState<PricedCart>(() =>
        price(invoice.lines),
    );

    return (
        <form
            noValidate
            onSubmit={(event) => event.preventDefault()}
            className="flex min-h-0 flex-1 flex-col gap-6"
        >
            <ResponsiveDialogBody>
                <FormField label={t('Customer')} htmlFor="wide-demo-customer">
                    <Input
                        id="wide-demo-customer"
                        defaultValue={invoice.customer}
                    />
                </FormField>
                <FormField label={t('Invoice date')} htmlFor="wide-demo-date">
                    <DatePicker
                        id="wide-demo-date"
                        defaultValue={invoice.date}
                    />
                </FormField>
                <FormField label={t('Due date')} htmlFor="wide-demo-due">
                    <DatePicker id="wide-demo-due" defaultValue={invoice.due} />
                </FormField>
                <LineItems
                    items={priced.items}
                    currency={invoice.currency}
                    field="lines"
                    onQuantityChange={(id, quantity) =>
                        setPriced(
                            price(
                                priced.items.map((item) =>
                                    item.id === id
                                        ? { ...item, quantity }
                                        : item,
                                ),
                            ),
                        )
                    }
                    onRemove={(id) =>
                        setPriced(
                            price(
                                priced.items.filter((item) => item.id !== id),
                            ),
                        )
                    }
                />
                <OrderSummary
                    rows={priced.rows}
                    total={priced.total}
                    currency={invoice.currency}
                />
                <FormField label={t('Note')} htmlFor="wide-demo-note">
                    <Input id="wide-demo-note" defaultValue={invoice.note} />
                </FormField>
            </ResponsiveDialogBody>

            <ResponsiveDialogFooter>
                <ResponsiveDialogClose asChild>
                    <Button type="button" variant="outline">
                        {t('Cancel')}
                    </Button>
                </ResponsiveDialogClose>
                <Button type="submit">{t('Create invoice')}</Button>
            </ResponsiveDialogFooter>
        </form>
    );
}

/**
 * Creating and editing a record: always a dialog, never a form above the list.
 * A long form with lines uses the wide dialog.
 */
export default function FormDemo({
    task,
    requiredMessage,
    invoice,
    price,
}: {
    task: ExampleTask;
    requiredMessage: string;
    invoice: ExampleInvoiceForm;
    price: (items: LineItem[]) => PricedCart;
}) {
    return (
        <Specimen
            label={t(
                'Open each one: create, edit (filled with the record), a validation error (the dialog stays open), and a long form with lines (the wide size)',
            )}
        >
            <ResponsiveDialog
                trigger={<Button>{t('Add task')}</Button>}
                title={t('Add task')}
            >
                <DemoForm submitLabel={t('Add task')} />
            </ResponsiveDialog>

            <ResponsiveDialog
                trigger={<Button variant="outline">{t('Edit task')}</Button>}
                title={t('Edit task')}
            >
                <DemoForm task={task} submitLabel={t('Save changes')} />
            </ResponsiveDialog>

            <ResponsiveDialog
                trigger={
                    <Button variant="outline">{t('With an error')}</Button>
                }
                title={t('Add task')}
            >
                <DemoForm submitLabel={t('Add task')} error={requiredMessage} />
            </ResponsiveDialog>

            <ResponsiveDialog
                wide
                trigger={
                    <Button variant="outline">
                        {t('Wide: an invoice with lines')}
                    </Button>
                }
                title={t('New invoice')}
            >
                <WideDemoForm invoice={invoice} price={price} />
            </ResponsiveDialog>
        </Specimen>
    );
}
