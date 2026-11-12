import type { ReactNode } from 'react';

/**
 * A list or region with nothing in it: one short line, and the create button
 * when the user can create what is missing. Nothing else.
 */
export default function EmptyState({
    message,
    action,
}: {
    message: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-start gap-4">
            <p className="text-sm text-muted-foreground">{message}</p>
            {action}
        </div>
    );
}
