import { cn } from '@/lib/utils';

/**
 * One message of a feed: an unread dot, the message and its time. The page
 * ("list") sits in a `FeedList`'s box; the bell's card ("menu") shows it as a
 * plain row. An unread row has the dot and darker text; no row has a background
 * of its own and none has a button. When the caller passes `onClick` the whole
 * row is one button (an unread notification marks itself as read); a row
 * without it is plain text. The message and whether the row can be clicked come
 * from the caller, who got them from the backend.
 */
export default function FeedRow({
    message,
    time,
    unread,
    variant = 'list',
    onClick,
    disabled = false,
}: {
    message: string;
    time: string | null;
    unread: boolean;
    variant?: 'list' | 'menu';
    onClick?: () => void;
    disabled?: boolean;
}) {
    const padding = variant === 'menu' ? 'px-4 py-3' : 'p-4';
    const content = (
        <>
            <span
                className={cn(
                    'mt-1.5 size-2 shrink-0 rounded-full',
                    unread && 'bg-primary',
                )}
            />
            <span className="grid min-w-0 gap-1">
                <span
                    className={cn(
                        'text-sm break-words',
                        unread ? 'text-foreground' : 'text-muted-foreground',
                    )}
                >
                    {message}
                </span>
                <span className="text-xs text-muted-foreground">{time}</span>
            </span>
        </>
    );

    return (
        <li>
            {onClick ? (
                <button
                    type="button"
                    onClick={onClick}
                    disabled={disabled}
                    className={cn(
                        'flex w-full items-start gap-3 text-left transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:ring-inset enabled:hover:bg-accent enabled:active:bg-accent-pressed',
                        padding,
                    )}
                >
                    {content}
                </button>
            ) : (
                <div className={cn('flex items-start gap-3', padding)}>
                    {content}
                </div>
            )}
        </li>
    );
}
