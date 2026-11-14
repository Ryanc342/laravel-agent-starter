import Heading from '@/components/heading';
import FeedList from '@/components/shared/feed-list';
import FeedRow from '@/components/shared/feed-row';
import type { ExampleNotification } from '@/components/style/types';
import { Button } from '@/components/ui/button';

/** A stream of messages: one bordered box, rows separated by lines, unread ones marked. An unread row is one button: clicking it marks it as read. */
export default function FeedDemo({
    notifications,
}: {
    notifications: ExampleNotification[];
}) {
    return (
        <div className="flex flex-col gap-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading title="Notifications" />
                <Button variant="outline" className="ml-auto">
                    Mark all as read
                </Button>
            </div>

            <FeedList emptyMessage="You have no notifications.">
                {notifications.map((notification) => (
                    <FeedRow
                        key={notification.id}
                        message={notification.message}
                        time={notification.time}
                        unread={notification.unread}
                        onClick={notification.unread ? () => {} : undefined}
                    />
                ))}
            </FeedList>

            <div className="grid gap-3">
                <p className="text-sm text-muted-foreground">
                    With no messages the box stays
                </p>
                <FeedList emptyMessage="You have no notifications.">
                    {[]}
                </FeedList>
            </div>
        </div>
    );
}
