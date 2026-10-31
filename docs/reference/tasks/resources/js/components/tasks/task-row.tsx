import { Form } from '@inertiajs/react';
import { useState } from 'react';
import TaskCompletionController from '@/actions/App/Http/Controllers/TaskCompletionController';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import TaskFields from '@/components/tasks/task-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Task } from '@/types/task';

/**
 * One task. Every button is shown from task.can.*, which the backend calculates.
 */
export default function TaskRow({ task }: { task: Task }) {
    const [editing, setEditing] = useState(false);

    if (editing) {
        return (
            <li className="rounded-lg border p-4">
                <Form
                    {...TaskController.update.form(task.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setEditing(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <TaskFields task={task} errors={errors} />
                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Save
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
            </li>
        );
    }

    return (
        <li className="flex items-center justify-between gap-4 rounded-lg border p-4">
            <div className="grid gap-1">
                <span className="font-medium">{task.title}</span>
                <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                    <Badge variant="secondary">{task.status_label}</Badge>
                    {task.due_on && <span>Due {task.due_on}</span>}
                    {task.is_overdue && (
                        <Badge variant="destructive">Overdue</Badge>
                    )}
                </div>
            </div>

            <div className="flex gap-2">
                {task.can.complete && (
                    <Form
                        {...TaskCompletionController.store.form(task.id)}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                            >
                                Complete
                            </Button>
                        )}
                    </Form>
                )}
                {task.can.update && (
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => setEditing(true)}
                    >
                        Edit
                    </Button>
                )}
                {task.can.delete && (
                    <Form
                        {...TaskController.destroy.form(task.id)}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                size="sm"
                                variant="destructive"
                                disabled={processing}
                            >
                                Delete
                            </Button>
                        )}
                    </Form>
                )}
            </div>
        </li>
    );
}
