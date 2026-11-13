import { router, usePage } from '@inertiajs/react';
import { useCallback, useRef } from 'react';
import { type ParamChange, withListParams } from '@/lib/list-query';

/**
 * The sort, filters and search of the current list, which live only in the
 * URL. `change` asks Inertia for the same list with those parameters changed:
 * it goes back to page 1, keeps `per_page` and the other choices, and keeps
 * the scroll position and the state of the page (an open column menu).
 * `change` always uses the newest address, so a timer set earlier is safe.
 */
export function useListQuery() {
    const { url } = usePage();
    const latest = useRef(url);

    latest.current = url;

    const change = useCallback((changes: Record<string, ParamChange>) => {
        router.get(
            withListParams(latest.current, changes),
            {},
            { preserveScroll: true, preserveState: true },
        );
    }, []);

    return { url, change };
}
