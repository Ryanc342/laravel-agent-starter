import { usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import ListDetailLayout, {
    ListDetailRow,
} from '@/components/shared/list-detail-layout';
import { Badge } from '@/components/ui/badge';
import { listParam } from '@/lib/list-query';
import { t } from '@/lib/i18n';

export type ExampleTicket = {
    id: number;
    title: string;
    status: string;
    summary: string;
    requester: string;
};

/**
 * The list beside the selected record. The selected record is in the URL
 * (`?ticket=2`, here on the style page); with none selected the first shows on
 * a computer, and on a phone the list fills the screen until a row is opened.
 */
export default function ListDetailDemo({
    tickets,
    hrefFor,
    backHref,
}: {
    tickets: ExampleTicket[];
    /** The address of a ticket's row (`?ticket=<id>`). */
    hrefFor: (id: number) => string;
    backHref: string;
}) {
    const { url } = usePage();
    const asked = Number(listParam(url, 'ticket'));
    const chosen = tickets.find((ticket) => ticket.id === asked);
    const shown = chosen ?? tickets[0];

    return (
        <div className="grid gap-6">
            <Heading title={t('Tickets')} />
            <ListDetailLayout
                selected={chosen !== undefined}
                backHref={backHref}
                backLabel={t('Tickets')}
                list={
                    <nav
                        aria-label={t('Tickets')}
                        className="grid overflow-hidden rounded-lg border"
                    >
                        {tickets.map((ticket) => (
                            <ListDetailRow
                                key={ticket.id}
                                href={hrefFor(ticket.id)}
                                selected={ticket.id === shown.id}
                            >
                                <span className="text-sm font-medium break-words">
                                    {ticket.title}
                                </span>
                                <span className="flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary">
                                        {ticket.status}
                                    </Badge>
                                    <span className="text-xs text-muted-foreground">
                                        {ticket.requester}
                                    </span>
                                </span>
                            </ListDetailRow>
                        ))}
                    </nav>
                }
                detail={
                    <div className="grid gap-4 rounded-lg border p-4 md:p-6">
                        <div className="flex flex-wrap items-start gap-2">
                            <Heading title={shown.title} />
                            <Badge variant="secondary" className="mt-1">
                                {shown.status}
                            </Badge>
                        </div>
                        <dl className="grid gap-1 text-sm">
                            <dt className="text-muted-foreground">
                                {t('Requested by')}
                            </dt>
                            <dd>{shown.requester}</dd>
                        </dl>
                        <p className="text-sm">{shown.summary}</p>
                    </div>
                }
            />
        </div>
    );
}
