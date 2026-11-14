import FormField from '@/components/shared/form-field';
import Pagination from '@/components/shared/pagination';
import ConfirmDialog, {
    ConfirmDialogActions,
} from '@/components/shared/confirm-dialog';
import ResponsiveTable from '@/components/shared/responsive-table';
import { Specimen } from '@/components/style/style-section';
import type { ExampleTask } from '@/components/style/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/format';
import type { ResourceCollection } from '@/types/resource';

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
            <Specimen label="FormField: the label on the left on desktop and above on a phone; a field with an error has a red border and its message is a toast (open the dialog under Form)">
                <div className="grid w-full max-w-xl gap-6">
                    <FormField label="Title" htmlFor="style-field-title">
                        <Input
                            id="style-field-title"
                            defaultValue="Write the report"
                        />
                    </FormField>
                    <FormField label="Due date" htmlFor="style-field-due">
                        <Input
                            id="style-field-due"
                            defaultValue="31/02/2026"
                            aria-invalid
                        />
                    </FormField>
                </div>
            </Specimen>

            <Specimen label="FormField in a narrow column (the label moves above the input) and with a hidden label (a search box)">
                <div className="grid w-full max-w-sm gap-6">
                    <FormField
                        label="Email address"
                        htmlFor="style-field-email"
                    >
                        <Input
                            id="style-field-email"
                            placeholder="email@example.com"
                        />
                    </FormField>
                    <FormField
                        label="Search tasks"
                        htmlFor="style-field-search"
                        hideLabel
                    >
                        <Input
                            id="style-field-search"
                            placeholder="Search tasks"
                        />
                    </FormField>
                </div>
            </Specimen>

            <Specimen label="ConfirmDialog (a ResponsiveDialog): a centred dialog on desktop, a sheet from the bottom on a phone (open it, then narrow the window)">
                <ConfirmDialog
                    trigger={<Button variant="outline">Delete task</Button>}
                    title="Delete this task?"
                    consequence="“Write the report” will be deleted. This cannot be undone."
                >
                    <ConfirmDialogActions>
                        <Button variant="destructive">Delete task</Button>
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
                <Pagination collection={pagination} />
            </div>
        </div>
    );
}
