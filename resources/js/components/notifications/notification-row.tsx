import { Form } from '@inertiajs/react';
import NotificationReadController from '@/actions/App/Http/Controllers/NotificationReadController';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { AppNotification } from '@/types/notification';

/**
 * One notification. The button is shown from notification.can.mark_read,
 * which the backend calculates.
 */
export default function NotificationRow({
    notification,
}: {
    notification: AppNotification;
}) {
    return (
        <li
            className={cn(
                'flex items-center justify-between gap-4 rounded-lg border p-4',
                notification.is_unread && 'bg-muted/50',
            )}
        >
            <div className="flex items-start gap-3">
                <span
                    className={cn(
                        'mt-1.5 size-2 shrink-0 rounded-full',
                        notification.is_unread && 'bg-primary',
                    )}
                />
                <div className="grid gap-1">
                    <span
                        className={cn(
                            'text-sm',
                            notification.is_unread && 'font-medium',
                        )}
                    >
                        {notification.message}
                    </span>
                    <span className="text-xs text-muted-foreground">
                        {notification.created_at_diff}
                    </span>
                </div>
            </div>

            {notification.can.mark_read && (
                <Form
                    {...NotificationReadController.store.form(notification.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <Button
                            type="submit"
                            size="sm"
                            variant="outline"
                            disabled={processing}
                        >
                            Mark as read
                        </Button>
                    )}
                </Form>
            )}
        </li>
    );
}
