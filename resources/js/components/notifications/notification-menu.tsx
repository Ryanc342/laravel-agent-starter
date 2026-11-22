import { Link, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useState } from 'react';
import MarkAllReadButton from '@/components/notifications/mark-all-read-button';
import NotificationRow from '@/components/notifications/notification-row';
import FeedList from '@/components/shared/feed-list';
import IconButton from '@/components/shared/icon-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useLatestNotifications } from '@/hooks/use-latest-notifications';
import { index } from '@/routes/notifications';

/**
 * The bell in the top bar. The unread count comes with every page
 * (notificationSummary); the five newest notifications load when the card
 * opens (latestNotifications, an optional prop of HandleInertiaRequests).
 */
export default function NotificationMenu() {
    const { notificationSummary } = usePage().props;
    const { latest, failed, load: loadLatest } = useLatestNotifications();
    const [open, setOpen] = useState(false);

    if (notificationSummary === null) {
        return null;
    }

    const unread = notificationSummary.unread;

    return (
        <Popover
            open={open}
            onOpenChange={(next: boolean) => {
                setOpen(next);

                if (next) {
                    loadLatest();
                }
            }}
        >
            <IconButton
                trigger={PopoverTrigger}
                label={
                    unread > 0
                        ? `Notifications, ${unread} unread`
                        : 'Notifications'
                }
                className="relative"
            >
                <Bell />
                {unread > 0 && (
                    <Badge
                        size="count"
                        className="pointer-events-none absolute -top-1 -right-1"
                    >
                        {unread > 9 ? '9+' : unread}
                    </Badge>
                )}
            </IconButton>

            <PopoverContent align="end" className="w-80 p-0">
                <div className="flex items-center justify-between gap-2 border-b px-4 py-2">
                    <h2 className="text-sm font-medium">Notifications</h2>
                    {notificationSummary.can.mark_all_read && (
                        <MarkAllReadButton size="sm" onSuccess={loadLatest} />
                    )}
                </div>

                <div className="max-h-96 overflow-y-auto">
                    <FeedList
                        framed={false}
                        emptyMessage="You have no notifications."
                        loading={!failed && latest === undefined}
                        error={
                            failed
                                ? {
                                      message:
                                          'Could not load your notifications.',
                                      onRetry: loadLatest,
                                  }
                                : undefined
                        }
                    >
                        {latest?.map((notification) => (
                            <NotificationRow
                                key={notification.id}
                                notification={notification}
                                variant="menu"
                                onMarkedRead={loadLatest}
                            />
                        ))}
                    </FeedList>
                </div>

                <div className="border-t p-1">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="w-full"
                        asChild
                    >
                        <Link href={index()} onClick={() => setOpen(false)}>
                            View all
                        </Link>
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}
