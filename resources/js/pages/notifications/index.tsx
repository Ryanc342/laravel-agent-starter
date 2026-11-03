import { Form, Head } from '@inertiajs/react';
import AllNotificationsReadController from '@/actions/App/Http/Controllers/AllNotificationsReadController';
import Heading from '@/components/heading';
import NotificationRow from '@/components/notifications/notification-row';
import Pagination from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/notifications';
import type { AppNotification } from '@/types/notification';
import type { ResourceCollection } from '@/types/resource';

type NotificationsPageProps = {
    notifications: ResourceCollection<AppNotification>;
    can: {
        mark_all_read: boolean;
    };
};

export default function Notifications({
    notifications,
    can,
}: NotificationsPageProps) {
    return (
        <>
            <Head title="Notifications" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title="Notifications" />

                    {can.mark_all_read && (
                        <Form
                            {...AllNotificationsReadController.store.form()}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    Mark all as read
                                </Button>
                            )}
                        </Form>
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
