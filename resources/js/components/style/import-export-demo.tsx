import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import Actions from '@/components/shared/actions';
import ExportButton from '@/components/shared/export-button';
import FilePicker from '@/components/shared/file-picker';
import FormField from '@/components/shared/form-field';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import SubmitButton from '@/components/shared/submit-button';
import { DemoState } from '@/components/style/style-section';
import { Button } from '@/components/ui/button';

/**
 * Moving a list in and out as a CSV file. Export is a plain download of the
 * list as the user sees it: in an app, ExportButton on a list whose
 * Route::listing() allows export. Import is upload then import: in an app, an
 * operation whose csvRows() names the file. The backend checks every row, and
 * when any is wrong nothing is imported and the errors are toasts, at most
 * five and then "And N more errors" (the shared useValidationErrorToasts cuts
 * them). The second state shows those toasts with the row errors of the demo
 * data, as Inertia reports them.
 */
export default function ImportExportDemo({
    rowErrors,
}: {
    /** Every row error the backend sent, in row order. */
    rowErrors: string[];
}) {
    const [open, setOpen] = useState(false);

    const showErrors = () =>
        document.dispatchEvent(
            new CustomEvent('inertia:error', {
                detail: {
                    errors: Object.fromEntries(
                        rowErrors.map((message, index) => [
                            `rows.${index + 2}.row`,
                            message,
                        ]),
                    ),
                },
            }),
        );

    return (
        <div className="grid gap-6">
            <DemoState label="The list's actions">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title="Tasks" />
                    <Actions>
                        <ResponsiveDialog
                            open={open}
                            onOpenChange={setOpen}
                            trigger={<Button variant="outline">Import</Button>}
                            title="Import tasks"
                        >
                            <form
                                noValidate
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    setOpen(false);
                                }}
                                className="flex min-h-0 flex-1 flex-col gap-6"
                            >
                                <ResponsiveDialogBody>
                                    <FormField
                                        label="CSV file"
                                        htmlFor="import-demo-file"
                                    >
                                        <FilePicker
                                            id="import-demo-file"
                                            accept=".csv,text/csv"
                                        />
                                    </FormField>
                                    <Button
                                        variant="link"
                                        size="inline"
                                        className="w-fit"
                                        onClick={() =>
                                            toast(
                                                'The template is a CSV file with the column names.',
                                            )
                                        }
                                    >
                                        Download template
                                    </Button>
                                </ResponsiveDialogBody>
                                <ResponsiveDialogFooter>
                                    <ResponsiveDialogClose asChild>
                                        <Button variant="outline">
                                            Cancel
                                        </Button>
                                    </ResponsiveDialogClose>
                                    <SubmitButton sending={false}>
                                        Import
                                    </SubmitButton>
                                </ResponsiveDialogFooter>
                            </form>
                        </ResponsiveDialog>
                        {/* The demo keeps the download: the style page is not a list. */}
                        <div
                            className="contents"
                            onClickCapture={(event) => {
                                event.preventDefault();
                                toast(
                                    'The export downloads tasks.csv, as the list is shown.',
                                );
                            }}
                        >
                            <ExportButton>Export</ExportButton>
                        </div>
                        <Button>Add</Button>
                    </Actions>
                </div>
            </DemoState>

            <DemoState label="A failed import: nothing is imported and the errors are toasts">
                <div>
                    <Button variant="outline" onClick={showErrors}>
                        Show the errors
                    </Button>
                </div>
            </DemoState>
        </div>
    );
}
