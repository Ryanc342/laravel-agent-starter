import Heading from '@/components/heading';
import SearchBox from '@/components/shared/search-box';
import SearchResults, {
    type SearchGroup,
} from '@/components/shared/search-results';

/**
 * Search across more than one kind of record: the box in the top bar (a mock
 * bar here; a project mounts the box in its own layout) and the results page,
 * grouped by type with the matched text marked.
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
        <div className="grid gap-6">
            <div className="flex h-14 items-center rounded-lg border px-4">
                <SearchBox action={action} defaultValue={query} />
            </div>
            <div className="grid gap-6">
                <Heading title="Search results" />
                <SearchResults query={query} groups={groups} />
            </div>
        </div>
    );
}
