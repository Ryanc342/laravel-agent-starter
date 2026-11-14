import FormField from '@/components/shared/form-field';
import Pagination from '@/components/shared/pagination';
import ConfirmDialog, {
    ConfirmDialogActions,
} from '@/components/shared/confirm-dialog';
import ResponsiveTable from '@/components/shared/responsive-table';
import { Specimen } from '@/components/style/style-section';
import type { ExampleTask } from '@/components/style/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/format';
import type { ResourceCollection } from '@/types/resource';
import { t } from '@/lib/i18n';

/**
 * The blocks in components/shared. EmptyState, ErrorState and LoadingState are
 * under States, FeedRow under Feed, QuickLinks under Dashboard and ErrorContent
 * under Error pages.
 */
export default function SharedDemo({
    tasks,
    pagination,
}: {
    tasks: ExampleTask[];
    pagination: ResourceCollection<ExampleTask>;
}) {
    return (
        <div className="grid gap-8">
            <Specimen
                label={t(
                    'FormField: the label sits beside the input in a wide form and above it in a narrow one; a field with an error has a red border and its message is a toast (open the dialog under Form)',
                )}
            >
                <div className="grid w-full max-w-xl gap-6">
                    <FormField label={t('Title')} htmlFor="style-field-title">
                        <Input
                            id="style-field-title"
                            defaultValue="Write the report"
                        />
                    </FormField>
                    <FormField label={t('Due date')} htmlFor="style-field-due">
                        <Input
                            id="style-field-due"
                            defaultValue="31/02/2026"
                            aria-invalid
                        />
                    </FormField>
                </div>
            </Specimen>

            <Specimen
                label={t(
                    'FormField in a narrow column (the label moves above the input) and with a hidden label (a search box)',
                )}
            >
                <div className="grid w-full max-w-sm gap-6">
                    <FormField
                        label={t('Email address')}
                        htmlFor="style-field-email"
                    >
                        <Input
                            id="style-field-email"
                            placeholder={t('email@example.com')}
                        />
                    </FormField>
                    <FormField
                        label={t('Search tasks')}
                        htmlFor="style-field-search"
                        hideLabel
                    >
                        <Input
                            id="style-field-search"
                            placeholder={t('Search tasks')}
                        />
                    </FormField>
                </div>
            </Specimen>

            <Specimen
                label={t(
                    'ConfirmDialog (a ResponsiveDialog): a centred dialog on desktop, a sheet from the bottom on a phone (open it, then narrow the window)',
                )}
            >
                <ConfirmDialog
                    trigger={
                        <Button variant="outline">{t('Delete task')}</Button>
                    }
                    title={t('Delete this task?')}
                    consequence={t(
                        '“Write the report” will be deleted. This cannot be undone.',
                    )}
                >
                    <ConfirmDialogActions>
                        <Button variant="destructive">
                            {t('Delete task')}
                        </Button>
                    </ConfirmDialogActions>
                </ConfirmDialog>
            </Specimen>

            <div className="grid gap-3">
                <p className="text-sm text-muted-foreground">
                    {t(
                        'ResponsiveTable: a table on desktop, one card per row on a phone',
                    )}
                </p>
                <ResponsiveTable
                    columns={[
                        { header: t('Title'), cell: (task) => task.title },
                        {
                            header: t('Status'),
                            hideLabelOnCard: true,
                            cell: (task) => (
                                <Badge variant="secondary">{task.status}</Badge>
                            ),
                        },
                        {
                            header: t('Due date'),
                            cell: (task) =>
                                task.due_on
                                    ? formatDate(task.due_on)
                                    : t('No due date'),
                        },
                    ]}
                    rows={tasks.slice(0, 2)}
                    getRowKey={(task) => task.id}
                    emptyMessage={t('You have no tasks yet.')}
                />
            </div>

            <div className="grid gap-3">
                <p className="text-sm text-muted-foreground">
                    {t('ResponsiveTable with no records: the header row stays')}
                </p>
                <ResponsiveTable
                    columns={[
                        { header: t('Title'), cell: (task) => task.title },
                        { header: t('Status'), cell: (task) => task.status },
                        {
                            header: t('Due date'),
                            cell: (task) =>
                                task.due_on
                                    ? formatDate(task.due_on)
                                    : t('No due date'),
                        },
                    ]}
                    rows={tasks.slice(0, 0)}
                    getRowKey={(task) => task.id}
                    emptyMessage={t('You have no tasks yet.')}
                />
            </div>

            <div className="grid gap-3">
                <p className="text-sm text-muted-foreground">
                    {t(
                        'Pagination: the page links when the list has more than one page, and the rows per page (10, 20, 50, 100) next to them',
                    )}
                </p>
                <Pagination collection={pagination} />
            </div>
        </div>
    );
}
