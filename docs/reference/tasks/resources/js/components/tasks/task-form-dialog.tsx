import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import TaskFields from '@/components/tasks/task-fields';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { t } from '@/lib/i18n';
import type { Task } from '@/types/task';

type TaskFormDialogProps = {
    /** The task to edit; leave it out to create a task. */
    task?: Task;
    /** The button that opens the dialog. */
    trigger: ReactNode;
};

/**
 * Create and edit a task in one dialog: a centred dialog on desktop, a sheet
 * from the bottom on a phone. The dialog closes when the server accepts the
 * form (the controller flashes the toast); validation errors keep it open.
 */
export default function TaskFormDialog({ task, trigger }: TaskFormDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={setOpen}
            trigger={trigger}
            title={task ? t('Edit task') : t('Add task')}
        >
            <Form
                {...(task
                    ? TaskController.update.form(task.id)
                    : TaskController.store.form())}
                options={{ preserveScroll: true }}
                onSuccess={() => setOpen(false)}
                className="flex min-h-0 flex-1 flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <ResponsiveDialogBody>
                            <TaskFields task={task} errors={errors} />
                        </ResponsiveDialogBody>

                        <ResponsiveDialogFooter>
                            <ResponsiveDialogClose asChild>
                                <Button type="button" variant="outline">
                                    {t('Cancel')}
                                </Button>
                            </ResponsiveDialogClose>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                {task ? t('Save changes') : t('Add task')}
                            </Button>
                        </ResponsiveDialogFooter>
                    </>
                )}
            </Form>
        </ResponsiveDialog>
    );
}
