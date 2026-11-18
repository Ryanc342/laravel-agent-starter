import { useRef, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import ConfirmDialog, {
    ConfirmDialogActions,
} from '@/components/shared/confirm-dialog';
import FormField from '@/components/shared/form-field';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import ResponsiveTable from '@/components/shared/responsive-table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';

export type ExampleCategory = { id: number; name: string; expenses: number };

/**
 * A list whose records have no detail page (patterns/list.md): each row's own
 * actions sit in one three-dot menu (`RowActions`, through `ResponsiveTable`),
 * Edit first and Delete last with its confirmation. React decides nothing: the
 * name is checked by `answer`; on a real page the FormRequest answers, and here
 * the page's example data stands in for it.
 */
export default function RowActionsDemo({
    categories,
    answer,
}: {
    categories: ExampleCategory[];
    answer: (name: string) => string | null;
}) {
    const [rows, setRows] = useState(categories);
    const [editing, setEditing] = useState<ExampleCategory | null>(null);
    const [deleting, setDeleting] = useState<ExampleCategory | null>(null);
    const [name, setName] = useState('');
    const [error, setError] = useState<string | null>(null);
    const nameInput = useRef<HTMLInputElement>(null);

    const edit = (category: ExampleCategory) => {
        setEditing(category);
        setName(category.name);
        setError(null);
    };

    const save = () => {
        const message = answer(name);

        setError(message);

        if (message) {
            toast.error(message, { id: 'category-name' });
            // As after a failed submit: the field with the error gets focus.
            nameInput.current?.focus();

            return;
        }

        setRows(
            rows.map((row) =>
                row.id === editing?.id ? { ...row, name: name.trim() } : row,
            ),
        );
        setEditing(null);
        toast.success(t('Category saved.'));
    };

    const remove = () => {
        setRows(rows.filter((row) => row.id !== deleting?.id));
        setDeleting(null);
        toast.success(t('Category deleted.'));
    };

    return (
        <div className="grid gap-6">
            <Heading title={t('Expense categories')} />

            <ResponsiveTable
                columns={[
                    {
                        header: t('Category'),
                        className: 'font-medium',
                        cell: (category) => category.name,
                    },
                    {
                        header: t('Expenses'),
                        cell: (category) => category.expenses,
                    },
                ]}
                rows={rows}
                getRowKey={(category) => category.id}
                emptyMessage={t('No categories yet.')}
                rowActions={(category) => [
                    { label: t('Edit'), onSelect: () => edit(category) },
                    {
                        label: t('Delete'),
                        destructive: true,
                        onSelect: () => setDeleting(category),
                    },
                ]}
            />

            <ResponsiveDialog
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
                title={t('Edit category')}
            >
                {/* A form, so Enter in the field saves like the Save button. */}
                <form
                    noValidate
                    onSubmit={(event) => {
                        event.preventDefault();
                        save();
                    }}
                    className="flex min-h-0 flex-1 flex-col gap-6"
                >
                    <ResponsiveDialogBody>
                        <FormField
                            label={t('Category name')}
                            htmlFor="category-name"
                            error={error ?? undefined}
                        >
                            <Input
                                ref={nameInput}
                                id="category-name"
                                required
                                value={name}
                                onChange={(event) =>
                                    setName(event.target.value)
                                }
                            />
                        </FormField>
                    </ResponsiveDialogBody>
                    <ResponsiveDialogFooter>
                        <ResponsiveDialogClose asChild>
                            <Button type="button" variant="outline">
                                {t('Cancel')}
                            </Button>
                        </ResponsiveDialogClose>
                        <Button type="submit">{t('Save')}</Button>
                    </ResponsiveDialogFooter>
                </form>
            </ResponsiveDialog>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
                title={t('Delete this category?')}
                consequence={t(
                    '“:name” will be deleted. This cannot be undone.',
                    { name: deleting?.name ?? '' },
                )}
            >
                <ConfirmDialogActions>
                    <Button variant="destructive" onClick={remove}>
                        {t('Delete category')}
                    </Button>
                </ConfirmDialogActions>
            </ConfirmDialog>
        </div>
    );
}
