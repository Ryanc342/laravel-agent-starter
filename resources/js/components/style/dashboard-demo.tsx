import Heading from '@/components/heading';
import FeedRow from '@/components/shared/feed-row';
import QuickLinks from '@/components/shared/quick-links';
import type { ExampleNotification } from '@/components/style/types';
import { Button } from '@/components/ui/button';
import type { NavItem } from '@/types';

/** The page after login: the newest notifications beside the quick links. */
export default function DashboardDemo({
    notifications,
    links,
}: {
    notifications: ExampleNotification[];
    links: NavItem[];
}) {
    return (
        <div className="flex flex-col gap-8">
            <Heading title="Dashboard" />

            <div className="grid gap-8 md:grid-cols-3">
                <section className="grid content-start gap-4 md:col-span-2">
                    <Heading variant="small" title="Latest notifications" />
                    <ul className="grid gap-3">
                        {notifications.map((notification) => (
                            <FeedRow
                                key={notification.id}
                                message={notification.message}
                                time={notification.time}
                                unread={notification.unread}
                            />
                        ))}
                    </ul>
                    <Button variant="outline" className="justify-self-start">
                        View all notifications
                    </Button>
                </section>

                <section className="grid content-start gap-4">
                    <Heading variant="small" title="Quick links" />
                    <QuickLinks links={links} />
                </section>
            </div>
        </div>
    );
}
