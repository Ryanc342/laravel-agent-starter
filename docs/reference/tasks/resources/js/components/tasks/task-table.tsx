import { Link } from '@inertiajs/react';
import type { Choice } from '@/components/shared/column-menu';
import ListToolbar from '@/components/shared/list-toolbar';
import ResponsiveTable from '@/components/shared/responsive-table';
import type { TableColumn } from '@/components/shared/responsive-table';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@/lib/format';
import { show } from '@/routes/tasks';
import type { Task } from '@/types/task';

/**
 * The columns and their menus. The title can be sorted and searched, the status
 * sorted and ticked (the options come from the backend), the due date sorted
 * and limited to a from-to range.
 */
function taskColumns(statusOptions: Choice[]): TableColumn<Task>[] {
    return [
        {
            header: 'Title',
            className: 'max-w-80 truncate font-medium',
            menu: { sortKey: 'title', search: { param: 'search' } },
            cell: (task) => (
                <Link
                    href={show(task.id)}
                    className="underline-offset-4 hover:underline active:text-primary-pressed"
                >
                    {task.title}
                </Link>
            ),
        },
        {
            header: 'Status',
            hideLabelOnCard: true,
            menu: {
                sortKey: 'status',
                options: { param: 'status', choices: statusOptions },
            },
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
            menu: {
                sortKey: 'due_on',
                range: {
                    fromParam: 'due_from',
                    toParam: 'due_to',
                    type: 'date',
                },
            },
            cell: (task) =>
                task.due_on ? formatDate(task.due_on) : 'No due date',
        },
    ];
}

/**
 * The task list: the search box, then a table on desktop and one card per task
 * on a phone (where a "Sort & filter" button holds the column menus). The title
 * opens the task's page, where its actions are. With no tasks the table keeps
 * its header row and shows the empty state inside it; when the search or a
 * filter matches nothing the state says so. The status comes right after the
 * title, so a card shows both first.
 */
export default function TaskTable({
    tasks,
    statusOptions,
    narrowed,
}: {
    tasks: Task[];
    statusOptions: Choice[];
    /** From the backend: the search or a filter really narrows the list. */
    narrowed: boolean;
}) {
    const columns = taskColumns(statusOptions);

    return (
        <div className="flex flex-col gap-4">
            <ListToolbar columns={columns} searchLabel="Search tasks" />
            <ResponsiveTable
                columns={columns}
                rows={tasks}
                getRowKey={(task) => task.id}
                emptyMessage={
                    narrowed
                        ? 'No tasks match your search or filters.'
                        : 'You have no tasks yet.'
                }
            />
        </div>
    );
}
