import type { FormDataConvertible } from '@inertiajs/core';

/**
 * Sort, filters and search of a list live only in the URL. These helpers read
 * and write them on an address (a path with its query, as Inertia's
 * `usePage().url` gives it): a list value is a bracket parameter (`status[]`),
 * every change drops `page` so the list starts again at page 1, and everything
 * else (`per_page`, the other columns' choices) stays.
 */
export type ParamChange = string | string[] | null;

export function withListParams(
    url: string,
    changes: Record<string, ParamChange>,
): string {
    const address = new URL(url, 'http://localhost');

    for (const [key, value] of Object.entries(changes)) {
        address.searchParams.delete(key);
        listKeys(address, key).forEach((name) =>
            address.searchParams.delete(name),
        );

        if (Array.isArray(value)) {
            value.forEach((item) =>
                address.searchParams.append(`${key}[]`, item),
            );
        } else if (value) {
            address.searchParams.set(key, value);
        }
    }

    address.searchParams.delete('page');

    return `${address.pathname}${address.search}`;
}

/** One value, or an empty string when the parameter is not in the address. */
export function listParam(url: string, key: string): string {
    return new URL(url, 'http://localhost').searchParams.get(key) ?? '';
}

/** The values of a bracket parameter: `status[]`, and `status[0]` as Inertia writes it back. */
export function listParams(url: string, key: string): string[] {
    const address = new URL(url, 'http://localhost');

    return listKeys(address, key).flatMap((name) =>
        address.searchParams.getAll(name),
    );
}

/** The parameter names that carry one list: `status[]`, `status[0]`, `status[1]`. */
function listKeys(address: URL, key: string): string[] {
    const own = new RegExp(`^${key}\\[\\d*\\]$`);

    return [...new Set(address.searchParams.keys())].filter((name) =>
        own.test(name),
    );
}

/**
 * What a bulk action sends: the ticked ids, or `all: true` with the list's own
 * search and filters, read from its address, so the backend acts on exactly the
 * rows the list shows. `single` names the parameters that hold one value
 * (`search`, `due_from`), `multiple` the ones that hold a list (`status`).
 */
export function bulkPayload(
    url: string,
    selection: { allResults: boolean; ids: (string | number)[] },
    filters: { single: string[]; multiple: string[] },
): Record<string, FormDataConvertible> {
    if (!selection.allResults) {
        return { ids: selection.ids };
    }

    const payload: Record<string, FormDataConvertible> = { all: true };

    for (const key of filters.single) {
        const value = listParam(url, key);

        if (value !== '') {
            payload[key] = value;
        }
    }

    for (const key of filters.multiple) {
        const values = listParams(url, key);

        if (values.length > 0) {
            payload[key] = values;
        }
    }

    return payload;
}
