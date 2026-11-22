import type { MenuColumn } from '@/components/shared/column-menu';
import SortFilterSheet from '@/components/shared/sort-filter-sheet';
import UrlSearchInput from '@/components/shared/url-search-input';
import { useIsMobile } from '@/hooks/use-mobile';

/**
 * The row above a list's table: the search box and, on a
 * phone, the "Sort & filter" button that opens the column menus in a sheet.
 * Sort, filters and search live only in the URL.
 */
export default function ListToolbar({
    columns,
    searchLabel,
}: {
    columns: MenuColumn[];
    searchLabel: string;
}) {
    const isMobile = useIsMobile();

    return (
        <div className="flex items-center gap-3">
            <UrlSearchInput
                id="list-search"
                param="search"
                label={searchLabel}
                className="min-w-0 flex-1 sm:w-64 sm:flex-none"
            />
            {isMobile && <SortFilterSheet columns={columns} />}
        </div>
    );
}
