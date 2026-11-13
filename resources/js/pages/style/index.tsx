import { Head } from '@inertiajs/react';
import { Bell, Palette, ShieldCheck, UserRound } from 'lucide-react';
import Heading from '@/components/heading';
import type { Choice } from '@/components/shared/column-menu';
import type { SearchGroup } from '@/components/shared/search-results';
import type { TimelineEvent } from '@/components/shared/timeline';
import ApprovalDemo, {
    type ApprovalState,
} from '@/components/style/approval-demo';
import AuthDemo from '@/components/style/auth-demo';
import BulkActionsDemo from '@/components/style/bulk-actions-demo';
import CalendarDemo from '@/components/style/calendar-demo';
import DashboardDemo from '@/components/style/dashboard-demo';
import DetailDemo from '@/components/style/detail-demo';
import DetailTabsDemo from '@/components/style/detail-tabs-demo';
import ErrorPageDemo from '@/components/style/error-page-demo';
import FeedDemo from '@/components/style/feed-demo';
import FormDemo from '@/components/style/form-demo';
import ImportExportDemo from '@/components/style/import-export-demo';
import KanbanDemo, { type ExampleCard } from '@/components/style/kanban-demo';
import ListDemo from '@/components/style/list-demo';
import ListDetailDemo, {
    type ExampleTicket,
} from '@/components/style/list-detail-demo';
import PrintDemo, { type ExampleInvoice } from '@/components/style/print-demo';
import ReportDemo from '@/components/style/report-demo';
import SearchDemo from '@/components/style/search-demo';
import SettingsDemo from '@/components/style/settings-demo';
import SharedDemo from '@/components/style/shared-demo';
import StatesDemo from '@/components/style/states-demo';
import StyleSection, { DemoFrame } from '@/components/style/style-section';
import TokensDemo from '@/components/style/tokens-demo';
import WizardDemo from '@/components/style/wizard-demo';
import type {
    ExampleNotification,
    ExampleTask,
} from '@/components/style/types';
import UiActionsDemo from '@/components/style/ui-actions-demo';
import UiFieldsDemo from '@/components/style/ui-fields-demo';
import UiSurfacesDemo from '@/components/style/ui-surfaces-demo';
import AppearanceToggleTab from '@/components/users/appearance-tabs';
import type { KanbanColumn } from '@/lib/kanban';
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
        amount: 48000,
    },
    {
        id: 2,
        title: 'Write the quarterly report',
        status: 'Open',
        overdue: false,
        due_on: '2026-11-17',
        amount: 125000,
    },
    {
        id: 3,
        title: 'Book the venue',
        status: 'Open',
        overdue: false,
        due_on: null,
        amount: 9900,
    },
    {
        id: 4,
        title: 'Send the invoice',
        status: 'Done',
        overdue: false,
        due_on: null,
        amount: 0,
    },
];

