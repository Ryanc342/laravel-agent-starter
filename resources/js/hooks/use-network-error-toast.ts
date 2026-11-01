import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

export const NETWORK_ERROR_MESSAGE =
    'Cannot reach the server. Check your connection and try again.';

/**
 * Tells the user when a request could not reach the server (offline, server
 * down), on every page and for every form. Several failures at once, such as
 * a save and a hover prefetch, share one toast.
 */
export function useNetworkErrorToast(): void {
    useEffect(() => {
        return router.on('networkError', () => {
            toast.error(NETWORK_ERROR_MESSAGE, { id: 'network-error' });
        });
    }, []);
}
