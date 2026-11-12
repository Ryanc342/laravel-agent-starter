import FormField from '@/components/shared/form-field';
import Pagination from '@/components/shared/pagination';
import ResponsiveDialog, {
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
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
            <Specimen label="FormField: the label on the left on desktop and above on a phone, the error under the input">
                <div className="grid w-full max-w-xl gap-6">
                    <FormField label="Title" htmlFor="style-field-title">
                        <Input
                            id="style-field-title"
                            defaultValue="Write the report"
                        />
                    </FormField>
                    <FormField
                        label="Due date"
                        htmlFor="style-field-due"
                        error="Enter a date that exists."
                    >
                        <Input
                            id="style-field-due"
                            defaultValue="31/02/2026"
                            aria-invalid
                        />
                    </FormField>
                </div>
            </Specimen>

            <Specimen label="FormField, stacked (a narrow column) and with a hidden label (a search box)">
                <div className="grid w-full max-w-sm gap-6">
                    <FormField
                        label="Email address"
                        htmlFor="style-field-email"
                        stacked
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

            <Specimen label="ResponsiveDialog: a centred dialog on desktop, a sheet from the bottom on a phone (open it, then narrow the window)">
                <ResponsiveDialog
                    trigger={<Button variant="outline">Delete task</Button>}
                    title="Delete this task?"
                    description="“Write the report” will be deleted. This cannot be undone."
                >
                    <ResponsiveDialogFooter>
                        <Button variant="destructive">Delete task</Button>
                        <ResponsiveDialogClose asChild>
                            <Button variant="outline">Cancel</Button>
                        </ResponsiveDialogClose>
                    </ResponsiveDialogFooter>
                </ResponsiveDialog>
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
                />
            </div>

            <div className="grid gap-3">
                <p className="text-sm text-muted-foreground">
                    Pagination: previous and next, only when the list has more
                    than one page
                </p>
                <Pagination collection={pagination} />
            </div>
        </div>
    );
}
