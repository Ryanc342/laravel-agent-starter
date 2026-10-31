/**
 * A Laravel resource collection as React receives it:
 * `TaskResource::collection($tasks)->response($request)->getData(true)`.
 * `links` and `meta` are present only for paginated collections.
 */
export type ResourceCollection<T> = {
    data: T[];
    links?: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta?: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
    };
};
