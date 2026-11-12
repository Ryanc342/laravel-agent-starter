import { Head } from '@inertiajs/react';
import { Bell, Palette, ShieldCheck, UserRound } from 'lucide-react';
import Heading from '@/components/heading';
import AuthDemo from '@/components/style/auth-demo';
import DashboardDemo from '@/components/style/dashboard-demo';
import DetailDemo from '@/components/style/detail-demo';
import ErrorPageDemo from '@/components/style/error-page-demo';
import FeedDemo from '@/components/style/feed-demo';
import FormDemo from '@/components/style/form-demo';
import ListDemo from '@/components/style/list-demo';
import SettingsDemo from '@/components/style/settings-demo';
import SharedDemo from '@/components/style/shared-demo';
import StatesDemo from '@/components/style/states-demo';
import StyleSection, { DemoFrame } from '@/components/style/style-section';
import TokensDemo from '@/components/style/tokens-demo';
import type {
    ExampleNotification,
    ExampleTask,
} from '@/components/style/types';
import UiActionsDemo from '@/components/style/ui-actions-demo';
import UiFieldsDemo from '@/components/style/ui-fields-demo';
import UiSurfacesDemo from '@/components/style/ui-surfaces-demo';
import AppearanceToggleTab from '@/components/users/appearance-tabs';
import { style } from '@/routes';
import { edit as editAppearance } from '@/routes/appearance';
import { index as notificationsIndex } from '@/routes/notifications';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';
import type { ResourceCollection } from '@/types/resource';

// Example data for the demos. It is not backend data: a demo takes its data
// only through props, and this page is where the examples come from.
const tasks: ExampleTask[] = [
    {
        id: 1,
        title: 'Reply to the supplier about the delayed shipment of spare parts and the revised invoice',
        status: 'Open',
        overdue: true,
        due_on: '2026-11-11',
    },
    {
        id: 2,
        title: 'Write the quarterly report',
        status: 'Open',
        overdue: false,
        due_on: '2026-11-17',
    },
    {
        id: 3,
        title: 'Book the venue',
        status: 'Open',
        overdue: false,
        due_on: null,
    },
    {
        id: 4,
        title: 'Send the invoice',
        status: 'Done',
        overdue: false,
        due_on: null,
    },
];

const pagination: ResourceCollection<ExampleTask> = {
    data: tasks,
    links: {
        first: style({ query: { page: 1 } }).url,
        last: style({ query: { page: 3 } }).url,
        prev: null,
        next: style({ query: { page: 2 } }).url,
    },
    meta: {
        current_page: 1,
        from: 1,
        last_page: 3,
        per_page: 4,
        to: 4,
        total: 9,
    },
};

const notifications: ExampleNotification[] = [
    {
        id: '1',
        message: 'Your password was changed.',
        time: '5 minutes ago',
        unread: true,
    },
    {
        id: '2',
        message:
            'A new passkey was added to your account from a browser we have not seen before, so please check that it was you who added it.',
        time: '3 hours ago',
        unread: true,
    },
    {
        id: '3',
        message: 'Your password was changed.',
        time: '2 days ago',
        unread: false,
    },
];

const links: NavItem[] = [
    { title: 'Notifications', href: notificationsIndex(), icon: Bell },
    { title: 'Profile', href: editProfile(), icon: UserRound },
    { title: 'Security', href: editSecurity(), icon: ShieldCheck },
    { title: 'Appearance', href: editAppearance(), icon: Palette },
];

const menu = [
    ['tokens', 'Tokens'],
    ['ui', 'UI components'],
    ['shared', 'Shared blocks'],
    ['list', 'List'],
    ['detail', 'Detail'],
    ['form', 'Form'],
    ['states', 'States'],
    ['dashboard', 'Dashboard'],
    ['settings', 'Settings'],
    ['feed', 'Feed'],
    ['auth', 'Login and register'],
    ['error-page', 'Error pages'],
];

