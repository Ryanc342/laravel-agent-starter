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
import { t } from '@/lib/i18n';

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
            noValidate
            onSubmit={(event) => event.preventDefault()}
            className="flex min-h-0 flex-1 flex-col gap-6"
        >
            <ResponsiveDialogBody>
                <FormField
                    label={t('Title')}
                    htmlFor="form-demo-title"
                    error={error}
                >
                    <Input id="form-demo-title" defaultValue={task?.title} />
                </FormField>
                <FormField label={t('Due date')} htmlFor="form-demo-due">
                    <DatePicker
                        id="form-demo-due"
                        defaultValue={task?.due_on ?? ''}
                    />
                </FormField>
            </ResponsiveDialogBody>

            <ResponsiveDialogFooter>
                <ResponsiveDialogClose asChild>
                    <Button type="button" variant="outline">
                        {t('Cancel')}
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
        <Specimen
            label={t(
                'Open each one: create, edit (filled with the record), and a validation error (the dialog stays open)',
            )}
        >
            <ResponsiveDialog
                trigger={<Button>{t('Add task')}</Button>}
                title={t('Add task')}
            >
                <DemoForm submitLabel={t('Add task')} />
            </ResponsiveDialog>

            <ResponsiveDialog
                trigger={<Button variant="outline">{t('Edit task')}</Button>}
                title={t('Edit task')}
            >
                <DemoForm task={task} submitLabel={t('Save changes')} />
            </ResponsiveDialog>

            <ResponsiveDialog
                trigger={
                    <Button variant="outline">{t('With an error')}</Button>
                }
                title={t('Add task')}
            >
                <DemoForm submitLabel={t('Add task')} error={requiredMessage} />
            </ResponsiveDialog>
        </Specimen>
    );
}
