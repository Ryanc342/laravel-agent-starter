import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import Actions from '@/components/shared/actions';

/**
 * The top of a page: its title, and the page's actions at the far end (they
 * wrap under the title on a phone). Use it as the first thing of every page
 * that has a title of its own.
 */
export default function PageHeader({
    title,
    actions,
}: {
    title: string;
    actions?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-4">
            <Heading title={title} />
            {actions && <Actions>{actions}</Actions>}
        </div>
    );
}
