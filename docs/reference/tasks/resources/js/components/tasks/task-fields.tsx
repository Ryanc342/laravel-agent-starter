import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Task } from '@/types/task';

type TaskFieldsProps = {
    task?: Task;
    errors: Partial<Record<string, string>>;
};

/**
 * The task form fields, shared by the create form and the edit form.
 * Validation lives in the FormRequest; this only shows the server's errors.
 */
export default function TaskFields({ task, errors }: TaskFieldsProps) {
    const idPrefix = task ? `task-${task.id}` : 'new-task';

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-title`}>Title</Label>
                <Input
                    id={`${idPrefix}-title`}
                    name="title"
                    defaultValue={task?.title}
                    required
                    maxLength={255}
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-due-on`}>Due date</Label>
                <Input
                    id={`${idPrefix}-due-on`}
                    name="due_on"
                    type="date"
                    defaultValue={task?.due_on ?? ''}
                />
                <InputError message={errors.due_on} />
            </div>
        </>
    );
}
