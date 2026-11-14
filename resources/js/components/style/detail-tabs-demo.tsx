import Heading from '@/components/heading';
import Actions from '@/components/shared/actions';
import Timeline, { type TimelineEvent } from '@/components/shared/timeline';
import UrlTabs from '@/components/shared/url-tabs';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/format';

type Item = { id: number; description: string; quantity: number; unit: number };

/**
 * One record with several groups under pill tabs: the facts, the items and the
 * history. The selected tab is in the URL (`?tab=items`); a missing or unknown
 * value shows the first. On a phone the strip scrolls sideways.
 */
export default function DetailTabsDemo({
    title,
    status,
    facts,
    items,
    events,
}: {
    title: string;
    status: string;
    facts: { label: string; value: string }[];
    items: Item[];
    events: TimelineEvent[];
}) {
    return (
        <div className="grid gap-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="flex min-w-0 flex-wrap items-start gap-2">
                    <Heading title={title} />
                    <Badge variant="secondary" className="mt-1">
                        {status}
                    </Badge>
                </div>
                <Actions>
                    <Button variant="outline">Edit order</Button>
                </Actions>
            </div>

            <UrlTabs
                tabs={[
                    {
                        value: 'overview',
                        label: 'Overview',
                        content: (
                            <dl className="grid max-w-xl gap-4 text-sm">
                                {facts.map((fact) => (
                                    <div
                                        key={fact.label}
                                        className="grid gap-1"
                                    >
                                        <dt className="text-muted-foreground">
                                            {fact.label}
                                        </dt>
                                        <dd>{fact.value}</dd>
                                    </div>
                                ))}
                            </dl>
                        ),
                    },
                    {
                        value: 'items',
                        label: 'Items',
                        content: (
                            <ul className="divide-y rounded-lg border">
                                {items.map((item) => (
                                    <li
                                        key={item.id}
                                        className="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm"
                                    >
                                        <span className="min-w-0 break-words">
                                            {item.description}
                                        </span>
                                        <span className="tabular-nums">
                                            {item.quantity} ×{' '}
                                            {formatMoney(item.unit)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        ),
                    },
                    {
                        value: 'history',
                        label: 'History',
                        content: <Timeline events={events} />,
                    },
                ]}
            />
        </div>
    );
}
