import { Button } from '@/components/ui/button';

/**
 * A region that could not load: what went wrong and how to fix it, and a retry
 * only when trying again can help. Results of actions and network errors are
 * toasts, not this.
 */
export default function ErrorState({
    message,
    onRetry,
}: {
    message: string;
    onRetry?: () => void;
}) {
    return (
        <div role="alert" className="flex flex-col items-start gap-4">
            <p className="text-sm text-destructive-foreground">{message}</p>
            {onRetry && (
                <Button variant="outline" onClick={onRetry}>
                    Try again
                </Button>
            )}
        </div>
    );
}
