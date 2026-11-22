import { Link } from '@inertiajs/react';
import { useEffect } from 'react';
import NotificationRow from '@/components/notifications/notification-row';
import FeedList from '@/components/shared/feed-list';
import { Button } from '@/components/ui/button';
import { useLatestNotifications } from '@/hooks/use-latest-notifications';
import { index } from '@/routes/notifications';
import Actions from '@/components/shared/actions';

/**
 * The dashboard's block of the five newest notifications. Like the bell, it
 * loads them after the page opens, so the page itself shows at once.
 */
export default function LatestNotifications() {
    const { latest, failed, load } = useLatestNotifications();

    useEffect(() => {
        load();
    }, [load]);

    return (
        <div className="grid gap-4">
            <FeedList
                emptyMessage="You have no notifications."
                loading={latest === undefined && !failed}
                error={
                    failed
                        ? {
                              message: 'Could not load your notifications.',
                              onRetry: load,
                          }
                        : undefined
                }
            >
                {(latest ?? []).map((notification) => (
                    <NotificationRow
                        key={notification.id}
                        notification={notification}
                    />
                ))}
            </FeedList>
            {latest !== undefined && latest.length > 0 && (
                <Actions>
                    <Button variant="outline" asChild>
                        <Link href={index()}>View all</Link>
                    </Button>
                </Actions>
            )}
        </div>
    );
}
