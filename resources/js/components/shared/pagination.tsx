import { Link, router, usePage } from '@inertiajs/react';
import SelectField from '@/components/shared/select-field';
import { Button } from '@/components/ui/button';
import { withPerPage } from '@/lib/pagination';
import { cn } from '@/lib/utils';
import type { ResourceCollection } from '@/types/resource';

/**
 * Under a paginated list: the page links, and next to them how many rows a
 * page shows, named by `label` (what the pages are of). The server sends the
 * links and the sizes (`perPageOptions`); the size lives only in the URL. The links show when the list has more than one
 * page, the size choice when it has more rows than the smallest size.
 */
export default function Pagination({
    collection,
    label,
}: {
    collection: ResourceCollection<unknown>;
    label: string;
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
            aria-label={label}
            className={cn(
                'flex flex-wrap items-center gap-4',
                hasPages ? 'justify-between' : 'justify-end',
            )}
        >
            {hasPages && (
                <span className="text-sm text-muted-foreground">
                    {`Page ${meta.current_page} of ${meta.last_page}`}
                </span>
            )}

            <div className="flex flex-wrap items-center gap-4">
                {hasChoice && (
                    <SelectField
                        label="Rows per page"
                        className="w-36"
                        value={String(meta.per_page)}
                        options={options.map((option) => ({
                            value: String(option),
                            label: String(option),
                        }))}
                        onValueChange={(value) =>
                            router.get(
                                withPerPage(url, Number(value)),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    />
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
