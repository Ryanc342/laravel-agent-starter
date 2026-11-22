import { Head, usePage } from '@inertiajs/react';
import MarkAllReadButton from '@/components/notifications/mark-all-read-button';
import NotificationRow from '@/components/notifications/notification-row';
import FeedList from '@/components/shared/feed-list';
import PageHeader from '@/components/shared/page-header';
import Pagination from '@/components/shared/pagination';
import { index } from '@/routes/notifications';
import type { ResourceCollection } from '@/types/resource';

type NotificationsPageProps = {
    notifications: ResourceCollection<App.Http.Resources.NotificationResource>;
};

export default function Notifications({
    notifications,
}: NotificationsPageProps) {
    const { notificationSummary } = usePage().props;

    return (
        <>
            <Head title="Notifications" />

            <PageHeader
                title="Notifications"
                actions={
                    notificationSummary?.can.mark_all_read && (
                        <MarkAllReadButton />
                    )
                }
            />

            <FeedList emptyMessage="You have no notifications.">
                {notifications.data.map((notification) => (
                    <NotificationRow
                        key={notification.id}
                        notification={notification}
                    />
                ))}
            </FeedList>
            <Pagination collection={notifications} label="Notification pages" />
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
