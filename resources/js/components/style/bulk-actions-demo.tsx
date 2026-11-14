import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import BulkActionBar from '@/components/shared/bulk-action-bar';
import ConfirmDialog, {
    ConfirmDialogActions,
} from '@/components/shared/confirm-dialog';
import ResponsiveTable from '@/components/shared/responsive-table';
import type { TableColumn } from '@/components/shared/responsive-table';
import type { ExampleTask } from '@/components/style/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useRowSelection } from '@/hooks/use-row-selection';
import { formatDate } from '@/lib/format';
import { t, tChoice } from '@/lib/i18n';

/** Built when the demo renders, because `t()` needs the language, which is set after the module loads. */
const columns = (): TableColumn<ExampleTask>[] => [
    {
        header: t('Title'),
        className: 'max-w-80 truncate font-medium',
        cell: (task) => task.title,
    },
    {
        header: t('Status'),
        hideLabelOnCard: true,
        cell: (task) => <Badge variant="secondary">{task.status}</Badge>,
    },
    {
        header: t('Due date'),
        cell: (task) =>
            task.due_on ? formatDate(task.due_on) : t('No due date'),
    },
];

/**
 * Ticking rows: a checkbox column (a checkbox on each card on a phone), and
 * the bar that floats at the bottom of the page. When the header checkbox has
 * ticked the whole page and more results exist, the bar offers "Select all N
 * results". Delete asks first, naming the count. The demo changes nothing.
 */
export default function BulkActionsDemo({
    tasks,
    total,
}: {
    tasks: ExampleTask[];
    /** How many results the list has in all. */
    total: number;
}) {
    const { url } = usePage();
    const selection = useRowSelection(
        tasks.map((task) => task.id),
        url,
    );
    const [confirming, setConfirming] = useState(false);
    const count = selection.allResults ? total : selection.count;

    return (
        <div className="grid gap-4">
            <Heading title={t('Tasks')} />
            <ResponsiveTable
                columns={columns()}
                rows={tasks}
                getRowKey={(task) => task.id}
                emptyMessage={t('You have no tasks yet.')}
                selection={{
                    pageState: selection.pageState,
                    onTogglePage: selection.togglePage,
                    isSelected: (task) => selection.isSelected(task.id),
                    onToggle: (task) => selection.toggle(task.id),
                    rowLabel: (task) => `Select ${task.title}`,
                }}
            />
            <BulkActionBar
                count={count}
                allResults={selection.allResults}
                total={total}
                onSelectPage={
                    selection.pageState !== 'all' && !selection.allResults
                        ? selection.selectPage
                        : undefined
                }
                onSelectAllResults={
                    selection.pageState === 'all' &&
                    total > tasks.length &&
                    !selection.allResults
                        ? selection.selectAllResults
                        : undefined
                }
                onClear={selection.clear}
            >
                <Button
                    onClick={() => {
                        toast.success(
                            tChoice(
                                ':count task completed.|:count tasks completed.',
                                count,
                            ),
                        );
                        selection.clear();
                    }}
                >
                    {t('Complete')}
                </Button>
                <Button variant="outline" onClick={() => setConfirming(true)}>
                    {t('Delete')}
                </Button>
            </BulkActionBar>

            <ConfirmDialog
                open={confirming}
                onOpenChange={setConfirming}
                title={tChoice(
                    'Delete :count task?|Delete :count tasks?',
                    count,
                )}
                consequence={t('They will be deleted. This cannot be undone.')}
            >
                <ConfirmDialogActions>
                    <Button
                        variant="destructive"
                        onClick={() => {
                            toast.success(
                                tChoice(
                                    ':count task deleted.|:count tasks deleted.',
                                    count,
                                ),
                            );
                            setConfirming(false);
                            selection.clear();
                        }}
                    >
                        {tChoice(
                            'Delete :count task|Delete :count tasks',
                            count,
                        )}
                    </Button>
                </ConfirmDialogActions>
            </ConfirmDialog>
        </div>
    );
}
