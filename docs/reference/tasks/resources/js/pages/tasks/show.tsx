import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import Heading from '@/components/heading';
import TaskActions from '@/components/tasks/task-actions';
import TaskFields from '@/components/tasks/task-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { index, show } from '@/routes/tasks';
import type { Task } from '@/types/task';

type ShowTaskProps = {
    task: Task;
};

export default function ShowTask({ task }: ShowTaskProps) {
    const [editing, setEditing] = useState(false);

    return (
        <>
            <Head title={task.title} />

            <div className="flex flex-col gap-6 p-4">
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

                    {!editing && (
                        <TaskActions
                            task={task}
                            onEdit={() => setEditing(true)}
                        />
                    )}
                </div>

                {editing ? (
                    <Form
                        {...TaskController.update.form(task.id)}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setEditing(false)}
                        className="grid max-w-xl gap-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <TaskFields task={task} errors={errors} />
                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Save changes
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setEditing(false)}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                ) : (
                    <dl className="grid max-w-xl gap-1 text-sm">
                        <dt className="text-muted-foreground">Due date</dt>
                        <dd>
                            {task.due_on
                                ? formatDate(task.due_on)
                                : 'No due date'}
                        </dd>
                    </dl>
                )}
            </div>
        </>
    );
}

// The breadcrumb ends at this page and uses its data, so the title shows and
// the page needs no separate "back" button.
ShowTask.layout = (props: ShowTaskProps) => ({
    breadcrumbs: [
        { title: 'Tasks', href: index() },
        { title: props.task.title, href: show(props.task.id) },
    ],
});
