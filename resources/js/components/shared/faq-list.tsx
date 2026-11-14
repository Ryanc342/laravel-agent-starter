import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import EmptyState from '@/components/shared/empty-state';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { t } from '@/lib/i18n';

export type FaqItem = { id: string; question: string; answer: string };
export type FaqTopic = { id: string; title: string; items: FaqItem[] };

/**
 * Questions and answers (docs/design/patterns/help.md): a search box that
 * narrows the questions as the user types (question and answer, any case), and
 * the questions grouped by topic. Each topic is one bordered box of rows
 * separated by lines; each row is a `Collapsible` whose button is the question.
 * Several can be open at once. A topic with no match is not shown.
 */
export default function FaqList({
    topics,
    searchLabel = t('Search help'),
    emptyMessage = t('No questions match your search.'),
}: {
    topics: FaqTopic[];
    searchLabel?: string;
    emptyMessage?: string;
}) {
    const [search, setSearch] = useState('');
    const needle = search.trim().toLowerCase();

    const shown = topics
        .map((topic) => ({
            ...topic,
            title: t(topic.title),
            items: topic.items
                .map((item) => ({
                    ...item,
                    question: t(item.question),
                    answer: t(item.answer),
                }))
                .filter(
                    (item) =>
                        !needle ||
                        item.question.toLowerCase().includes(needle) ||
                        item.answer.toLowerCase().includes(needle),
                ),
        }))
        .filter((topic) => topic.items.length > 0);

    return (
        <div className="grid gap-8">
            <div>
                <Label htmlFor="faq-search" className="sr-only">
                    {searchLabel}
                </Label>
                <Input
                    id="faq-search"
                    type="search"
                    placeholder={searchLabel}
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                />
            </div>

            {shown.length === 0 && (
                <div className="rounded-lg border p-8">
                    <EmptyState message={emptyMessage} />
                </div>
            )}

            {shown.map((topic) => (
                <section key={topic.id} className="grid gap-3">
                    <Heading variant="small" title={topic.title} />
                    <ul className="divide-y rounded-lg border">
                        {topic.items.map((item) => (
                            <li key={item.id}>
                                <Collapsible className="group/faq">
                                    <CollapsibleTrigger className="flex min-h-12 w-full items-center justify-between gap-4 px-4 py-3 text-left text-sm font-medium transition-colors hover:bg-accent active:bg-accent-pressed">
                                        <span className="min-w-0">
                                            {item.question}
                                        </span>
                                        <ChevronDown
                                            aria-hidden="true"
                                            className="size-4 shrink-0 text-muted-foreground transition-transform group-data-[state=open]/faq:rotate-180"
                                        />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <p className="px-4 pb-4 text-sm text-muted-foreground">
                                            {item.answer}
                                        </p>
                                    </CollapsibleContent>
                                </Collapsible>
                            </li>
                        ))}
                    </ul>
                </section>
            ))}
        </div>
    );
}
