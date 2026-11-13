import { router, usePage } from '@inertiajs/react';
import { useCallback, useState } from 'react';

/**
 * The five newest notifications, an optional prop of HandleInertiaRequests that
 * is sent only on request. Both places that show them, the bell and the
 * dashboard block, ask here, so loading and failing look the same: `load` asks
 * (a server error ends the visit here, a network error already has its toast)
 * and `failed` says it did not arrive. `latest` is undefined until they do.
 */
export function useLatestNotifications() {
    const { latestNotifications } = usePage().props;
    const [failed, setFailed] = useState(false);

    const load = useCallback(() => {
        setFailed(false);
        router.reload({
            only: ['latestNotifications'],
            onHttpException: () => {
                setFailed(true);

                return false;
            },
            onNetworkError: () => setFailed(true),
        });
    }, []);

    return { latest: latestNotifications, failed, load };
}
