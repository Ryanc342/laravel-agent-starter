import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import TaskActions from '@/components/tasks/task-actions';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@/lib/format';
import { index } from '@/routes/tasks';
import type { Task } from '@/types/task';

type ShowTaskProps = {
    task: Task;
};

export default function ShowTask({ task }: ShowTaskProps) {
    return (
        <>
            <Head title={task.title} />

            <div className="flex flex-col gap-8 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex min-w-0 flex-wrap items-start gap-2">
                        <Heading title={task.title} />
                        <Badge variant="secondary" className="mt-1">
                            {task.status_label}
                        </Badge>
                        {task.is_overdue && (
                            <Badge variant="destructive" className="mt-1">
                                Overdue
                            </Badge>
                        )}
                    </div>

                    <TaskActions task={task} />
                </div>

                <dl className="grid max-w-xl gap-1 text-sm">
                    <dt className="text-muted-foreground">Due date</dt>
                    <dd>
                        {task.due_on ? formatDate(task.due_on) : 'No due date'}
                    </dd>
                </dl>
            </div>
        </>
    );
}

// The breadcrumb ends at the list and is the way back, so the page needs no
// separate "back" button. The task's name shows once, as the heading.
ShowTask.layout = {
    breadcrumbs: [{ title: 'Tasks', href: index() }],
};
