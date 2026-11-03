import { Link, router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useState } from 'react';
import MarkAllReadButton from '@/components/notifications/mark-all-read-button';
import NotificationRow from '@/components/notifications/notification-row';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { index } from '@/routes/notifications';

const loadLatest = () => router.reload({ only: ['latestNotifications'] });

/**
 * The bell in the top bar. The unread count comes with every page
 * (notificationSummary); the five newest notifications load when the card
 * opens (latestNotifications, an optional prop of HandleInertiaRequests).
 */
export default function NotificationMenu() {
    const { notificationSummary, latestNotifications } = usePage().props;
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
            <Tooltip>
                <TooltipTrigger asChild>
                    <PopoverTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="relative"
                            aria-label={
                                unread > 0
                                    ? `Notifications, ${unread} unread`
                                    : 'Notifications'
                            }
                        >
                            <Bell />
                            {unread > 0 && (
                                <Badge className="pointer-events-none absolute -top-1 -right-1 h-4 min-w-4 px-1 leading-none">
                                    {unread > 9 ? '9+' : unread}
                                </Badge>
                            )}
                        </Button>
                    </PopoverTrigger>
                </TooltipTrigger>
                <TooltipContent>Notifications</TooltipContent>
            </Tooltip>

            <PopoverContent align="end" className="w-80 p-0">
                <div className="flex items-center justify-between gap-2 border-b px-4 py-2">
                    <h2 className="text-sm font-medium">Notifications</h2>
                    {notificationSummary.can.mark_all_read && (
                        <MarkAllReadButton size="sm" onSuccess={loadLatest} />
                    )}
                </div>

                {latestNotifications === undefined ? (
                    <div className="grid gap-3 p-4">
                        <Skeleton className="h-10" />
                        <Skeleton className="h-10" />
                        <Skeleton className="h-10" />
                    </div>
                ) : latestNotifications.length === 0 ? (
                    <p className="px-4 py-6 text-sm text-muted-foreground">
                        You have no notifications.
                    </p>
                ) : (
                    <ul className="max-h-96 divide-y overflow-y-auto">
                        {latestNotifications.map((notification) => (
                            <NotificationRow
                                key={notification.id}
                                notification={notification}
                                variant="menu"
                                onMarkedRead={loadLatest}
                            />
                        ))}
                    </ul>
                )}

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
