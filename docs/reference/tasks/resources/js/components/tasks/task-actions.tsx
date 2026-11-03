import { Form } from '@inertiajs/react';
import TaskCompletionController from '@/actions/App/Http/Controllers/TaskCompletionController';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { Task } from '@/types/task';

/**
 * The task page's actions. Every button is shown from task.can.*, which the
 * backend calculates.
 */
export default function TaskActions({
    task,
    onEdit,
}: {
    task: Task;
    onEdit: () => void;
}) {
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
                <Button variant="outline" onClick={onEdit}>
                    Edit task
                </Button>
            )}
            {task.can.delete && (
                <Dialog>
                    <DialogTrigger asChild>
                        <Button variant="outline">Delete task</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>Delete this task?</DialogTitle>
                        <DialogDescription>
                            “{task.title}” will be deleted. This cannot be
                            undone.
                        </DialogDescription>
                        <Form {...TaskController.destroy.form(task.id)}>
                            {({ processing }) => (
                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        Delete task
                                    </Button>
                                </DialogFooter>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            )}
        </div>
    );
}
