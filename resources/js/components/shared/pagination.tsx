import { Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { withPerPage } from '@/lib/pagination';
import { cn } from '@/lib/utils';
import type { ResourceCollection } from '@/types/resource';

/**
 * Under a paginated list: the page links, and next to them how many rows a
 * page shows. The server sends the links and the sizes (`perPageOptions`); the
 * size lives only in the URL. The links show when the list has more than one
 * page, the size choice when it has more rows than the smallest size.
 */
export default function Pagination({
    collection,
}: {
    collection: ResourceCollection<unknown>;
}) {
    const { links, meta } = collection;
    const { url, props } = usePage();
    const options = props.perPageOptions;

    if (!links || !meta) {
        return null;
    }

    const hasPages = Boolean(links.prev || links.next);
    const hasChoice = meta.total > Math.min(...options);

    if (!hasPages && !hasChoice) {
        return null;
    }

    return (
        <nav
            aria-label="Pagination"
            className={cn(
                'flex flex-wrap items-center gap-4',
                hasPages ? 'justify-between' : 'justify-end',
            )}
        >
            {hasPages && (
                <span className="text-sm text-muted-foreground">
                    Page {meta.current_page} of {meta.last_page}
                </span>
            )}

            <div className="flex flex-wrap items-center gap-4">
                {hasChoice && (
                    <div className="flex items-center gap-2">
                        <span
                            className="text-sm text-muted-foreground"
                            aria-hidden="true"
                        >
                            Rows per page
                        </span>
                        <Select
                            value={String(meta.per_page)}
                            onValueChange={(value) =>
                                router.get(
                                    withPerPage(url, Number(value)),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <SelectTrigger
                                size="sm"
                                className="w-20"
                                aria-label="Rows per page"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {options.map((option) => (
                                    <SelectItem
                                        key={option}
                                        value={String(option)}
                                    >
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}

                {hasPages && (
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
                )}
            </div>
        </nav>
    );
}
