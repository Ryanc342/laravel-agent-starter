import Heading from '@/components/heading';
import FeedList from '@/components/shared/feed-list';
import FeedRow from '@/components/shared/feed-row';
import QuickLinks from '@/components/shared/quick-links';
import type { ExampleNotification } from '@/components/style/types';
import { Button } from '@/components/ui/button';
import type { NavItem } from '@/types';
import { t } from '@/lib/i18n';
import Actions from '@/components/shared/actions';

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
            <Heading title={t('Dashboard')} />

            <div className="grid gap-8 md:grid-cols-3">
                <section className="grid content-start gap-4 md:col-span-2">
                    <Heading
                        variant="small"
                        title={t('Latest notifications')}
                    />
                    <FeedList emptyMessage={t('You have no notifications.')}>
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
                        <Button variant="outline">
                            {t('View all notifications')}
                        </Button>
                    </Actions>
                </section>

                <section className="grid content-start gap-4">
                    <Heading variant="small" title={t('Quick links')} />
                    <QuickLinks links={links} />
                </section>
            </div>
        </div>
    );
}
