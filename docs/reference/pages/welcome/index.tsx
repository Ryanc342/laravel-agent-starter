import { Head, Link, usePage } from '@inertiajs/react';
import {
    Bell,
    Languages,
    ListChecks,
    LogIn,
    SunMoon,
    UserRound,
} from 'lucide-react';
import FeatureGrid from '@/components/shared/feature-grid';
import Hero from '@/components/shared/hero';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';
import { t } from '@/lib/i18n';

// What the starter itself ships. A project replaces this text with its own
// (docs/design/patterns/landing.md): no reviews, logos or numbers it does not have.
const features = () => [
    {
        icon: LogIn,
        title: t('Sign in'),
        description: t(
            'Register, log in, reset a password, passkeys and two-factor.',
        ),
    },
    {
        icon: UserRound,
        title: t('Account settings'),
        description: t('Profile, password, security and appearance.'),
    },
    {
        icon: Bell,
        title: t('Notifications'),
        description: t('A bell in the top bar and a page for the full list.'),
    },
    {
        icon: SunMoon,
        title: t('Light and dark'),
        description: t('The appearance follows your system.'),
    },
    {
        icon: Languages,
        title: t('English and Chinese'),
        description: t('Every screen holds text in both languages.'),
    },
    {
        icon: ListChecks,
        title: t('Checked'),
        description: t('Tests and checks run before every change is done.'),
    },
];

export default function Welcome() {
    const { auth } = usePage().props;
    const signedIn = auth.user !== null;

    return (
        <>
            <Head title={t('Home')} />

            <div className="grid gap-12 md:gap-16">
                <Hero
                    title={t('Start building your app')}
                    summary={t(
                        'A Laravel and React starter with sign-in, settings and notifications in place.',
                    )}
                    primary={
                        signedIn
                            ? {
                                  label: t('Open dashboard'),
                                  href: dashboard(),
                              }
                            : { label: t('Get started'), href: register() }
                    }
                    secondary={
                        signedIn
                            ? undefined
                            : { label: t('Log in'), href: login() }
                    }
                />

                <FeatureGrid features={features()} />

                <section className="flex flex-col gap-4 border-t pt-8 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-base font-medium">
                        {t('Ready to build?')}
                    </p>
                    <Button asChild>
                        <Link href={signedIn ? dashboard() : register()}>
                            {signedIn ? t('Open dashboard') : t('Get started')}
                        </Link>
                    </Button>
                </section>
            </div>
        </>
    );
}
