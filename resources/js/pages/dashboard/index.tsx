import { Head } from '@inertiajs/react';
import { Bell, Palette, ShieldCheck, UserRound } from 'lucide-react';
import Heading from '@/components/heading';
import LatestNotifications from '@/components/notifications/latest-notifications';
import QuickLinks from '@/components/shared/quick-links';
import { dashboard } from '@/routes';
import { index as notifications } from '@/routes/notifications';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

// The app's own pages the user comes back to. The dashboard itself is not
// listed. A project adds its main pages here as it grows.
const quickLinks: NavItem[] = [
    { title: 'Notifications', href: notifications(), icon: Bell },
    { title: 'Profile', href: editProfile(), icon: UserRound },
    { title: 'Security', href: editSecurity(), icon: ShieldCheck },
    { title: 'Appearance', href: editAppearance(), icon: Palette },
];

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
                        <QuickLinks links={quickLinks} />
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
