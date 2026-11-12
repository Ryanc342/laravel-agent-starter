import { Form, Head } from '@inertiajs/react';
import OverdueTaskCompletionController from '@/actions/App/Http/Controllers/OverdueTaskCompletionController';
import Heading from '@/components/heading';
import EmptyState from '@/components/shared/empty-state';
import Pagination from '@/components/shared/pagination';
import TaskFormDialog from '@/components/tasks/task-form-dialog';
import TaskTable from '@/components/tasks/task-table';
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

            <div className="flex flex-col gap-8 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title="Tasks" />

                    <div className="flex flex-wrap gap-2">
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

                        <TaskFormDialog trigger={<Button>Add task</Button>} />
                    </div>
                </div>

                {tasks.data.length === 0 ? (
                    // The header already has the create button, so the empty
                    // state is only the line.
                    <EmptyState message="You have no tasks yet." />
                ) : (
                    <>
                        <TaskTable tasks={tasks.data} />
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
