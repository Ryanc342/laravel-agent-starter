import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { ResourceCollection } from '@/types/resource';

/**
 * Previous and next links for a paginated list. The server sends the links;
 * nothing is shown when the list fits on one page.
 */
export default function Pagination({
    collection,
}: {
    collection: ResourceCollection<unknown>;
}) {
    const { links, meta } = collection;

    if (!links || !meta || (!links.prev && !links.next)) {
        return null;
    }

    return (
        <nav
            aria-label="Pagination"
            className="flex items-center justify-between gap-4"
        >
            <span className="text-sm text-muted-foreground">
                Page {meta.current_page} of {meta.last_page}
            </span>

            <div className="flex gap-2">
                {links.prev ? (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={links.prev} preserveScroll>
                            Previous
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        Previous
                    </Button>
                )}
                {links.next ? (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={links.next} preserveScroll>
                            Next
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        Next
                    </Button>
                )}
            </div>
        </nav>
    );
}
