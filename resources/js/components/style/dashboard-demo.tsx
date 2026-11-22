import DashboardGrid from '@/components/shared/dashboard-grid';
import FeedList from '@/components/shared/feed-list';
import FeedRow from '@/components/shared/feed-row';
import PageHeader from '@/components/shared/page-header';
import QuickLinks from '@/components/shared/quick-links';
import type { ExampleNotification } from '@/components/style/types';
import Actions from '@/components/shared/actions';
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
            <PageHeader title="Dashboard" />

            <DashboardGrid
                main={{
                    title: 'Latest notifications',
                    content: (
                        <>
                            <FeedList emptyMessage="You have no notifications.">
                                {notifications.map((notification) => (
                                    <FeedRow
                                        key={notification.id}
                                        message={notification.message}
                                        time={notification.time}
                                        unread={notification.unread}
                                    />
                                ))}
                            </FeedList>
                            <Actions>
                                <Button variant="outline">View all</Button>
                            </Actions>
                        </>
                    ),
                }}
                side={{
                    title: 'Quick links',
                    content: <QuickLinks links={links} />,
                }}
            />
        </div>
    );
}