export default function Style() {
    return (
        <>
            <Head title="Style overview" />

            <div className="mx-auto flex max-w-5xl flex-col gap-12 p-4 md:p-6">
                <header className="flex flex-col gap-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <Heading
                            title="Style overview"
                            description="The design's tokens, components and pattern demos, with example data. This page exists only in the local environment."
                        />
                        <AppearanceToggleTab />
                    </div>

                    <nav
                        aria-label="Sections"
                        className="flex flex-wrap gap-x-5 gap-y-2 text-sm"
                    >
                        {menu.map(([id, label]) => (
                            <a
                                key={id}
                                href={`#${id}`}
                                className="text-muted-foreground transition-colors hover:text-foreground"
                            >
                                {label}
                            </a>
                        ))}
                    </nav>
                </header>

                <StyleSection
                    id="tokens"
                    title="Tokens"
                    description="Colour, radius and type (docs/design/foundations.md)."
                >
                    <TokensDemo />
                </StyleSection>

                <StyleSection
                    id="ui"
                    title="UI components"
                    description="components/ui, with their variants and states (docs/design/components.md)."
                >
                    <UiActionsDemo />
                    <UiFieldsDemo />
                    <UiSurfacesDemo />
                </StyleSection>

                <StyleSection
                    id="shared"
                    title="Shared blocks"
                    description="components/shared: what every screen is built from."
                >
                    <SharedDemo tasks={tasks} pagination={pagination} />
                </StyleSection>

                <StyleSection
                    id="list"
                    title="List"
                    description="docs/design/patterns/list.md"
                >
                    <DemoFrame>
                        <ListDemo tasks={tasks} pagination={pagination} />
                    </DemoFrame>
                </StyleSection>

                <StyleSection
                    id="detail"
                    title="Detail"
                    description="docs/design/patterns/detail.md"
                >
                    <DemoFrame>
                        <DetailDemo task={tasks[0]} />
                    </DemoFrame>
                </StyleSection>

                <StyleSection
                    id="form"
                    title="Form"
                    description="docs/design/patterns/form.md"
                >
                    <FormDemo
                        task={tasks[1]}
                        requiredMessage="The title field is required."
                    />
                </StyleSection>

                <StyleSection
                    id="states"
                    title="States"
                    description="docs/design/patterns/states.md"
                >
                    <StatesDemo subject="tasks" />
                </StyleSection>

                <StyleSection
                    id="dashboard"
                    title="Dashboard"
                    description="docs/design/patterns/dashboard.md"
                >
                    <DemoFrame>
                        <DashboardDemo
                            notifications={notifications}
                            links={links}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection
                    id="settings"
                    title="Settings"
                    description="docs/design/patterns/settings.md"
                >
                    <DemoFrame flush>
                        <SettingsDemo
                            name="Test User"
                            email="test@example.com"
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection
                    id="feed"
                    title="Feed"
                    description="docs/design/patterns/feed.md"
                >
                    <DemoFrame>
                        <FeedDemo notifications={notifications} />
                    </DemoFrame>
                </StyleSection>

                <StyleSection
                    id="auth"
                    title="Login and register"
                    description="docs/design/patterns/auth.md"
                >
                    <DemoFrame>
                        <AuthDemo
                            title="Log in to your account"
                            fields={[
                                {
                                    id: 'email',
                                    label: 'Email address',
                                    type: 'email',
                                    placeholder: 'email@example.com',
                                },
                                {
                                    id: 'password',
                                    label: 'Password',
                                    type: 'password',
                                    placeholder: 'Password',
                                },
                            ]}
                            submitLabel="Log in"
                            footer="Don't have an account?"
                            footerLink="Sign up"
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection
                    id="error-page"
                    title="Error pages"
                    description="docs/design/patterns/error-page.md"
                >
                    <ErrorPageDemo
                        examples={[
                            { status: 403, signedIn: true },
                            { status: 404, signedIn: false },
                            { status: 500, signedIn: true },
                            { status: 503, signedIn: false },
                        ]}
                    />
                </StyleSection>
            </div>
        </>
    );
}
