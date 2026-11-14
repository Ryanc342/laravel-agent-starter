import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BulkTaskCompletionController from '@/actions/App/Http/Controllers/BulkTaskCompletionController';
import BulkTaskDeletionController from '@/actions/App/Http/Controllers/BulkTaskDeletionController';
import BulkActionBar from '@/components/shared/bulk-action-bar';
import ConfirmDialog, {
    ConfirmDialogActions,
} from '@/components/shared/confirm-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { useRowSelection } from '@/hooks/use-row-selection';
import { t, tChoice } from '@/lib/i18n';
import { bulkPayload } from '@/lib/list-query';

/** The list's own search and filters, which "all results" carries to the backend. */
const filters = {
    single: ['search', 'due_from', 'due_to'],
    multiple: ['status'],
};

/**
 * The floating bar of the task list: Complete and Delete for the ticked rows,
 * or for every result of the list's search and filters after "Select all N
 * results". The backend decides what each task allows; the buttons only send
 * the selection. Delete asks first, naming the count.
 */
export default function TaskBulkActions({
    selection,
    total,
    pageSize,
}: {
    selection: ReturnType<typeof useRowSelection<number>>;
    /** How many tasks the list has in all, over every page. */
    total: number;
    /** How many tasks this page shows. */
    pageSize: number;
}) {
    const { url } = usePage();
    const [processing, setProcessing] = useState(false);
    const [confirming, setConfirming] = useState(false);

    const count = selection.allResults ? total : selection.count;
    const payload = bulkPayload(url, selection, filters);
    const options = {
        preserveScroll: true,
        onStart: () => setProcessing(true),
        onFinish: () => setProcessing(false),
        onSuccess: () => {
            setConfirming(false);
            selection.clear();
        },
    };
    const canSelectAll =
        selection.pageState === 'all' &&
        total > pageSize &&
        !selection.allResults;

    return (
        <>
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
                    canSelectAll ? selection.selectAllResults : undefined
                }
                onClear={selection.clear}
            >
                <Button
                    disabled={processing}
                    onClick={() =>
                        router.post(
                            BulkTaskCompletionController.store.url(),
                            payload,
                            options,
                        )
                    }
                >
                    {t('Complete')}
                </Button>
                <Button
                    variant="outline"
                    disabled={processing}
                    onClick={() => setConfirming(true)}
                >
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
                        disabled={processing}
                        onClick={() =>
                            router.delete(
                                BulkTaskDeletionController.destroy.url(),
                                { ...options, data: payload },
                            )
                        }
                    >
                        {processing && <Spinner />}
                        {tChoice(
                            'Delete :count task|Delete :count tasks',
                            count,
                        )}
                    </Button>
                </ConfirmDialogActions>
            </ConfirmDialog>
        </>
    );
}
