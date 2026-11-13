/**
 * The address of the same list with another number of rows per page: the path
 * and the filters stay, the page number goes (the list starts again at page 1).
 * `url` is a path with its query, as Inertia's `usePage().url` gives it.
 */
export function withPerPage(url: string, perPage: number): string {
    const address = new URL(url, 'http://localhost');

    address.searchParams.set('per_page', String(perPage));
    address.searchParams.delete('page');

    return `${address.pathname}${address.search}`;
}
