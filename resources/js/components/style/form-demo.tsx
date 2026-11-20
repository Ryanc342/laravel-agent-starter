import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import DatePicker from '@/components/shared/date-picker';
import FormField from '@/components/shared/form-field';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import { Specimen } from '@/components/style/style-section';
import type { ExampleTask } from '@/components/style/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatMoney } from '@/lib/format';
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

type InvoiceLine = {
    id: number;
    description: string;
    /** As typed in the quantity and unit price boxes. */
    quantity: string;
    unit: string;
    /** The line's amount in minor units, as the backend sends it. */
    total: number;
};

/** An invoice as the backend would send it: amounts in minor units, the total worked out. */
export type ExampleInvoiceForm = {
    customer: string;
    date: string;
    due: string;
    note: string;
    currency: string;
    lines: InvoiceLine[];
    total: number;
};

/** A line of the form: as the backend sent it, or changed or added since, so its amount is not known until it is saved. */
type FormLine = { key: number; line: InvoiceLine | null };

/**
 * A long form with lines, in the wide dialog: the invoice's fields, one row
 * of inputs per line with a remove button, an "Add line" button and the
 * total. The amounts come from the backend, worked out when the form is
 * saved: nothing is added up here, so a line changed, added or removed since
 * shows no amount, and then the total shows none either. On a phone each line
 * stacks: the description and its remove button, then the numbers.
 */
function WideDemoForm({ invoice }: { invoice: ExampleInvoiceForm }) {
    const [lines, setLines] = useState<FormLine[]>(() =>
        invoice.lines.map((line) => ({ key: line.id, line })),
    );
    const [nextKey, setNextKey] = useState(
        () => Math.max(0, ...invoice.lines.map((line) => line.id)) + 1,
    );
    const changed =
        lines.length !== invoice.lines.length ||
        lines.some((row) => row.line === null);
    const unknown = '—';

    const forget = (key: number) =>
        setLines((rows) =>
            rows.map((row) => (row.key === key ? { ...row, line: null } : row)),
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

                <div className="flex flex-col gap-3">
                    <p className="text-sm font-medium">{t('Lines')}</p>
                    <div className="hidden grid-cols-12 gap-2 text-sm text-muted-foreground sm:grid">
                        <span className="col-span-5">{t('Description')}</span>
                        <span className="col-span-2 text-right">
                            {t('Qty')}
                        </span>
                        <span className="col-span-2 text-right">
                            {t('Price')}
                        </span>
                        <span className="col-span-2 text-right">
                            {t('Amount')}
                        </span>
                    </div>
                    {lines.map((row, index) => {
                        const id = `wide-demo-line-${row.key}`;
                        const number = index + 1;

                        return (
                            <div
                                key={row.key}
                                className="grid grid-cols-12 items-center gap-2 border-b pb-3 sm:border-0 sm:pb-0"
                            >
                                <FormField
                                    hideLabel
                                    label={t('Description of line :number', {
                                        number,
                                    })}
                                    htmlFor={`${id}-description`}
                                    className="col-span-10 sm:order-1 sm:col-span-5"
                                >
                                    <Input
                                        id={`${id}-description`}
                                        defaultValue={row.line?.description}
                                        onChange={() => forget(row.key)}
                                    />
                                </FormField>
                                <FormField
                                    hideLabel
                                    label={t('Quantity of line :number', {
                                        number,
                                    })}
                                    htmlFor={`${id}-quantity`}
                                    className="col-span-3 sm:order-2 sm:col-span-2"
                                >
                                    <Input
                                        id={`${id}-quantity`}
                                        inputMode="decimal"
                                        className="text-right tabular-nums"
                                        defaultValue={row.line?.quantity}
                                        onChange={() => forget(row.key)}
                                    />
                                </FormField>
                                <FormField
                                    hideLabel
                                    label={t('Unit price of line :number', {
                                        number,
                                    })}
                                    htmlFor={`${id}-unit`}
                                    className="col-span-5 sm:order-3 sm:col-span-2"
                                >
                                    <Input
                                        id={`${id}-unit`}
                                        inputMode="decimal"
                                        className="text-right tabular-nums"
                                        defaultValue={row.line?.unit}
                                        onChange={() => forget(row.key)}
                                    />
                                </FormField>
                                <p className="col-span-4 text-right text-sm tabular-nums sm:order-4 sm:col-span-2">
                                    {row.line
                                        ? formatMoney(
                                              row.line.total,
                                              invoice.currency,
                                          )
                                        : unknown}
                                </p>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="col-span-2 col-start-11 row-start-1 justify-self-end sm:order-5 sm:col-span-1 sm:col-start-auto sm:row-start-auto"
                                            aria-label={t(
                                                'Remove line :number',
                                                {
                                                    number,
                                                },
                                            )}
                                            onClick={() =>
                                                setLines((rows) =>
                                                    rows.filter(
                                                        (other) =>
                                                            other.key !==
                                                            row.key,
                                                    ),
                                                )
                                            }
                                        >
                                            <X />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {t('Remove line')}
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        );
                    })}
                    <div className="flex items-center justify-between gap-4 border-t pt-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                setLines((rows) => [
                                    ...rows,
                                    { key: nextKey, line: null },
                                ]);
                                setNextKey((key) => key + 1);
                            }}
                        >
                            <Plus />
                            {t('Add line')}
                        </Button>
                        <p className="text-sm font-medium tabular-nums">
                            {t('Total')}{' '}
                            {changed
                                ? unknown
                                : formatMoney(invoice.total, invoice.currency)}
                        </p>
                    </div>
                </div>

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
}: {
    task: ExampleTask;
    requiredMessage: string;
    invoice: ExampleInvoiceForm;
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
                <WideDemoForm invoice={invoice} />
            </ResponsiveDialog>
        </Specimen>
    );
}
