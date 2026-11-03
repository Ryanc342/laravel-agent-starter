import { Form, Head } from '@inertiajs/react';
import OverdueTaskCompletionController from '@/actions/App/Http/Controllers/OverdueTaskCompletionController';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import Heading from '@/components/heading';
import Pagination from '@/components/shared/pagination';
import TaskFields from '@/components/tasks/task-fields';
import TaskRow from '@/components/tasks/task-row';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/tasks';
import type { ResourceCollection } from '@/types/resource';
import type { Task } from '@/types/task';

type TasksPageProps = {
    tasks: ResourceCollection<Task>;
    can: {
        complete_overdue: boolean;
    };
};

export default function Tasks({ tasks, can }: TasksPageProps) {
    return (
        <>
            <Head title="Tasks" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title="Tasks" />

                    {can.complete_overdue && (
                        <Form
                            {...OverdueTaskCompletionController.store.form()}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    Complete overdue tasks
                                </Button>
                            )}
                        </Form>
                    )}
                </div>

                <Form
                    {...TaskController.store.form()}
                    resetOnSuccess
                    options={{ preserveScroll: true }}
                    className="grid max-w-xl gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <TaskFields errors={errors} />
                            <div>
                                <Button type="submit" disabled={processing}>
                                    Add task
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                {tasks.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        You have no tasks yet.
                    </p>
                ) : (
                    <>
                        <ul className="grid gap-2">
                            {tasks.data.map((task) => (
                                <TaskRow key={task.id} task={task} />
                            ))}
                        </ul>
                        <Pagination collection={tasks} />
                    </>
                )}
            </div>
        </>
    );
}

Tasks.layout = {
    breadcrumbs: [
        {
            title: 'Tasks',
            href: index(),
        },
    ],
};
