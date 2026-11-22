import { useState } from 'react';
import Heading from '@/components/heading';
import ConfirmDialog, {
    ConfirmDialogActions,
} from '@/components/shared/confirm-dialog';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import ResponsiveTable from '@/components/shared/responsive-table';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import { DemoState } from '@/components/style/style-section';
import { Button } from '@/components/ui/button';

export type ExampleCategory = { id: number; name: string; expenses: number };

/** The edit dialog: the name as typed, and the server's error when it sent the form back. */
function EditDialog({
    category,
    error,
    onClose,
}: {
    category: ExampleCategory | null;
    error?: string;
    onClose: () => void;
}) {
    return (
        <ResponsiveDialog
            open={category !== null}
            onOpenChange={(open) => !open && onClose()}
            title="Edit category"
        >
            {/* A form, so Enter in the field sends it like the Save button. */}
            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    onClose();
                }}
                className="flex min-h-0 flex-1 flex-col gap-6"
            >
                <ResponsiveDialogBody>
                    <TextField
                        label="Category name"
                        name="name"
                        required
                        defaultValue={error ? '' : category?.name}
                        error={error}
                    />
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose asChild>
                        <Button variant="outline">Cancel</Button>
                    </ResponsiveDialogClose>
                    <SubmitButton sending={false}>Save</SubmitButton>
                </ResponsiveDialogFooter>
            </form>
        </ResponsiveDialog>
    );
}

/**
 * A list whose records have no detail page: each row's own actions sit in one
 * three-dot menu (RowActions, through ResponsiveTable), Edit first and Delete
 * last with its confirmation. The second state is the edit dialog as the
 * server sent it back, with the error from the demo data (`nameError`).
 */
export default function RowActionsDemo({
    categories,
    nameError,
}: {
    categories: ExampleCategory[];
    nameError: string;
}) {
    const [editing, setEditing] = useState<ExampleCategory | null>(null);
    const [failed, setFailed] = useState<ExampleCategory | null>(null);
    const [deleting, setDeleting] = useState<ExampleCategory | null>(null);

    return (
        <div className="grid gap-6">
            <Heading title="Expense categories" />

            <DemoState label="The list: open a row's menu">
                <ResponsiveTable
                    columns={[
                        {
                            header: 'Category',
                            className: 'font-medium',
                            cell: (category) => category.name,
                        },
                        {
                            header: 'Expenses',
                            cell: (category) => category.expenses,
                        },
                    ]}
                    rows={categories}
                    getRowKey={(category) => category.id}
                    emptyMessage="No categories yet."
                    rowActions={(category) => [
                        { label: 'Edit', onSelect: () => setEditing(category) },
                        {
                            label: 'Delete',
                            destructive: true,
                            onSelect: () => setDeleting(category),
                        },
                    ]}
                />
            </DemoState>

            <DemoState label="Edit, sent back with an error">
                <div>
                    <Button
                        variant="outline"
                        onClick={() => setFailed(categories[0])}
                    >
                        Edit
                    </Button>
                </div>
            </DemoState>

            <EditDialog category={editing} onClose={() => setEditing(null)} />
            <EditDialog
                category={failed}
                error={nameError}
                onClose={() => setFailed(null)}
            />

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Delete this category?"
                consequence={`“${deleting?.name ?? ''}” will be deleted. This cannot be undone.`}
            >
                <ConfirmDialogActions>
                    <Button
                        variant="destructive"
                        onClick={() => setDeleting(null)}
                    >
                        Delete
                    </Button>
                </ConfirmDialogActions>
            </ConfirmDialog>
        </div>
    );
}
