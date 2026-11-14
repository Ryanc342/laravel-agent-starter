import { CircleAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';

/**
 * A region that could not load: centred, a red icon and a red line saying what
 * went wrong and how to fix it, and a retry only when trying again can help.
 * Results of actions and network errors are toasts, not this.
 */
export default function ErrorState({
    message,
    onRetry,
}: {
    message: string;
    onRetry?: () => void;
}) {
    return (
        <div
            role="alert"
            className="flex flex-col items-center gap-3 text-center"
        >
            <CircleAlert
                aria-hidden="true"
                className="size-8 text-destructive-foreground"
            />
            <p className="text-sm text-destructive-foreground">{message}</p>
            {onRetry && (
                <Button variant="outline" onClick={onRetry}>
                    {t('Try again')}
                </Button>
            )}
        </div>
    );
}
