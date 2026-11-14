import { Form, Link } from '@inertiajs/react';
import TaskCompletionController from '@/actions/App/Http/Controllers/TaskCompletionController';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import ResponsiveDialog, {
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import TaskFormDialog from '@/components/tasks/task-form-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { print as printTask } from '@/routes/tasks';
import type { Task } from '@/types/task';

/**
 * The task page's actions, at the right end of the header, the main one
 * (Complete) last. Every button is shown from task.can.*, which the backend
 * calculates.
 */
export default function TaskActions({ task }: { task: Task }) {
    return (
        <div className="ml-auto flex flex-wrap justify-end gap-2">
            {task.can.update && (
                <TaskFormDialog
                    task={task}
                    trigger={<Button variant="outline">Edit task</Button>}
                />
            )}
            <Button variant="outline" asChild>
                <Link href={printTask(task.id)}>Print</Link>
            </Button>
            {task.can.delete && (
                <ResponsiveDialog
                    trigger={<Button variant="outline">Delete task</Button>}
                    title="Delete this task?"
                    description={`“${task.title}” will be deleted. This cannot be undone.`}
                >
                    <Form {...TaskController.destroy.form(task.id)}>
                        {({ processing }) => (
                            <ResponsiveDialogFooter>
                                <ResponsiveDialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </ResponsiveDialogClose>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    Delete task
                                </Button>
                            </ResponsiveDialogFooter>
                        )}
                    </Form>
                </ResponsiveDialog>
            )}
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
        </div>
    );
}
