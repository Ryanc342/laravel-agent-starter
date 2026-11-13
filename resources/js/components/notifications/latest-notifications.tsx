import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import NotificationRow from '@/components/notifications/notification-row';
import FeedList from '@/components/shared/feed-list';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/notifications';

/**
 * Ask for the newest notifications (an optional prop of HandleInertiaRequests,
 * sent only on request). A server error ends the visit here, in this block; a
 * network error already has its toast.
 */
function loadLatest(onFail: () => void) {
    router.reload({
        only: ['latestNotifications'],
        onHttpException: () => {
            onFail();

            return false;
        },
        onNetworkError: onFail,
    });
}

/**
 * The dashboard's block of the five newest notifications. Like the bell, it
 * loads them after the page opens, so the page itself shows at once.
 */
export default function LatestNotifications() {
    const { latestNotifications } = usePage().props;
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        loadLatest(() => setFailed(true));
    }, []);

    return (
        <div className="grid gap-4">
            <FeedList
                emptyMessage="You have no notifications."
                loading={latestNotifications === undefined && !failed}
                error={
                    failed
                        ? {
                              message: 'Could not load your notifications.',
                              onRetry: () => {
                                  setFailed(false);
                                  loadLatest(() => setFailed(true));
                              },
                          }
                        : undefined
                }
            >
                {(latestNotifications ?? []).map((notification) => (
                    <NotificationRow
                        key={notification.id}
                        notification={notification}
                    />
                ))}
            </FeedList>
            {latestNotifications !== undefined &&
                latestNotifications.length > 0 && (
                    <Button
                        variant="outline"
                        className="justify-self-start"
                        asChild
                    >
                        <Link href={index()}>View all notifications</Link>
                    </Button>
                )}
        </div>
    );
}
