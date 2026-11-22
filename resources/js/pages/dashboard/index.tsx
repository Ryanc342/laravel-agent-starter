import { Head } from '@inertiajs/react';
import { Bell, Palette, ShieldCheck, UserRound } from 'lucide-react';
import LatestNotifications from '@/components/notifications/latest-notifications';
import DashboardGrid from '@/components/shared/dashboard-grid';
import PageHeader from '@/components/shared/page-header';
import QuickLinks from '@/components/shared/quick-links';
import { dashboard } from '@/routes';
import { index as notifications } from '@/routes/notifications';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

// The app's own pages the user comes back to. The dashboard itself is not
// listed. A project adds its main pages here as it grows.
const quickLinks = (): NavItem[] => [
    { title: 'Notifications', href: notifications(), icon: Bell },
    { title: 'Profile', href: editProfile(), icon: UserRound },
    { title: 'Security', href: editSecurity(), icon: ShieldCheck },
    { title: 'Appearance', href: editAppearance(), icon: Palette },
];

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />

            <PageHeader title="Dashboard" />

            <DashboardGrid
                main={{
                    title: 'Latest notifications',
                    content: <LatestNotifications />,
                }}
                side={{
                    title: 'Quick links',
                    content: <QuickLinks links={quickLinks()} />,
                }}
            />
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
