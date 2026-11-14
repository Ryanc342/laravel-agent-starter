import { Link } from '@inertiajs/react';
import EmptyState from '@/components/shared/empty-state';
import Heading from '@/components/heading';
import TextLink from '@/components/text-link';
import { Badge } from '@/components/ui/badge';
import { t } from '@/lib/i18n';

export type SearchResult = {
    id: string | number;
    title: string;
    href: string;
    status?: {
        label: string;
        variant: 'secondary' | 'success' | 'warning' | 'destructive';
    };
};

export type SearchGroup = {
    type: string;
    /** The group's name ("Tasks"). */
    label: string;
    /** How many records match in all, not only the ones shown. */
    count: number;
    results: SearchResult[];
    /** The type's own list with the search carried over. */
    viewAllHref?: string;
};

/** The text with the part that matches the query in a `mark` (the `accent` token), case-insensitively. */
export function Highlight({ text, query }: { text: string; query: string }) {
    const needle = query.trim();

    if (needle === '') {
        return text;
    }

    const escaped = needle.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    return text.split(new RegExp(`(${escaped})`, 'i')).map((part, index) =>
        index % 2 === 1 ? (
            <mark
                key={index}
                className="rounded-sm bg-accent-pressed text-foreground"
            >
                {part}
            </mark>
        ) : (
            part
        ),
    );
}

/**
 * The results page body: results grouped by type, each group under a small
 * heading with its count, a bordered box of line-separated rows. The backend
 * searches, groups and counts; this only shows what it was given.
 */
export default function SearchResults({
    query,
    groups,
}: {
    query: string;
    groups: SearchGroup[];
}) {
    const shown = groups.filter((group) => group.results.length > 0);

    if (shown.length === 0) {
        return (
            <div className="rounded-lg border px-4 py-8">
                <EmptyState message={t('No results for ":query"', { query })} />
            </div>
        );
    }

    return (
        <div className="grid gap-8">
            {shown.map((group) => (
                <section key={group.type} className="grid gap-2">
                    <Heading
                        variant="small"
                        title={t(':label (:count)', {
                            label: t(group.label),
                            count: group.count,
                        })}
                    />
                    <ul className="divide-y rounded-lg border">
                        {group.results.map((result) => (
                            <li
                                key={result.id}
                                className="flex flex-wrap items-center justify-between gap-2 px-4 py-3"
                            >
                                <Link
                                    href={result.href}
                                    className="min-w-0 text-sm break-words text-primary transition-colors hover:underline active:text-primary-pressed"
                                >
                                    <Highlight
                                        text={result.title}
                                        query={query}
                                    />
                                </Link>
                                {result.status ? (
                                    <Badge variant={result.status.variant}>
                                        {result.status.label}
                                    </Badge>
                                ) : null}
                            </li>
                        ))}
                    </ul>
                    {group.viewAllHref && group.count > group.results.length ? (
                        <TextLink
                            href={group.viewAllHref}
                            className="w-fit text-sm"
                        >
                            {t('View all :count', { count: group.count })}
                        </TextLink>
                    ) : null}
                </section>
            ))}
        </div>
    );
}
