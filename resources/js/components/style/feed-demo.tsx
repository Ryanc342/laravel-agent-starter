import Heading from '@/components/heading';
import FeedRow from '@/components/shared/feed-row';
import type { ExampleNotification } from '@/components/style/types';
import { Button } from '@/components/ui/button';

/** A stream of messages: one box per message, unread ones marked, with the row's action on the right. */
export default function FeedDemo({
    notifications,
}: {
    notifications: ExampleNotification[];
}) {
    return (
        <div className="flex flex-col gap-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading title="Notifications" />
                <Button variant="outline">Mark all as read</Button>
            </div>

            <ul className="grid gap-3">
                {notifications.map((notification) => (
                    <FeedRow
                        key={notification.id}
                        message={notification.message}
                        time={notification.time}
                        unread={notification.unread}
                        action={
                            notification.unread && (
                                <Button size="sm" variant="outline">
                                    Mark as read
                                </Button>
                            )
                        }
                    />
                ))}
            </ul>
        </div>
    );
}
