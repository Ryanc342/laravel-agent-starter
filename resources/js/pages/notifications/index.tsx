import { Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import MarkAllReadButton from '@/components/notifications/mark-all-read-button';
import NotificationRow from '@/components/notifications/notification-row';
import EmptyState from '@/components/shared/empty-state';
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

            <div className="flex flex-col gap-8 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title="Notifications" />

                    {notificationSummary?.can.mark_all_read && (
                        <MarkAllReadButton />
                    )}
                </div>

                {notifications.data.length === 0 ? (
                    <EmptyState message="You have no notifications." />
                ) : (
                    <>
                        <ul className="grid gap-3">
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
