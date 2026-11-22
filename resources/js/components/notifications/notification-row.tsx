import { router } from '@inertiajs/react';
import { useState } from 'react';
import FeedRow from '@/components/shared/feed-row';
import { store as markRead } from '@/routes/notifications/read';

/**
 * One notification, as a row of the notifications page ("list") or of the
 * bell's card ("menu"). An unread row is one button: clicking it marks the
 * notification as read and leaves the user on the page. A read row is plain
 * text. The row is a button from notification.can.mark_read, which the backend
 * calculates.
 */
export default function NotificationRow({
    notification,
    variant = 'list',
    onMarkedRead,
}: {
    notification: App.Http.Resources.NotificationResource;
    variant?: 'list' | 'menu';
    onMarkedRead?: () => void;
}) {
    const [processing, setProcessing] = useState(false);

    return (
        <FeedRow
            message={notification.message}
            time={notification.created_at_diff}
            unread={notification.is_unread}
            variant={variant}
            disabled={processing}
            onClick={
                notification.can.mark_read
                    ? () =>
                          router.visit(markRead(notification.id), {
                              preserveScroll: true,
                              onStart: () => setProcessing(true),
                              onFinish: () => setProcessing(false),
                              onSuccess: onMarkedRead,
                          })
                    : undefined
            }
        />
    );
}
