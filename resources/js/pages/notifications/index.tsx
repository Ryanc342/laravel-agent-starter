import { Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import MarkAllReadButton from '@/components/notifications/mark-all-read-button';
import NotificationRow from '@/components/notifications/notification-row';
import FeedList from '@/components/shared/feed-list';
import Pagination from '@/components/shared/pagination';
import { index } from '@/routes/notifications';
import type { AppNotification } from '@/types/notification';
import type { ResourceCollection } from '@/types/resource';
import { msg, t } from '@/lib/i18n';

type NotificationsPageProps = {
    notifications: ResourceCollection<AppNotification>;
};

export default function Notifications({
    notifications,
}: NotificationsPageProps) {
    const { notificationSummary } = usePage().props;

    return (
        <>
            <Head title={t('Notifications')} />

            <div className="flex flex-col gap-8 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title={t('Notifications')} />

                    {notificationSummary?.can.mark_all_read && (
                        <div className="ml-auto">
                            <MarkAllReadButton />
                        </div>
                    )}
                </div>

                <FeedList emptyMessage={t('You have no notifications.')}>
                    {notifications.data.map((notification) => (
                        <NotificationRow
                            key={notification.id}
                            notification={notification}
                        />
                    ))}
                </FeedList>
                <Pagination collection={notifications} />
            </div>
        </>
    );
}

Notifications.layout = {
    breadcrumbs: [
        {
            title: msg('Notifications'),
            href: index(),
        },
    ],
};
