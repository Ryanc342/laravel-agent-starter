import DatePicker from '@/components/shared/date-picker';
import FormField from '@/components/shared/form-field';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import { Specimen } from '@/components/style/style-section';
import type { ExampleTask } from '@/components/style/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

/** The fields and buttons of a form dialog: fields in the body, buttons in the footer, main button first. */
function DemoForm({
    task,
    error,
    submitLabel,
}: {
    task?: ExampleTask;
    error?: string;
    submitLabel: string;
}) {
    return (
        <form
            onSubmit={(event) => event.preventDefault()}
            className="flex min-h-0 flex-1 flex-col gap-6"
        >
            <ResponsiveDialogBody>
                <FormField
                    label="Title"
                    htmlFor="form-demo-title"
                    error={error}
                >
                    <Input id="form-demo-title" defaultValue={task?.title} />
                </FormField>
                <FormField label="Due date" htmlFor="form-demo-due">
                    <DatePicker
                        id="form-demo-due"
                        defaultValue={task?.due_on ?? ''}
                    />
                </FormField>
            </ResponsiveDialogBody>

            <ResponsiveDialogFooter>
                <ResponsiveDialogClose asChild>
                    <Button type="button" variant="outline">
                        Cancel
                    </Button>
                </ResponsiveDialogClose>
                <Button type="submit">{submitLabel}</Button>
            </ResponsiveDialogFooter>
        </form>
    );
}

/** Creating and editing a record: always a dialog, never a form above the list. */
export default function FormDemo({
    task,
    requiredMessage,
}: {
    task: ExampleTask;
    requiredMessage: string;
}) {
    return (
        <Specimen label="Open each one: create, edit (filled with the record), and a validation error (the dialog stays open)">
            <ResponsiveDialog
                trigger={<Button>Add task</Button>}
                title="Add task"
            >
                <DemoForm submitLabel="Add task" />
            </ResponsiveDialog>

            <ResponsiveDialog
                trigger={<Button variant="outline">Edit task</Button>}
                title="Edit task"
            >
                <DemoForm task={task} submitLabel="Save changes" />
            </ResponsiveDialog>

            <ResponsiveDialog
                trigger={<Button variant="outline">With an error</Button>}
                title="Add task"
            >
                <DemoForm submitLabel="Add task" error={requiredMessage} />
            </ResponsiveDialog>
        </Specimen>
    );
}
