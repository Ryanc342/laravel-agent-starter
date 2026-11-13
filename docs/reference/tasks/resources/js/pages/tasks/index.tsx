import { Form, Head, usePage } from '@inertiajs/react';
import OverdueTaskCompletionController from '@/actions/App/Http/Controllers/OverdueTaskCompletionController';
import TaskExportController from '@/actions/App/Http/Controllers/TaskExportController';
import Heading from '@/components/heading';
import type { Choice } from '@/components/shared/column-menu';
import Pagination from '@/components/shared/pagination';
import TaskBulkActions from '@/components/tasks/task-bulk-actions';
import TaskFormDialog from '@/components/tasks/task-form-dialog';
import TaskTable from '@/components/tasks/task-table';
import { Button } from '@/components/ui/button';
import { useRowSelection } from '@/hooks/use-row-selection';
import { queryString } from '@/lib/list-query';
import { index } from '@/routes/tasks';
import type { ResourceCollection } from '@/types/resource';
import type { Task } from '@/types/task';

type TasksPageProps = {
    tasks: ResourceCollection<Task>;
    statusOptions: Choice[];
    narrowed: boolean;
    can: {
        complete_overdue: boolean;
    };
};

export default function Tasks({
    tasks,
    statusOptions,
    narrowed,
    can,
}: TasksPageProps) {
    const { url } = usePage();
    // Ticked rows start again whenever the list's address changes: search, sort, filters or page.
    const selection = useRowSelection(
        tasks.data.map((task) => task.id),
        url,
    );

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

                        <Button variant="outline" asChild>
                            {/* A plain link: the file is downloaded, and it is the list as it is shown now. */}
                            <a
                                href={`${TaskExportController.url()}${queryString(url)}`}
                            >
                                Export
                            </a>
                        </Button>

                        <TaskFormDialog trigger={<Button>Add task</Button>} />
                    </div>
                </div>

                <TaskTable
                    tasks={tasks.data}
                    statusOptions={statusOptions}
                    narrowed={narrowed}
                    selection={{
                        pageState: selection.pageState,
                        onTogglePage: selection.togglePage,
                        isSelected: (task) => selection.isSelected(task.id),
                        onToggle: (task) => selection.toggle(task.id),
                        rowLabel: (task) => `Select ${task.title}`,
                    }}
                />
                <Pagination collection={tasks} />
                <TaskBulkActions
                    selection={selection}
                    total={tasks.meta?.total ?? tasks.data.length}
                    pageSize={tasks.data.length}
                />
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
