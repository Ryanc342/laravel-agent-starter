import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { withListParams } from '@/lib/list-query';

/**
 * The export of a list whose Route::listing() allows it: a plain download of
 * the list's own address with export=csv, so the file holds every row the
 * list shows now, with its search, filters and sort, not only this page. It
 * is a link and not an Inertia visit, because the answer is a file.
 */
export default function ExportButton({
    children,
}: {
    /** The verb on the button ("Export"). */
    children: ReactNode;
}) {
    const { url } = usePage();

    return (
        <Button asChild variant="outline">
            <a href={withListParams(url, { export: 'csv' })} download>
                {children}
            </a>
        </Button>
    );
}
