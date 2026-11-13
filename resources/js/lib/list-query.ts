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

/** Whether the address holds any value for these parameters, such as a search or a filter. */
export function hasListParams(url: string, keys: string[]): boolean {
    return keys.some(
        (key) => listParam(url, key) !== '' || listParams(url, key).length > 0,
    );
}
