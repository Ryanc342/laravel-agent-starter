import { Link } from '@inertiajs/react';
import ResponsiveTable from '@/components/shared/responsive-table';
import type { TableColumn } from '@/components/shared/responsive-table';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@/lib/format';
import { show } from '@/routes/tasks';
import type { Task } from '@/types/task';

const columns: TableColumn<Task>[] = [
    {
        header: 'Title',
        className: 'max-w-80 truncate font-medium',
        cell: (task) => (
            <Link
                href={show(task.id)}
                className="underline-offset-4 hover:underline"
            >
                {task.title}
            </Link>
        ),
    },
    {
        header: 'Status',
        hideLabelOnCard: true,
        cell: (task) => (
            <div className="flex flex-wrap gap-2">
                <Badge variant="secondary">{task.status_label}</Badge>
                {task.is_overdue && (
                    <Badge variant="destructive">Overdue</Badge>
                )}
            </div>
        ),
    },
    {
        header: 'Due date',
        cell: (task) => (task.due_on ? formatDate(task.due_on) : 'No due date'),
    },
];

/**
 * The task list: a table on desktop, one card per task on a phone. The title
 * opens the task's page, where its actions are. With no tasks the table keeps
 * its header row and shows the empty line inside it (the header already has
 * the create button, so the line is all it says). The status comes right after
 * the title, so a card shows both first.
 */
export default function TaskTable({ tasks }: { tasks: Task[] }) {
    return (
        <ResponsiveTable
            columns={columns}
            rows={tasks}
            getRowKey={(task) => task.id}
            emptyMessage="You have no tasks yet."
        />
    );
}
