import DatePicker from '@/components/shared/date-picker';
import FormField from '@/components/shared/form-field';
import { Input } from '@/components/ui/input';
import type { Task } from '@/types/task';

type TaskFieldsProps = {
    task?: Task;
    errors: Partial<Record<string, string>>;
};

/**
 * The task form fields, shared by the create and the edit dialog.
 * Validation lives in the FormRequest; this only shows the server's errors.
 */
export default function TaskFields({ task, errors }: TaskFieldsProps) {
    const idPrefix = task ? `task-${task.id}` : 'new-task';

    return (
        <>
            <FormField
                label="Title"
                htmlFor={`${idPrefix}-title`}
                error={errors.title}
            >
                <Input
                    id={`${idPrefix}-title`}
                    name="title"
                    defaultValue={task?.title}
                    required
                    maxLength={255}
                />
            </FormField>

            <FormField
                label="Due date"
                htmlFor={`${idPrefix}-due-on`}
                error={errors.due_on}
            >
                <DatePicker
                    id={`${idPrefix}-due-on`}
                    name="due_on"
                    defaultValue={task?.due_on ?? ''}
                />
            </FormField>
        </>
    );
}
