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

// What the starter itself ships. A project replaces this text with its own
// (docs/design/patterns/landing.md): no reviews, logos or numbers it does not have.
const features = [
    {
        icon: LogIn,
        title: 'Sign in',
        description:
            'Register, log in, reset a password, passkeys and two-factor.',
    },
    {
        icon: UserRound,
        title: 'Account settings',
        description: 'Profile, password, security and appearance.',
    },
    {
        icon: Bell,
        title: 'Notifications',
        description: 'A bell in the top bar and a page for the full list.',
    },
    {
        icon: SunMoon,
        title: 'Light and dark',
        description: 'The appearance follows your system.',
    },
    {
        icon: Languages,
        title: 'English and Chinese',
        description: 'Every screen holds text in both languages.',
    },
    {
        icon: ListChecks,
        title: 'Checked',
        description: 'Tests and checks run before every change is done.',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;
    const signedIn = auth.user !== null;

    return (
        <>
            <Head title="Home" />

            <div className="grid gap-12 md:gap-16">
                <Hero
                    title="Start building your app"
                    summary="A Laravel and React starter with sign-in, settings and notifications in place."
                    primary={
                        signedIn
                            ? { label: 'Open dashboard', href: dashboard() }
                            : { label: 'Get started', href: register() }
                    }
                    secondary={
                        signedIn
                            ? undefined
                            : { label: 'Log in', href: login() }
                    }
                />

                <FeatureGrid features={features} />

                <section className="flex flex-col gap-4 border-t pt-8 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-base font-medium">Ready to build?</p>
                    <Button asChild>
                        <Link href={signedIn ? dashboard() : register()}>
                            {signedIn ? 'Open dashboard' : 'Get started'}
                        </Link>
                    </Button>
                </section>
            </div>
        </>
    );
}
