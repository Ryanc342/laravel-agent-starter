import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import BulkActionBar from '@/components/shared/bulk-action-bar';
import ResponsiveDialog, {
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import ResponsiveTable from '@/components/shared/responsive-table';
import type { TableColumn } from '@/components/shared/responsive-table';
import type { ExampleTask } from '@/components/style/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useRowSelection } from '@/hooks/use-row-selection';
import { formatDate } from '@/lib/format';

const columns: TableColumn<ExampleTask>[] = [
    {
        header: 'Title',
        className: 'max-w-80 truncate font-medium',
        cell: (task) => task.title,
    },
    {
        header: 'Status',
        hideLabelOnCard: true,
        cell: (task) => <Badge variant="secondary">{task.status}</Badge>,
    },
    {
        header: 'Due date',
        cell: (task) => (task.due_on ? formatDate(task.due_on) : 'No due date'),
    },
];

/**
 * Ticking rows: a checkbox column (a checkbox on each card on a phone), and
 * the bar that floats at the bottom of the page. When the header checkbox has
 * ticked the whole page and more results exist, the bar offers "Select all N
 * results". Delete asks first, naming the count. The demo changes nothing.
 */
export default function BulkActionsDemo({
    tasks,
    total,
}: {
    tasks: ExampleTask[];
    /** How many results the list has in all. */
    total: number;
}) {
    const { url } = usePage();
    const selection = useRowSelection(
        tasks.map((task) => task.id),
        url,
    );
    const [confirming, setConfirming] = useState(false);
    const count = selection.allResults ? total : selection.count;
    const noun = count === 1 ? 'task' : 'tasks';

    return (
        <div className="grid gap-4">
            <Heading title="Tasks" />
            <ResponsiveTable
                columns={columns}
                rows={tasks}
                getRowKey={(task) => task.id}
                emptyMessage="You have no tasks yet."
                selection={{
                    pageState: selection.pageState,
                    onTogglePage: selection.togglePage,
                    isSelected: (task) => selection.isSelected(task.id),
                    onToggle: (task) => selection.toggle(task.id),
                    rowLabel: (task) => `Select ${task.title}`,
                }}
            />
            <BulkActionBar
                count={count}
                allResults={selection.allResults}
                total={total}
                onSelectPage={
                    selection.pageState !== 'all' && !selection.allResults
                        ? selection.selectPage
                        : undefined
                }
                onSelectAllResults={
                    selection.pageState === 'all' &&
                    total > tasks.length &&
                    !selection.allResults
                        ? selection.selectAllResults
                        : undefined
                }
                onClear={selection.clear}
            >
                <Button
                    onClick={() => {
                        toast.success(`${count} ${noun} completed.`);
                        selection.clear();
                    }}
                >
                    Complete
                </Button>
                <Button variant="outline" onClick={() => setConfirming(true)}>
                    Delete
                </Button>
            </BulkActionBar>

            <ResponsiveDialog
                open={confirming}
                onOpenChange={setConfirming}
                title={`Delete ${count} ${noun}?`}
                description="They will be deleted. This cannot be undone."
            >
                <ResponsiveDialogFooter>
                    <Button
                        variant="destructive"
                        onClick={() => {
                            toast.success(`${count} ${noun} deleted.`);
                            setConfirming(false);
                            selection.clear();
                        }}
                    >
                        Delete {count} {noun}
                    </Button>
                    <ResponsiveDialogClose asChild>
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </ResponsiveDialogClose>
                </ResponsiveDialogFooter>
            </ResponsiveDialog>
        </div>
    );
}
