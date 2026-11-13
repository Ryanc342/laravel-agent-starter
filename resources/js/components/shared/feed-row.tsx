import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * One message of a feed: an unread dot, the message, its time, and the row's
 * action on the right. The page ("list") sits in a `FeedList`'s box; the bell's
 * card ("menu") shows it as a plain row. An unread row has the dot and darker
 * text; no row has a background of its own. The message and the action come from the caller, who
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
                variant === 'menu' ? 'px-4 py-3' : 'p-4',
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
                            unread
                                ? 'text-foreground'
                                : 'text-muted-foreground',
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
