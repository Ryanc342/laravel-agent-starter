import { Inbox } from 'lucide-react';
import type { ReactNode } from 'react';

/**
 * A list or region with nothing in it: centred, a faint icon, one short line,
 * and the create button when the user can create what is missing. Nothing else.
 */
export default function EmptyState({
    message,
    action,
}: {
    message: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center gap-3 text-center">
            <Inbox
                aria-hidden="true"
                className="size-8 text-muted-foreground/60"
            />
            <p className="text-sm text-muted-foreground">{message}</p>
            {action}
        </div>
    );
}
