import DatePicker from '@/components/shared/date-picker';
import FormField from '@/components/shared/form-field';
import OrderSummary from '@/components/shared/order-summary';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatMoney, formatQuantity } from '@/lib/format';
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

type SummaryRow = { label: string; amount: number };

/** An invoice as the backend would send it: amounts in minor units, totals worked out. */
export type ExampleInvoiceForm = {
    customer: string;
    date: string;
    due: string;
    note: string;
    currency: string;
    lines: {
        id: number;
        description: string;
        quantity: string;
        unit: number;
        total: number;
    }[];
    rows: SummaryRow[];
    total: SummaryRow;
};

/**
 * A long form with lines, in the wide dialog: the invoice's fields, its lines
 * as a table and its totals. The totals come with the data, as from the
 * backend: nothing is added up here.
 */
function WideDemoForm({ invoice }: { invoice: ExampleInvoiceForm }) {
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
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{t('Description')}</TableHead>
                            <TableHead className="text-right">
                                {t('Qty')}
                            </TableHead>
                            <TableHead className="text-right">
                                {t('Price')}
                            </TableHead>
                            <TableHead className="text-right">
                                {t('Total')}
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {invoice.lines.map((line) => (
                            <TableRow key={line.id}>
                                <TableCell className="whitespace-normal">
                                    {line.description}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatQuantity(line.quantity)}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatMoney(line.unit, invoice.currency)}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatMoney(line.total, invoice.currency)}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                <OrderSummary
                    rows={invoice.rows}
                    total={invoice.total}
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
