import Heading from '@/components/heading';
import SearchBox from '@/components/shared/search-box';
import SearchResults, {
    type SearchGroup,
} from '@/components/shared/search-results';
import { t } from '@/lib/i18n';

/**
 * Search across more than one kind of record: the box in the top bar (a mock
 * strip here, drawn like the app's header; a project mounts the box in its own
 * layout) and the results page, grouped by type with the matched text marked.
 * Used in a `flush` DemoFrame: the strip and the page padding are its own.
 */
export default function SearchDemo({
    query,
    action,
    groups,
}: {
    query: string;
    /** The results page the box opens (`?q=`). */
    action: string;
    groups: SearchGroup[];
}) {
    return (
        <div>
            <header className="flex h-16 items-center border-b px-4 md:px-6">
                <SearchBox action={action} defaultValue={query} />
            </header>
            <div className="grid gap-6 p-4 md:p-6">
                <Heading title={t('Search results')} />
                <SearchResults query={query} groups={groups} />
            </div>
        </div>
    );
}
