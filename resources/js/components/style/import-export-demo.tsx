import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import Actions from '@/components/shared/actions';
import FilePicker from '@/components/shared/file-picker';
import FormField from '@/components/shared/form-field';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';

/**
 * Moving a list in and out as a CSV file. Export is a plain download of the
 * list as the user sees it. Import is upload then import: the backend checks
 * every row, and when any is wrong nothing is imported and the errors are
 * toasts, at most five and then "And N more errors" (the shared
 * `useValidationErrorToasts` cuts them). The demo's import always fails with
 * every row error it is given, through the event Inertia fires.
 */
export default function ImportExportDemo({
    rowErrors,
}: {
    /** Every row error the backend would send, in row order. */
    rowErrors: string[];
}) {
    const [open, setOpen] = useState(false);

    return (
        <div className="grid gap-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading title={t('Tasks')} />
                <Actions data-testid="list-actions">
                    <ResponsiveDialog
                        open={open}
                        onOpenChange={setOpen}
                        trigger={
                            <Button variant="outline">{t('Import')}</Button>
                        }
                        title={t('Import tasks')}
                    >
                        <form
                            noValidate
                            onSubmit={(event) => {
                                event.preventDefault();
                                document.dispatchEvent(
                                    new CustomEvent('inertia:error', {
                                        detail: {
                                            errors: Object.fromEntries(
                                                rowErrors.map(
                                                    (message, index) => [
                                                        `rows.${index + 2}.row`,
                                                        message,
                                                    ],
                                                ),
                                            ),
                                        },
                                    }),
                                );
                            }}
                            className="flex min-h-0 flex-1 flex-col gap-6"
                        >
                            <ResponsiveDialogBody>
                                <FormField
                                    label={t('CSV file')}
                                    htmlFor="import-demo-file"
                                >
                                    <FilePicker
                                        id="import-demo-file"
                                        accept=".csv,text/csv"
                                    />
                                </FormField>
                                <Button
                                    type="button"
                                    variant="link"
                                    size="inline"
                                    className="w-fit"
                                    onClick={() =>
                                        toast(
                                            t(
                                                'The template is a CSV file with the column names.',
                                            ),
                                        )
                                    }
                                >
                                    {t('Download template')}
                                </Button>
                            </ResponsiveDialogBody>
                            <ResponsiveDialogFooter>
                                <ResponsiveDialogClose asChild>
                                    <Button type="button" variant="outline">
                                        {t('Cancel')}
                                    </Button>
                                </ResponsiveDialogClose>
                                <Button type="submit">{t('Import')}</Button>
                            </ResponsiveDialogFooter>
                        </form>
                    </ResponsiveDialog>
                    <Button
                        variant="outline"
                        onClick={() =>
                            toast(
                                t(
                                    'The export downloads tasks.csv, as the list is shown.',
                                ),
                            )
                        }
                    >
                        {t('Export')}
                    </Button>
                    <Button>{t('Add task')}</Button>
                </Actions>
            </div>
        </div>
    );
}
