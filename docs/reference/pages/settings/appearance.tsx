import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/users/appearance-tabs';
import LanguageToggle from '@/components/users/language-toggle';
import Heading from '@/components/heading';
import { edit as editAppearance } from '@/routes/appearance';
import { msg, t } from '@/lib/i18n';

export default function Appearance() {
    return (
        <>
            <Head title={t('Appearance settings')} />

            <h1 className="sr-only">{t('Appearance settings')}</h1>

            <div className="space-y-6">
                <Heading variant="small" title={t('Appearance settings')} />
                <AppearanceTabs />
            </div>

            <div className="space-y-6">
                <Heading variant="small" title={t('Language')} />
                <LanguageToggle />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: msg('Appearance settings'),
            href: editAppearance(),
        },
    ],
};
