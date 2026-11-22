import { Trash2, X } from 'lucide-react';
import ConfirmButton from '@/components/shared/confirm-button';
import ConfirmDialog, {
    ConfirmDialogActions,
} from '@/components/shared/confirm-dialog';
import DatePicker from '@/components/shared/date-picker';
import DetailList from '@/components/shared/detail-list';
import IconButton from '@/components/shared/icon-button';
import LabeledDivider from '@/components/shared/labeled-divider';
import Pagination from '@/components/shared/pagination';
import PasswordField from '@/components/shared/password-field';
import ResponsiveTable from '@/components/shared/responsive-table';
import RowActions from '@/components/shared/row-actions';
import SelectField from '@/components/shared/select-field';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import TextareaField from '@/components/shared/textarea-field';
import UrlSearchInput from '@/components/shared/url-search-input';
import { Specimen } from '@/components/style/style-section';
import type { ExampleTask } from '@/components/style/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { style } from '@/routes';
import type { ResourceCollection } from '@/types/resource';

const statuses = [
    { value: 'open', label: 'Open' },
    { value: 'done', label: 'Done' },
];

/**
 * The blocks in components/shared. EmptyState, ErrorState and LoadingState are
 * under States, FeedRow under Feed, QuickLinks under Dashboard and ErrorContent
 * under Error pages.
 */
export default function SharedDemo({
    tasks,
    pagination,
}: {
    tasks: ExampleTask[];
    pagination: ResourceCollection<ExampleTask>;
}) {
    return (
        <div className="grid gap-8">
            <Specimen label="Boxed fields: the label floats inside the box. Empty, filled, with an error from the server (a red border; the message is a toast), and disabled">
                <div className="grid w-full gap-6 sm:grid-cols-2">
                    <TextField label="Title" />
                    <TextField label="Title" defaultValue="Write the report" />
                    <TextField
                        label="Due date"
                        defaultValue="31/02/2026"
                        error="The due date is not a date."
                    />
                    <TextField label="Title" defaultValue="Locked" disabled />
                </div>
            </Specimen>

            <Specimen label="PasswordField, TextareaField, SelectField and DatePicker">
                <div className="grid w-full gap-6 sm:grid-cols-2">
                    <PasswordField label="Password" />
                    <SelectField
                        label="Status"
                        options={statuses}
                        defaultValue="open"
                    />
                    <TextareaField label="Note" />
                    <DatePicker label="Due date" defaultValue="2026-11-20" />
                </div>
            </Specimen>

            <Specimen label="DetailList: the facts of a record, stacked or beside">
                <div className="grid w-full gap-6 sm:grid-cols-2">
                    <DetailList
                        details={[
                            { label: 'Status', value: 'Open' },
                            { label: 'Due date', value: 'No due date' },
                        ]}
                    />
                    <DetailList
                        layout="beside"
                        details={[
                            {
                                label: 'Customer',
                                value: 'Invoice Desk Sdn Bhd',
                            },
                            { label: 'Order date', value: '4 Nov 2026' },
                        ]}
                    />
                </div>
            </Specimen>

            <Specimen label="UrlSearchInput: a search tied to the address (?search=); LabeledDivider between two ways to do one thing">
                <div className="grid w-full gap-6 sm:grid-cols-2">
                    <UrlSearchInput param="search" label="Search tasks" />
                    <LabeledDivider>Or continue with email</LabeledDivider>
                </div>
            </Specimen>

            <Specimen label="RowActions: a row's own actions in one menu, the destructive one last">
                <RowActions
                    actions={[
                        { label: 'Edit', onSelect: () => undefined },
                        {
                            label: 'Delete',
                            destructive: true,
                            onSelect: () => undefined,
                        },
                    ]}
                />
            </Specimen>

            <Specimen label="SubmitButton, ready and sending; IconButton (hover or focus it for its name)">
                <SubmitButton sending={false}>Save</SubmitButton>
                <SubmitButton sending>Save</SubmitButton>
                <IconButton label="Close">
                    <X />
                </IconButton>
                <IconButton label="Delete" variant="outline" size="icon-sm">
                    <Trash2 />
                </IconButton>
            </Specimen>

            <Specimen label="ConfirmButton: an action that cannot be undone opens ConfirmDialog first. ConfirmDialog: a centred dialog on desktop, a sheet from the bottom on a phone">
                <ConfirmButton
                    variant="outline"
                    form={{ action: style().url, method: 'get' }}
                    title="Delete this task?"
                    consequence="“Write the report” will be deleted. This cannot be undone."
                >
                    Delete
                </ConfirmButton>
                <ConfirmDialog
                    trigger={<Button variant="outline">Open the dialog</Button>}
                    title="Delete this task?"
                    consequence="“Write the report” will be deleted. This cannot be undone."
                >
                    <ConfirmDialogActions>
                        <Button variant="destructive">Delete</Button>
                    </ConfirmDialogActions>
                </ConfirmDialog>
            </Specimen>

            <div className="grid gap-3">
                <p className="text-sm text-muted-foreground">
                    ResponsiveTable: a table on desktop, one card per row on a
                    phone
                </p>
                <ResponsiveTable
                    columns={[
                        { header: 'Title', cell: (task) => task.title },
                        {
                            header: 'Status',
                            hideLabelOnCard: true,
                            cell: (task) => (
                                <Badge variant="secondary">{task.status}</Badge>
                            ),
                        },
                        {
                            header: 'Due date',
                            cell: (task) =>
                                task.due_on
                                    ? formatDate(task.due_on)
                                    : 'No due date',
                        },
                    ]}
                    rows={tasks.slice(0, 2)}
                    getRowKey={(task) => task.id}
                    emptyMessage="You have no tasks yet."
                />
            </div>

            <div className="grid gap-3">
                <p className="text-sm text-muted-foreground">
                    ResponsiveTable with no records: the header row stays
                </p>
                <ResponsiveTable
                    columns={[
                        { header: 'Title', cell: (task) => task.title },
                        { header: 'Status', cell: (task) => task.status },
                        {
                            header: 'Due date',
                            cell: (task) =>
                                task.due_on
                                    ? formatDate(task.due_on)
                                    : 'No due date',
                        },
                    ]}
                    rows={tasks.slice(0, 0)}
                    getRowKey={(task) => task.id}
                    emptyMessage="You have no tasks yet."
                />
            </div>

            <div className="grid gap-3">
                <p className="text-sm text-muted-foreground">
                    Pagination: the page links when the list has more than one
                    page, and the rows per page (10, 20, 50, 100) next to them
                </p>
                <Pagination collection={pagination} label="Example pages" />
            </div>
        </div>
    );
}
