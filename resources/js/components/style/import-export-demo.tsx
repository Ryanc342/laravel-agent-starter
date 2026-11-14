import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import Actions from '@/components/shared/actions';
import FormField from '@/components/shared/form-field';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

/**
 * Moving a list in and out as a CSV file. Export is a plain download of the
 * list as the user sees it. Import is upload then import: the backend checks
 * every row, and when any is wrong nothing is imported and the errors are
 * toasts, at most five and then "And N more errors". The demo's import always
 * finds the errors it is given.
 */
export default function ImportExportDemo({
    rowErrors,
    more,
}: {
    /** The first five row errors the backend would send. */
    rowErrors: string[];
    /** The line for the rest ("And 2 more errors."). */
    more: string;
}) {
    const [open, setOpen] = useState(false);

    return (
        <div className="grid gap-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading title="Tasks" />
                <Actions data-testid="list-actions">
                    <ResponsiveDialog
                        open={open}
                        onOpenChange={setOpen}
                        trigger={<Button variant="outline">Import</Button>}
                        title="Import tasks"
                    >
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                [...rowErrors, more].forEach((message, index) =>
                                    toast.error(message, {
                                        id: `import-error-${index}`,
                                    }),
                                );
                            }}
                            className="flex min-h-0 flex-1 flex-col gap-6"
                        >
                            <ResponsiveDialogBody>
                                <FormField
                                    label="CSV file"
                                    htmlFor="import-demo-file"
                                >
                                    <Input
                                        id="import-demo-file"
                                        type="file"
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
                                            'The template is a CSV file with the column names.',
                                        )
                                    }
                                >
                                    Download template
                                </Button>
                            </ResponsiveDialogBody>
                            <ResponsiveDialogFooter>
                                <ResponsiveDialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </ResponsiveDialogClose>
                                <Button type="submit">Import</Button>
                            </ResponsiveDialogFooter>
                        </form>
                    </ResponsiveDialog>
                    <Button
                        variant="outline"
                        onClick={() =>
                            toast(
                                'The export downloads tasks.csv, as the list is shown.',
                            )
                        }
                    >
                        Export
                    </Button>
                    <Button>Add task</Button>
                </Actions>
            </div>
        </div>
    );
}
