import EmptyState from '@/components/shared/empty-state';
import ErrorState from '@/components/shared/error-state';
import LoadingState from '@/components/shared/loading-state';

export type RegionError = { message: string; onRetry?: () => void };

/**
 * What a framed list shows instead of rows: the failure, the loading skeleton
 * or the empty line, centred. The caller draws the frame (the table under its
 * header row, or the box) and passes `loading`/`error` from the backend.
 */
export default function RegionState({
    emptyMessage,
    loading = false,
    error,
}: {
    emptyMessage: string;
    loading?: boolean;
    error?: RegionError;
}) {
    if (error) {
        return (
            <div className="px-4 py-8">
                <ErrorState {...error} />
            </div>
        );
    }

    if (loading) {
        return <LoadingState framed={false} />;
    }

    return (
        <div className="px-4 py-8">
            <EmptyState message={emptyMessage} />
        </div>
    );
}
