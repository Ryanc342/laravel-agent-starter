import { Form, Head } from '@inertiajs/react';
import OverdueTaskCompletionController from '@/actions/App/Http/Controllers/OverdueTaskCompletionController';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import Heading from '@/components/heading';
import TaskFields from '@/components/tasks/task-fields';
import TaskRow from '@/components/tasks/task-row';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/tasks';
import type { ResourceCollection } from '@/types/resource';
import type { Task } from '@/types/task';

type TasksPageProps = {
    tasks: ResourceCollection<Task>;
    overdueCount: number;
};

export default function Tasks({ tasks, overdueCount }: TasksPageProps) {
    return (
        <>
            <Head title="Tasks" />

            <div className="flex flex-col gap-6 p-4">
                <Heading title="Tasks" description="Your to-do list" />

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

                {overdueCount > 0 && (
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
                                Complete {overdueCount} overdue
                            </Button>
                        )}
                    </Form>
                )}

                <ul className="grid gap-2">
                    {tasks.data.map((task) => (
                        <TaskRow key={task.id} task={task} />
                    ))}
                </ul>
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
