import { Head } from '@inertiajs/react';
import QuickLinks from '@/components/dashboard/quick-links';
import Heading from '@/components/heading';
import LatestNotifications from '@/components/notifications/latest-notifications';
import { dashboard } from '@/routes';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-8 p-4 md:p-6">
                <Heading title="Dashboard" />

                <div className="grid gap-8 md:grid-cols-3">
                    <section className="grid content-start gap-4 md:col-span-2">
                        <Heading variant="small" title="Latest notifications" />
                        <LatestNotifications />
                    </section>

                    <section className="grid content-start gap-4">
                        <Heading variant="small" title="Quick links" />
                        <QuickLinks />
                    </section>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
