import { Children, type ReactNode } from 'react';
import EmptyState from '@/components/shared/empty-state';

/**
 * The rows of a feed in one bordered box, separated by lines: the notifications
 * page and the dashboard block. With no rows the box stays and the empty line
 * sits inside it. The rows are `FeedRow`s.
 */
export default function FeedList({
    emptyMessage,
    children,
}: {
    emptyMessage: string;
    children: ReactNode;
}) {
    if (Children.toArray(children).length === 0) {
        return (
            <div className="rounded-lg border p-4">
                <EmptyState message={emptyMessage} />
            </div>
        );
    }

    return <ul className="divide-y rounded-lg border">{children}</ul>;
}
