import Heading from '@/components/heading';
import AccountNav from '@/components/shared/account-nav';
import type { PublicLink } from '@/components/shared/public-header';
import QuickLinks from '@/components/shared/quick-links';
import ResponsiveTable from '@/components/shared/responsive-table';
import PublicFrame from '@/components/style/public-frame';
import { Badge } from '@/components/ui/badge';
import { formatDate, formatMoney } from '@/lib/format';
import type { NavItem } from '@/types';

export type ExampleOrder = {
    id: number;
    number: string;
    status: string;
    variant: 'secondary' | 'success' | 'warning' | 'destructive';
    placed_on: string;
    /** In minor units; shown through `formatMoney`. */
    total: number;
};

/**
 * A signed-in customer's overview (docs/design/patterns/customer-area.md): the
 * public layout with "Dashboard" in the bar, the account navigation, the
 * recent orders as a list and the quick links. The orders, one order and the
 * profile follow the list, detail and settings patterns.
 */
export default function CustomerAreaDemo({
    links,
    nav,
    title,
    ordersTitle,
    linksTitle,
    orders,
    currency,
    quickLinks,
    columns,
    emptyMessage,
}: {
    links: PublicLink[];
    nav: NavItem[];
    title: string;
    ordersTitle: string;
    linksTitle: string;
    orders: ExampleOrder[];
    currency: string;
    quickLinks: NavItem[];
    columns: { order: string; status: string; placed: string; total: string };
    emptyMessage: string;
}) {
    return (
        <PublicFrame links={links} signedIn>
            <div className="grid gap-8">
                <AccountNav items={nav} />
                <Heading title={title} />

                <div className="grid gap-8 md:grid-cols-3">
                    <section className="grid content-start gap-4 md:col-span-2">
                        <Heading variant="small" title={ordersTitle} />
                        <ResponsiveTable
                            rows={orders}
                            getRowKey={(order) => order.id}
                            emptyMessage={emptyMessage}
                            columns={[
                                {
                                    header: columns.order,
                                    cell: (order) => order.number,
                                },
                                {
                                    header: columns.status,
                                    hideLabelOnCard: true,
                                    cell: (order) => (
                                        <Badge variant={order.variant}>
                                            {order.status}
                                        </Badge>
                                    ),
                                },
                                {
                                    header: columns.placed,
                                    cell: (order) =>
                                        formatDate(order.placed_on),
                                },
                                {
                                    header: columns.total,
                                    className: 'tabular-nums',
                                    cell: (order) =>
                                        formatMoney(order.total, currency),
                                },
                            ]}
                        />
                    </section>

                    <section className="grid content-start gap-4">
                        <Heading variant="small" title={linksTitle} />
                        <QuickLinks links={quickLinks} />
                    </section>
                </div>
            </div>
        </PublicFrame>
    );
}
