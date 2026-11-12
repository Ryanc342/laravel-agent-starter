import { Form } from '@inertiajs/react';
import { Check } from 'lucide-react';
import NotificationReadController from '@/actions/App/Http/Controllers/NotificationReadController';
import FeedRow from '@/components/shared/feed-row';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
        <FeedRow
            message={notification.message}
            time={notification.created_at_diff}
            unread={notification.is_unread}
            variant={variant}
            action={
                notification.can.mark_read && (
                    <Form
                        {...NotificationReadController.store.form(
                            notification.id,
                        )}
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
                                    <TooltipContent>
                                        Mark as read
                                    </TooltipContent>
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
                )
            }
        />
    );
}
