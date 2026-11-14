import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import TaskImportController from '@/actions/App/Http/Controllers/TaskImportController';
import TaskImportTemplateController from '@/actions/App/Http/Controllers/TaskImportTemplateController';
import FormField from '@/components/shared/form-field';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

/**
 * Import tasks from a CSV file: upload, and the backend checks every row. If
 * any row is wrong nothing is imported and each error (at most five, then "And
 * N more errors") is a toast, so the dialog stays open; on success the
 * controller flashes "Imported 41 tasks" and the dialog closes.
 */
export default function TaskImportDialog({ trigger }: { trigger: ReactNode }) {
    const [open, setOpen] = useState(false);

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={setOpen}
            trigger={trigger}
            title="Import tasks"
        >
            <Form
                {...TaskImportController.store.form()}
                options={{ preserveScroll: true }}
                onSuccess={() => setOpen(false)}
                className="flex min-h-0 flex-1 flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <ResponsiveDialogBody>
                            <FormField
                                label="CSV file"
                                htmlFor="task-import-file"
                                error={errors.file}
                            >
                                <Input
                                    id="task-import-file"
                                    name="file"
                                    type="file"
                                    accept=".csv,text/csv"
                                    required
                                />
                            </FormField>
                            <Button
                                variant="link"
                                size="inline"
                                className="w-fit"
                                asChild
                            >
                                <a href={TaskImportTemplateController.url()}>
                                    Download template
                                </a>
                            </Button>
                        </ResponsiveDialogBody>

                        <ResponsiveDialogFooter>
                            <ResponsiveDialogClose asChild>
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </ResponsiveDialogClose>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Import
                            </Button>
                        </ResponsiveDialogFooter>
                    </>
                )}
            </Form>
        </ResponsiveDialog>
    );
}
