import { Children, type ReactNode } from 'react';
import RegionState, {
    type RegionError,
} from '@/components/shared/region-state';
import { cn } from '@/lib/utils';

/**
 * The rows of a feed, separated by lines: the notifications page, the
 * dashboard block, the bell's card. Use it for any list of FeedRows or
 * NotificationRows. With no rows, while loading or after a failure the state
 * sits where the rows would, centred. It draws its own bordered box, except
 * inside a box that already has one (`framed={false}`, the bell's card).
 */
export default function FeedList({
    emptyMessage,
    loading = false,
    error,
    framed = true,
    children,
}: {
    emptyMessage: string;
    loading?: boolean;
    error?: RegionError;
    framed?: boolean;
    children: ReactNode;
}) {
    const frame = framed && 'rounded-lg border';

    if (loading || error || Children.toArray(children).length === 0) {
        return (
            <div className={cn(frame)}>
                <RegionState
                    emptyMessage={emptyMessage}
                    loading={loading}
                    error={error}
                />
            </div>
        );
    }

    return <ul className={cn('divide-y', frame)}>{children}</ul>;
}
