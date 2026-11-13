import Heading from '@/components/heading';
import FaqList, { type FaqTopic } from '@/components/shared/faq-list';
import type { PublicLink } from '@/components/shared/public-header';
import PublicFrame from '@/components/style/public-frame';
import TextLink from '@/components/text-link';

/**
 * The help page (docs/design/patterns/help.md): a search box, the questions
 * grouped by topic as collapsible rows, and a link to the contact page.
 */
export default function HelpDemo({
    links,
    title,
    topics,
    searchLabel,
    stillNeedHelp,
    contactLabel,
}: {
    links: PublicLink[];
    title: string;
    topics: FaqTopic[];
    searchLabel: string;
    stillNeedHelp: string;
    contactLabel: string;
}) {
    return (
        <PublicFrame links={links}>
            <div className="grid max-w-3xl gap-8">
                <Heading title={title} />
                <FaqList topics={topics} searchLabel={searchLabel} />
                <p className="text-sm">
                    {stillNeedHelp}{' '}
                    <TextLink href="#contact">{contactLabel}</TextLink>
                </p>
            </div>
        </PublicFrame>
    );
}
