import Heading from '@/components/heading';
import FaqList, { type FaqTopic } from '@/components/shared/faq-list';
import type { PublicLink } from '@/components/shared/public-header';
import PublicFrame from '@/components/style/public-frame';
import TextLink from '@/components/text-link';

/**
 * The help page: a search box, the questions
 * grouped by topic as collapsible rows, and a link to the contact page.
 */
export default function HelpDemo({
    links,
    title,
    topics,
    searchLabel,
    emptyMessage,
    stillNeedHelp,
    contactLabel,
}: {
    links: PublicLink[];
    title: string;
    topics: FaqTopic[];
    searchLabel: string;
    emptyMessage: string;
    stillNeedHelp: string;
    contactLabel: string;
}) {
    return (
        <PublicFrame name="Help" links={links}>
            <div className="grid max-w-3xl gap-8">
                <Heading title={title} />
                <FaqList
                    topics={topics}
                    searchLabel={searchLabel}
                    emptyMessage={emptyMessage}
                />
                <p className="text-sm">
                    {stillNeedHelp}{' '}
                    <TextLink href="#contact">{contactLabel}</TextLink>
                </p>
            </div>
        </PublicFrame>
    );
}
