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
import { msg, t } from '@/lib/i18n';

// The app's own pages the user comes back to. The dashboard itself is not
// listed. A project adds its main pages here as it grows.
const quickLinks = (): NavItem[] => [
    { title: t('Notifications'), href: notifications(), icon: Bell },
    { title: t('Profile'), href: editProfile(), icon: UserRound },
    { title: t('Security'), href: editSecurity(), icon: ShieldCheck },
    { title: t('Appearance'), href: editAppearance(), icon: Palette },
];

export default function Dashboard() {
    return (
        <>
            <Head title={t('Dashboard')} />

            <div className="flex flex-col gap-8 p-4 md:p-6">
                <Heading title={t('Dashboard')} />

                <div className="grid gap-8 md:grid-cols-3">
                    <section className="grid content-start gap-4 md:col-span-2">
                        <Heading
                            variant="small"
                            title={t('Latest notifications')}
                        />
                        <LatestNotifications />
                    </section>

                    <section className="grid content-start gap-4">
                        <Heading variant="small" title={t('Quick links')} />
                        <QuickLinks links={quickLinks()} />
                    </section>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: msg('Dashboard'),
            href: dashboard(),
        },
    ],
};
