import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import NotificationRow from '@/components/notifications/notification-row';
import EmptyState from '@/components/shared/empty-state';
import ErrorState from '@/components/shared/error-state';
import LoadingState from '@/components/shared/loading-state';
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

    if (failed) {
        return (
            <ErrorState
                message="Could not load your notifications."
                onRetry={() => {
                    setFailed(false);
                    loadLatest(() => setFailed(true));
                }}
            />
        );
    }

    if (latestNotifications === undefined) {
        return <LoadingState rows={3} />;
    }

    if (latestNotifications.length === 0) {
        return <EmptyState message="You have no notifications." />;
    }

    return (
        <div className="grid gap-4">
            <ul className="grid gap-3">
                {latestNotifications.map((notification) => (
                    <NotificationRow
                        key={notification.id}
                        notification={notification}
                    />
                ))}
            </ul>
            <Button variant="outline" className="justify-self-start" asChild>
                <Link href={index()}>View all notifications</Link>
            </Button>
        </div>
    );
}
