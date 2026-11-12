import { useEffect } from 'react';
import { toast } from 'sonner';

/**
 * Show errors a component received as one toast, each message once. The same
 * errors arriving again in a new array do not toast again; the id keeps one
 * toast per kind of error.
 */
export function useErrorsToast(errors: string[] | undefined, id: string): void {
    const message = errors?.length
        ? Array.from(new Set(errors)).join(' ')
        : null;

    useEffect(() => {
        if (message) {
            toast.error(message, { id });
        }
    }, [message, id]);
}