// The tick list's options come from the backend in a real list.
const statusChoices: Choice[] = [
    { value: 'open', label: 'Open' },
    { value: 'done', label: 'Done' },
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
        per_page: 20,
        to: 20,
        total: 45,
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

const searchGroups: SearchGroup[] = [
    {
        type: 'tasks',
        label: 'Tasks',
        count: 5,
        viewAllHref: style({ query: { search: 'invoice' } }).url,
        results: [
            {
                id: 1,
                title: 'Send the invoice to the supplier',
                href: '#detail',
                status: { label: 'Open', variant: 'secondary' },
            },
            {
                id: 2,
                title: 'Check the Invoice numbers for October',
                href: '#detail',
                status: { label: 'Done', variant: 'success' },
            },
            {
                id: 3,
                title: 'Reply about the revised invoice',
                href: '#detail',
                status: { label: 'Overdue', variant: 'destructive' },
            },
        ],
    },
    {
        type: 'customers',
        label: 'Customers',
        count: 1,
        results: [{ id: 10, title: 'Invoice Desk Sdn Bhd', href: '#detail' }],
    },
];

const wizardSteps = [
    {
        name: 'Team',
        fields: [{ id: 'team-name', label: 'Team name' }],
    },
    {
        name: 'Members',
        fields: [
            { id: 'member-1', label: 'First member' },
            { id: 'member-2', label: 'Second member' },
        ],
    },
    {
        name: 'Review',
        fields: [{ id: 'team-note', label: 'Note for the team' }],
    },
];

// Stands in for the server's answer to a step (a FormRequest on a real page).
const answerWizardStep = (
    fields: { id: string; label: string }[],
    values: Record<string, string>,
) =>
    Object.fromEntries(
        fields
            .filter((field) => !values[field.id]?.trim())
            .map((field) => [field.id, `Enter ${field.label.toLowerCase()}.`]),
    );

// Stands in for what the backend sends with each state of the request: the
// label, the badge and the `can.*` flags.
const approvalStates: Record<
    'pending' | 'approved' | 'rejected',
    ApprovalState
> = {
    pending: {
        label: 'Pending',
        variant: 'warning',
        can: { approve: true, reject: true },
    },
    approved: {
        label: 'Approved',
        variant: 'success',
        can: { approve: false, reject: false },
    },
    rejected: {
        label: 'Rejected',
        variant: 'destructive',
        can: { approve: false, reject: false },
    },
};

// Stands in for the FormRequest that checks the reason.
const answerRejection = (reason: string) =>
    reason.trim() ? null : 'Enter a reason.';

const approvalEvents: TimelineEvent[] = [
    {
        id: 1,
        actor: 'Mei Ling',
        action: 'submitted',
        when: '12 Nov, 09:30',
    },
    {
        id: 2,
        actor: 'Ahmad',
        action: 'asked for a receipt',
        when: '12 Nov, 14:05',
        note: 'Please attach the receipt for the hotel.',
    },
    {
        id: 3,
        actor: 'Mei Ling',
        action: 'added the receipt',
        when: '13 Nov, 08:10',
    },
];

const tickets: ExampleTicket[] = [
    {
        id: 1,
        title: 'Printer on the second floor is offline',
        status: 'Open',
        requester: 'Mei Ling',
        summary:
            'The shared printer shows as offline since Monday and the restart did not help.',
    },
    {
        id: 2,
        title: 'Cannot log in after changing my password',
        status: 'Open',
        requester: 'Ahmad',
        summary:
            'The login page says the password is wrong, although the reset e-mail worked.',
    },
    {
        id: 3,
        title: 'Need access to the shared drive',
        status: 'Done',
        requester: 'Siti',
        summary: 'Access was added on Tuesday and the user confirmed it.',
    },
];

const orderFacts = [
    { label: 'Customer', value: 'Invoice Desk Sdn Bhd' },
    { label: 'Order date', value: '4 Nov 2026' },
    { label: 'Delivery to', value: 'Jalan Ampang 12, Kuala Lumpur' },
];

const orderItems = [
    { id: 1, description: 'Spare parts, box of 20', quantity: 3, unit: 15000 },
    { id: 2, description: 'Delivery', quantity: 1, unit: 4500 },
];

const orderEvents: TimelineEvent[] = [
    {
        id: 1,
        actor: 'Mei Ling',
        action: 'created the order',
        when: '4 Nov, 10:00',
    },
    { id: 2, actor: 'Ahmad', action: 'confirmed it', when: '5 Nov, 09:15' },
];

const invoice: ExampleInvoice = {
    number: '2026-0012',
    date: '2026-11-14',
    seller: 'Ezdev Trading Sdn Bhd, Jalan Ampang 12, Kuala Lumpur',
    buyer: 'Invoice Desk Sdn Bhd, Jalan Tun Razak 3, Kuala Lumpur',
    lines: [
        {
            id: 1,
            description: 'Spare parts, box of 20',
            quantity: 3,
            unit: 15000,
            total: 45000,
        },
        {
            id: 2,
            description: 'Delivery',
            quantity: 1,
            unit: 4500,
            total: 4500,
        },
    ],
    subtotal: 49500,
    tax: 2970,
    total: 52470,
};

const reportTiles = [
    { label: 'Tasks done', value: '128' },
    { label: 'Tasks added', value: '141' },
    { label: 'Overdue now', value: '9' },
    { label: 'Done on time', value: '92%' },
];

const reportDaily = [
    { day: 'Mon', done: 18 },
    { day: 'Tue', done: 24 },
    { day: 'Wed', done: 21 },
    { day: 'Thu', done: 30 },
    { day: 'Fri', done: 35 },
];

const reportTeams = [
    { team: 'Sales', open: 12, done: 30, overdue: 3 },
    { team: 'Support', open: 20, done: 44, overdue: 5 },
    { team: 'Delivery', open: 8, done: 54, overdue: 1 },
];

const calendarEvents = [
    { id: 1, date: '2026-11-03', title: 'Team meeting' },
    { id: 2, date: '2026-11-05', title: 'Supplier call' },
    { id: 3, date: '2026-11-14', title: 'Deliver order 1042' },
    { id: 4, date: '2026-11-14', title: 'Quote review' },
    { id: 5, date: '2026-11-14', title: 'Pay the invoice' },
    { id: 6, date: '2026-11-14', title: 'Update the stock list' },
    { id: 7, date: '2026-11-14', title: 'Call the bank' },
    { id: 8, date: '2026-11-20', title: 'Quarterly report due' },
    { id: 9, date: '2026-11-27', title: 'Inventory count' },
];

const kanbanColumns: KanbanColumn<ExampleCard>[] = [
    {
        id: 'new',
        label: 'New',
        cards: [
            { id: 'o1', title: 'Order 1041, spare parts', owner: 'Mei Ling' },
            { id: 'o2', title: 'Order 1042, delivery to Ipoh', owner: 'Ahmad' },
        ],
    },
    {
        id: 'packing',
        label: 'Packing',
        cards: [{ id: 'o3', title: 'Order 1040, 20 boxes', owner: 'Siti' }],
    },
    { id: 'shipped', label: 'Shipped', cards: [] },
];

const menu = [
    ['tokens', 'Tokens'],
    ['ui', 'UI components'],
    ['shared', 'Shared blocks'],
    ['list', 'List'],
    ['detail', 'Detail'],
    ['detail-tabs', 'Detail with tabs'],
    ['form', 'Form'],
    ['wizard', 'Multi-step wizard'],
    ['approval', 'Approval'],
    ['list-detail', 'List beside detail'],
    ['bulk-actions', 'Bulk actions'],
    ['import-export', 'Import and export'],
    ['search', 'Search results'],
    ['report', 'Charts and reports'],
    ['calendar', 'Calendar'],
    ['kanban', 'Kanban'],
    ['print', 'Print page'],
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
                        <Heading title="Style overview" />
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
                                className="text-muted-foreground transition-colors hover:text-foreground active:text-primary-pressed"
                            >
                                {label}
                            </a>
                        ))}
                    </nav>
                </header>

                <StyleSection id="tokens" title="Tokens">
                    <TokensDemo />
                </StyleSection>

                <StyleSection id="ui" title="UI components">
                    <UiActionsDemo />
                    <UiFieldsDemo />
                    <UiSurfacesDemo />
                </StyleSection>

                <StyleSection id="shared" title="Shared blocks">
                    <SharedDemo tasks={tasks} pagination={pagination} />
                </StyleSection>

                <StyleSection id="list" title="List">
                    <DemoFrame>
                        <ListDemo
                            tasks={tasks}
                            pagination={pagination}
                            statusChoices={statusChoices}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="detail" title="Detail">
                    <DemoFrame>
                        <DetailDemo task={tasks[0]} />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="detail-tabs" title="Detail with tabs">
                    <DemoFrame>
                        <DetailTabsDemo
                            title="Order 1042"
                            status="Open"
                            facts={orderFacts}
                            items={orderItems}
                            events={orderEvents}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="form" title="Form">
                    <FormDemo
                        task={tasks[1]}
                        requiredMessage="The title field is required."
                    />
                </StyleSection>

                <StyleSection id="wizard" title="Multi-step wizard">
                    <DemoFrame>
                        <WizardDemo
                            title="Set up team"
                            steps={wizardSteps}
                            submitLabel="Create team"
                            answer={answerWizardStep}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="approval" title="Approval">
                    <DemoFrame>
                        <ApprovalDemo
                            title="Expense claim 218"
                            facts={[
                                { label: 'Amount', value: 'RM 480.00' },
                                { label: 'Requested by', value: 'Mei Ling' },
                            ]}
                            events={approvalEvents}
                            states={approvalStates}
                            answer={answerRejection}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="list-detail" title="List beside detail">
                    <DemoFrame>
                        <ListDetailDemo
                            tickets={tickets}
                            hrefFor={(id) =>
                                style({ query: { ticket: id } }).url
                            }
                            backHref={style().url}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="bulk-actions" title="Bulk actions">
                    <DemoFrame>
                        <BulkActionsDemo
                            tasks={tasks}
                            total={pagination.meta?.total ?? tasks.length}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="import-export" title="Import and export">
                    <DemoFrame>
                        <ImportExportDemo
                            rowErrors={[
                                'Row 2: Due date is not a date.',
                                'Row 3: Title is required.',
                                'Row 4: Due date is not a date.',
                                'Row 5: Due date is not a date.',
                                'Row 6: Due date is not a date.',
                            ]}
                            more="And 2 more errors."
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="search" title="Search results">
                    <DemoFrame flush>
                        <SearchDemo
                            query="invoice"
                            action={style().url}
                            groups={searchGroups}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="report" title="Charts and reports">
                    <DemoFrame>
                        <ReportDemo
                            tiles={reportTiles}
                            daily={reportDaily}
                            dailySeries={[{ key: 'done', label: 'Done' }]}
                            teams={reportTeams}
                            teamSeries={[
                                { key: 'open', label: 'Open' },
                                { key: 'done', label: 'Done' },
                                { key: 'overdue', label: 'Overdue' },
                            ]}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="calendar" title="Calendar">
                    <DemoFrame>
                        <CalendarDemo
                            events={calendarEvents}
                            today="2026-11-14"
                            month="2026-11"
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="kanban" title="Kanban">
                    <DemoFrame>
                        <KanbanDemo columns={kanbanColumns} />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="print" title="Print page">
                    <DemoFrame>
                        <PrintDemo invoice={invoice} />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="states" title="States">
                    <StatesDemo subject="tasks" />
                </StyleSection>

                <StyleSection id="dashboard" title="Dashboard">
                    <DemoFrame>
                        <DashboardDemo
                            notifications={notifications}
                            links={links}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="settings" title="Settings">
                    <DemoFrame flush>
                        <SettingsDemo
                            name="Test User"
                            email="test@example.com"
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="feed" title="Feed">
                    <DemoFrame>
                        <FeedDemo notifications={notifications} />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="auth" title="Login and register">
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

                <StyleSection id="error-page" title="Error pages">
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
