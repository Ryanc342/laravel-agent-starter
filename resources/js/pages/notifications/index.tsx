import { Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import MarkAllReadButton from '@/components/notifications/mark-all-read-button';
import NotificationRow from '@/components/notifications/notification-row';
import Pagination from '@/components/shared/pagination';
import { index } from '@/routes/notifications';
import type { AppNotification } from '@/types/notification';
import type { ResourceCollection } from '@/types/resource';

type NotificationsPageProps = {
    notifications: ResourceCollection<AppNotification>;
};

export default function Notifications({
    notifications,
}: NotificationsPageProps) {
    const { notificationSummary } = usePage().props;

    return (
        <>
            <Head title="Notifications" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title="Notifications" />

                    {notificationSummary?.can.mark_all_read && (
                        <MarkAllReadButton />
                    )}
                </div>

                {notifications.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        You have no notifications.
                    </p>
                ) : (
                    <>
                        <ul className="grid gap-2">
                            {notifications.data.map((notification) => (
                                <NotificationRow
                                    key={notification.id}
                                    notification={notification}
                                />
                            ))}
                        </ul>
                        <Pagination collection={notifications} />
                    </>
                )}
            </div>
        </>
    );
}

Notifications.layout = {
    breadcrumbs: [
        {
            title: 'Notifications',
            href: index(),
        },
    ],
};
