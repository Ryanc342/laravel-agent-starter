import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * One message of a feed: an unread dot, the message, its time, and the row's
 * action on the right. The page ("list") shows it as a box; the bell's card
 * ("menu") as a plain row. The message and the action come from the caller, who
 * got them from the backend.
 */
export default function FeedRow({
    message,
    time,
    unread,
    variant = 'list',
    action,
}: {
    message: string;
    time: string | null;
    unread: boolean;
    variant?: 'list' | 'menu';
    action?: ReactNode;
}) {
    return (
        <li
            className={cn(
                'flex items-center justify-between gap-4',
                variant === 'menu' ? 'px-4 py-3' : 'rounded-lg border p-4',
                unread && 'bg-muted/50',
            )}
        >
            <div className="flex min-w-0 items-start gap-3">
                <span
                    className={cn(
                        'mt-1.5 size-2 shrink-0 rounded-full',
                        unread && 'bg-primary',
                    )}
                />
                <div className="grid min-w-0 gap-1">
                    <span
                        className={cn(
                            'text-sm break-words',
                            unread && 'font-medium',
                        )}
                    >
                        {message}
                    </span>
                    <span className="text-xs text-muted-foreground">
                        {time}
                    </span>
                </div>
            </div>

            {action}
        </li>
    );
}
