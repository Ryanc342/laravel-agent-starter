import { Form } from '@inertiajs/react';
import { Check } from 'lucide-react';
import NotificationReadController from '@/actions/App/Http/Controllers/NotificationReadController';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { AppNotification } from '@/types/notification';

/**
 * One notification, as a row of the notifications page ("list") or of the
 * bell's card ("menu"). The button is shown from notification.can.mark_read,
 * which the backend calculates.
 */
export default function NotificationRow({
    notification,
    variant = 'list',
    onMarkedRead,
}: {
    notification: AppNotification;
    variant?: 'list' | 'menu';
    onMarkedRead?: () => void;
}) {
    const menu = variant === 'menu';

    return (
        <li
            className={cn(
                'flex items-center justify-between gap-4',
                menu ? 'px-4 py-3' : 'rounded-lg border p-4',
                notification.is_unread && 'bg-muted/50',
            )}
        >
            <div className="flex min-w-0 items-start gap-3">
                <span
                    className={cn(
                        'mt-1.5 size-2 shrink-0 rounded-full',
                        notification.is_unread && 'bg-primary',
                    )}
                />
                <div className="grid min-w-0 gap-1">
                    <span
                        className={cn(
                            'text-sm break-words',
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
                    onSuccess={onMarkedRead}
                >
                    {({ processing }) =>
                        menu ? (
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        type="submit"
                                        size="icon"
                                        variant="ghost"
                                        disabled={processing}
                                        aria-label="Mark as read"
                                    >
                                        <Check />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Mark as read</TooltipContent>
                            </Tooltip>
                        ) : (
                            <Button
                                type="submit"
                                size="sm"
                                variant="outline"
                                disabled={processing}
                            >
                                Mark as read
                            </Button>
                        )
                    }
                </Form>
            )}
        </li>
    );
}
