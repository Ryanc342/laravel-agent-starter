import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/users/appearance-tabs';
import Section from '@/components/shared/section';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    return (
        <>
            <Head title="Appearance settings" />

            <Section title="Appearance settings">
                <AppearanceTabs />
            </Section>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Appearance settings',
            href: editAppearance(),
        },
    ],
};
