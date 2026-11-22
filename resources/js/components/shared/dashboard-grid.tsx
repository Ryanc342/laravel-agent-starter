import type { ReactNode } from 'react';
import Heading from '@/components/heading';

type Block = { title: string; content: ReactNode };

/**
 * The two columns of a dashboard: the main block, two thirds wide, and a side
 * block beside it on a computer; one under the other on a phone. Each block
 * has its small heading.
 */
export default function DashboardGrid({
    main,
    side,
}: {
    main: Block;
    side: Block;
}) {
    return (
        <div className="grid gap-8 md:grid-cols-3">
            <section className="grid content-start gap-4 md:col-span-2">
                <Heading variant="small" title={main.title} />
                {main.content}
            </section>
            <section className="grid content-start gap-4">
                <Heading variant="small" title={side.title} />
                {side.content}
            </section>
        </div>
    );
}
