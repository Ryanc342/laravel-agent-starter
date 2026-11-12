import { Form } from '@inertiajs/react';
import TaskCompletionController from '@/actions/App/Http/Controllers/TaskCompletionController';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import ResponsiveDialog, {
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import TaskFormDialog from '@/components/tasks/task-form-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { Task } from '@/types/task';

/**
 * The task page's actions. Every button is shown from task.can.*, which the
 * backend calculates.
 */
export default function TaskActions({ task }: { task: Task }) {
    return (
        <div className="flex flex-wrap gap-2">
            {task.can.complete && (
                <Form
                    {...TaskCompletionController.store.form(task.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <Button type="submit" disabled={processing}>
                            Complete task
                        </Button>
                    )}
                </Form>
            )}
            {task.can.update && (
                <TaskFormDialog
                    task={task}
                    trigger={<Button variant="outline">Edit task</Button>}
                />
            )}
            {task.can.delete && (
                <ResponsiveDialog
                    trigger={<Button variant="outline">Delete task</Button>}
                    title="Delete this task?"
                    description={`“${task.title}” will be deleted. This cannot be undone.`}
                >
                    <Form {...TaskController.destroy.form(task.id)}>
                        {({ processing }) => (
                            <ResponsiveDialogFooter>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    Delete task
                                </Button>
                                <ResponsiveDialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </ResponsiveDialogClose>
                            </ResponsiveDialogFooter>
                        )}
                    </Form>
                </ResponsiveDialog>
            )}
        </div>
    );
}
