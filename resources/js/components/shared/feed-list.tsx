import { Children, type ReactNode } from 'react';
import RegionState, {
    type RegionError,
} from '@/components/shared/region-state';

/**
 * The rows of a feed in one bordered box, separated by lines: the notifications
 * page and the dashboard block. With no rows, while loading or after a failure
 * the box stays and the state sits inside it, centred. The rows are `FeedRow`s.
 */
export default function FeedList({
    emptyMessage,
    loading = false,
    error,
    children,
}: {
    emptyMessage: string;
    loading?: boolean;
    error?: RegionError;
    children: ReactNode;
}) {
    if (loading || error || Children.toArray(children).length === 0) {
        return (
            <div className="rounded-lg border">
                <RegionState
                    emptyMessage={emptyMessage}
                    loading={loading}
                    error={error}
                />
            </div>
        );
    }

    return <ul className="divide-y rounded-lg border">{children}</ul>;
}
