import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import DatePicker from '@/components/shared/date-picker';
import IconButton from '@/components/shared/icon-button';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import { Specimen } from '@/components/style/style-section';
import type { ExampleTask } from '@/components/style/types';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/format';

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
                <TextField
                    label="Title"
                    name="title"
                    defaultValue={task?.title}
                    error={error}
                />
                <DatePicker
                    label="Due date"
                    name="due_on"
                    defaultValue={task?.due_on ?? ''}
                />
            </ResponsiveDialogBody>

            <ResponsiveDialogFooter>
                <ResponsiveDialogClose asChild>
                    <Button variant="outline">Cancel</Button>
                </ResponsiveDialogClose>
                <SubmitButton sending={false}>{submitLabel}</SubmitButton>
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
                <TextField label="Customer" defaultValue={invoice.customer} />
                <DatePicker label="Invoice date" defaultValue={invoice.date} />
                <DatePicker label="Due date" defaultValue={invoice.due} />

                <div className="flex flex-col gap-3">
                    <Heading variant="small" title="Lines" />
                    {lines.map((row, index) => {
                        const id = `wide-demo-line-${row.key}`;
                        const number = index + 1;

                        return (
                            <div
                                key={row.key}
                                className="grid grid-cols-12 items-center gap-2 border-b pb-3 sm:border-0 sm:pb-0"
                            >
                                <TextField
                                    label="Description"
                                    aria-label={`Description of line ${number}`}
                                    id={`${id}-description`}
                                    defaultValue={row.line?.description}
                                    onChange={() => forget(row.key)}
                                    className="col-span-10 sm:order-1 sm:col-span-5"
                                />
                                <TextField
                                    label="Quantity"
                                    aria-label={`Quantity of line ${number}`}
                                    id={`${id}-quantity`}
                                    inputMode="decimal"
                                    defaultValue={row.line?.quantity}
                                    onChange={() => forget(row.key)}
                                    className="col-span-3 sm:order-2 sm:col-span-2"
                                />
                                <TextField
                                    label="Price"
                                    aria-label={`Unit price of line ${number}`}
                                    id={`${id}-unit`}
                                    inputMode="decimal"
                                    defaultValue={row.line?.unit}
                                    onChange={() => forget(row.key)}
                                    className="col-span-5 sm:order-3 sm:col-span-2"
                                />
                                <p className="col-span-4 text-right text-sm tabular-nums sm:order-4 sm:col-span-2">
                                    {row.line
                                        ? formatMoney(
                                              row.line.total,
                                              invoice.currency,
                                          )
                                        : unknown}
                                </p>
                                <IconButton
                                    label={`Remove line ${number}`}
                                    className="col-span-2 col-start-11 row-start-1 justify-self-end sm:order-5 sm:col-span-1 sm:col-start-auto sm:row-start-auto"
                                    onClick={() =>
                                        setLines((rows) =>
                                            rows.filter(
                                                (other) =>
                                                    other.key !== row.key,
                                            ),
                                        )
                                    }
                                >
                                    <X />
                                </IconButton>
                            </div>
                        );
                    })}
                    <div className="flex items-center justify-between gap-4 pt-3 sm:border-t">
                        <Button
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
                            Add
                        </Button>
                        <p className="text-sm font-medium tabular-nums">
                            Total{' '}
                            {changed
                                ? unknown
                                : formatMoney(invoice.total, invoice.currency)}
                        </p>
                    </div>
                </div>

                <TextField label="Note" defaultValue={invoice.note} />
            </ResponsiveDialogBody>

            <ResponsiveDialogFooter>
                <ResponsiveDialogClose asChild>
                    <Button variant="outline">Cancel</Button>
                </ResponsiveDialogClose>
                <SubmitButton sending={false}>Create</SubmitButton>
            </ResponsiveDialogFooter>
        </form>
    );
}

/**
 * Creating and editing a record: always a dialog, never a form above the list.
 * A long form with lines uses the wide dialog. In an app the dialog sends an
 * operation (Route::operation()).
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
        <Specimen label="Open each one: create, edit (filled with the record), a validation error (the dialog stays open), and a long form with lines (the wide size)">
            <ResponsiveDialog trigger={<Button>Add</Button>} title="Add task">
                <DemoForm submitLabel="Add" />
            </ResponsiveDialog>

            <ResponsiveDialog
                trigger={<Button variant="outline">Edit</Button>}
                title="Edit task"
            >
                <DemoForm task={task} submitLabel="Save" />
            </ResponsiveDialog>

            <ResponsiveDialog
                trigger={<Button variant="outline">With an error</Button>}
                title="Add task"
            >
                <DemoForm submitLabel="Add" error={requiredMessage} />
            </ResponsiveDialog>

            <ResponsiveDialog
                wide
                trigger={
                    <Button variant="outline">
                        Wide: an invoice with lines
                    </Button>
                }
                title="New invoice"
            >
                <WideDemoForm invoice={invoice} />
            </ResponsiveDialog>
        </Specimen>
    );
}
