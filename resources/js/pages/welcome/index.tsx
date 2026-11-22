import { Head, usePage } from '@inertiajs/react';
import {
    Bell,
    LayoutGrid,
    ListChecks,
    LogIn,
    SunMoon,
    UserRound,
} from 'lucide-react';
import ClosingCall from '@/components/shared/closing-call';
import FeatureGrid from '@/components/shared/feature-grid';
import Hero from '@/components/shared/hero';
import { dashboard, login, register } from '@/routes';

// What the starter itself ships. A project replaces this text with its own:
// no reviews, logos or numbers it does not have.
const features = () => [
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
        icon: LayoutGrid,
        title: 'Shared components',
        description:
            'Fields, buttons and lists that work the same on every page.',
    },
    {
        icon: ListChecks,
        title: 'Checked',
        description: 'Tests and checks run before every change is done.',
    },
];

export default function Welcome() {
    const { signedIn } = usePage().props;

    return (
        <>
            <Head title="Home" />

            <Hero
                title="Start building your app"
                summary="A Laravel and React starter with sign-in, settings and notifications in place."
                primary={
                    signedIn
                        ? {
                              label: 'Open dashboard',
                              href: dashboard(),
                          }
                        : { label: 'Get started', href: register() }
                }
                secondary={
                    signedIn ? undefined : { label: 'Log in', href: login() }
                }
            />

            <FeatureGrid features={features()} />

            <ClosingCall
                text="Ready to build?"
                action={
                    signedIn
                        ? { label: 'Open dashboard', href: dashboard() }
                        : { label: 'Get started', href: register() }
                }
            />
        </>
    );
}
