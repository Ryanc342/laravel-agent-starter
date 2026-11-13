import { useState } from 'react';

type Selection<Id> = { key: string; ids: Id[]; allResults: boolean };

/**
 * Which rows of a list are ticked. `resetKey` is the list's address: when the
 * search, sort, filters or page change, the selection starts again. `allResults`
 * is "Select all N results": every result of the list's own search and filters,
 * not only the rows on this page. The backend gets either the ids or "all
 * results" and decides what each user may touch.
 */
export function useRowSelection<Id extends string | number>(
    pageIds: Id[],
    resetKey: string,
) {
    const [stored, setStored] = useState<Selection<Id>>({
        key: resetKey,
        ids: [],
        allResults: false,
    });
    const current: Selection<Id> =
        stored.key === resetKey
            ? stored
            : { key: resetKey, ids: [], allResults: false };

    const update = (ids: Id[], allResults = false) =>
        setStored({ key: resetKey, ids, allResults });

    const onPage = pageIds.filter((id) => current.ids.includes(id));
    const pageState: 'none' | 'some' | 'all' =
        pageIds.length > 0 && onPage.length === pageIds.length
            ? 'all'
            : onPage.length > 0
              ? 'some'
              : 'none';

    return {
        ids: current.ids,
        count: current.ids.length,
        allResults: current.allResults,
        pageState,
        isSelected: (id: Id) => current.allResults || current.ids.includes(id),
        toggle: (id: Id) =>
            update(
                current.ids.includes(id)
                    ? current.ids.filter((other) => other !== id)
                    : [...current.ids, id],
            ),
        /** The header checkbox: ticks every row of this page, or unticks them. */
        togglePage: () => update(pageState === 'all' ? [] : [...pageIds]),
        /** Ticks every row of this page; a phone has no header checkbox for it. */
        selectPage: () => update([...pageIds]),
        selectAllResults: () => update([...pageIds], true),
        clear: () => update([]),
    };
}
