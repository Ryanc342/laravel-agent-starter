import { Head } from '@inertiajs/react';
import { Bell, Palette, ShieldCheck, UserRound } from 'lucide-react';
import Heading from '@/components/heading';
import type { Choice } from '@/components/shared/column-menu';
import type { SearchGroup } from '@/components/shared/search-results';
import type { TimelineEvent } from '@/components/shared/timeline';
import ApprovalDemo, {
    type ApprovalView,
} from '@/components/style/approval-demo';
import AuthDemo from '@/components/style/auth-demo';
import BulkActionsDemo from '@/components/style/bulk-actions-demo';
import CalendarDemo from '@/components/style/calendar-demo';
import CheckoutDemo from '@/components/style/checkout-demo';
import ContactDemo from '@/components/style/contact-demo';
import CustomerAreaDemo from '@/components/style/customer-area-demo';
import {
    checkout,
    contact,
    customerArea,
    help,
    landing,
    pricing,
    product,
    publicLinks,
} from '@/components/style/customer-examples';
import DashboardDemo from '@/components/style/dashboard-demo';
import DetailDemo from '@/components/style/detail-demo';
import DetailTabsDemo from '@/components/style/detail-tabs-demo';
import ErrorPageDemo from '@/components/style/error-page-demo';
import FeedDemo from '@/components/style/feed-demo';
import FormDemo, {
    type ExampleInvoiceForm,
} from '@/components/style/form-demo';
import HelpDemo from '@/components/style/help-demo';
import ImportExportDemo from '@/components/style/import-export-demo';
import LandingDemo from '@/components/style/landing-demo';
import KanbanDemo, { type ExampleCard } from '@/components/style/kanban-demo';
import ListDemo from '@/components/style/list-demo';
import RowActionsDemo, {
    type ExampleCategory,
} from '@/components/style/row-actions-demo';
import ListDetailDemo, {
    type ExampleTicket,
} from '@/components/style/list-detail-demo';
import PricingDemo from '@/components/style/pricing-demo';
import PrintDemo, { type ExampleInvoice } from '@/components/style/print-demo';
import ProductDemo from '@/components/style/product-demo';
import ReportDemo from '@/components/style/report-demo';
import SearchDemo from '@/components/style/search-demo';
import SettingsDemo from '@/components/style/settings-demo';
import SharedDemo from '@/components/style/shared-demo';
import StatesDemo from '@/components/style/states-demo';
import StyleSection, { DemoFrame } from '@/components/style/style-section';
import TokensDemo from '@/components/style/tokens-demo';
import WizardDemo, { type WizardState } from '@/components/style/wizard-demo';
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

// The wizard as it looks at three moments; the errors are what the server
// answered for that step.
const wizardStates: WizardState[] = [
    { label: 'The first step', step: 0, values: {}, errors: {} },
    {
        label: 'The second step, sent back with an error',
        step: 1,
        values: { 'member-1': 'Mei Ling' },
        errors: { 'member-2': 'Enter the second member.' },
    },
    {
        label: 'The last step',
        step: 2,
        values: { 'team-note': 'We meet on Mondays.' },
        errors: {},
    },
];

// A list without a detail page: its rows carry their own actions.
const categories: ExampleCategory[] = [
    { id: 1, name: 'Office supplies', expenses: 12 },
    { id: 2, name: 'Travel', expenses: 7 },
    { id: 3, name: 'Meals with clients', expenses: 4 },
];

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

// What the backend sends with the request at each moment: the label, the
// badge, the `can.*` flags and the history.
const approvalViews: ApprovalView[] = [
    {
        moment: 'Waiting for a decision',
        label: 'Pending',
        variant: 'warning',
        can: { approve: true, reject: true },
        events: approvalEvents,
    },
    {
        moment: 'Approved',
        label: 'Approved',
        variant: 'success',
        can: { approve: false, reject: false },
        events: [
            ...approvalEvents,
            {
                id: 4,
                actor: 'Ahmad',
                action: 'approved',
                when: '13 Nov, 10:20',
            },
        ],
    },
    {
        moment: 'Rejected, with the reason',
        label: 'Rejected',
        variant: 'destructive',
        can: { approve: false, reject: false },
        events: [
            ...approvalEvents,
            {
                id: 4,
                actor: 'Ahmad',
                action: 'rejected',
                when: '13 Nov, 10:20',
                note: 'The hotel is above the travel limit.',
            },
        ],
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

const invoiceForm: ExampleInvoiceForm = {
    customer: 'Invoice Desk Sdn Bhd',
    date: '2026-11-20',
    due: '2026-12-20',
    note: 'Pay by bank transfer before the due date',
    currency: 'MYR',
    lines: [
        {
            id: 1,
            description: 'Website design',
            quantity: '1',
            unit: '8,000.00',
            total: 800000,
        },
        {
            id: 2,
            description: 'Front-end development (days)',
            quantity: '12',
            unit: '1,200.00',
            total: 1440000,
        },
        {
            id: 3,
            description: 'Back-end development (days)',
            quantity: '10',
            unit: '1,300.00',
            total: 1300000,
        },
        {
            id: 4,
            description: 'Server (months)',
            quantity: '3',
            unit: '450.00',
            total: 135000,
        },
        {
            id: 5,
            description: 'Domain renewal',
            quantity: '1',
            unit: '120.00',
            total: 12000,
        },
        {
            id: 6,
            description: 'Launch support (days)',
            quantity: '2',
            unit: '1,000.00',
            total: 200000,
        },
    ],
    total: 3887000,
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
    ['landing', 'Home / landing'],
    ['pricing', 'Pricing'],
    ['product', 'Product page'],
    ['customer-area', 'Customer area'],
    ['checkout', 'Checkout'],
    ['help', 'Help / FAQ'],
    ['contact', 'Contact form'],
    ['error-page', 'Error pages'],
];

/** Example data: the names of the demo records, shown as they are. */
const exampleRecord = { order: 'Order 1042', claim: 'Expense claim 218' };

export default function Style() {
    return (
        <>
            <Head title="Style overview" />

            <main className="mx-auto flex max-w-5xl flex-col gap-12 p-4 md:p-6">
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
                    <DemoFrame>
                        <RowActionsDemo
                            categories={categories}
                            nameError="Enter a name for the category."
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
                            title={exampleRecord.order}
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
                        invoice={invoiceForm}
                    />
                </StyleSection>

                <StyleSection id="wizard" title="Multi-step wizard">
                    <DemoFrame>
                        <WizardDemo
                            title="Set up team"
                            steps={wizardSteps}
                            states={wizardStates}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="approval" title="Approval">
                    <DemoFrame>
                        <ApprovalDemo
                            title={exampleRecord.claim}
                            facts={[
                                { label: 'Amount', value: 'RM 480.00' },
                                { label: 'Requested by', value: 'Mei Ling' },
                            ]}
                            views={approvalViews}
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
                                `Row ${2}: Due date is not a date.`,
                                `Row ${3}: Title is required.`,
                                `Row ${4}: Due date is not a date.`,
                                `Row ${5}: Due date is not a date.`,
                                `Row ${6}: Due date is not a date.`,
                                `Row ${7}: Due date is not a date.`,
                                `Row ${8}: Title is required.`,
                            ]}
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
                    <DemoFrame>
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
                            status="Your password has been reset."
                            submitLabel="Log in"
                            footer="Don't have an account?"
                            footerLink="Sign up"
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="landing" title="Home / landing">
                    <LandingDemo links={publicLinks} {...landing} />
                </StyleSection>

                <StyleSection id="pricing" title="Pricing">
                    <PricingDemo links={publicLinks} {...pricing} />
                </StyleSection>

                <StyleSection id="product" title="Product page">
                    <ProductDemo links={publicLinks} {...product} />
                </StyleSection>

                <StyleSection id="customer-area" title="Customer area">
                    <CustomerAreaDemo links={publicLinks} {...customerArea} />
                </StyleSection>

                <StyleSection id="checkout" title="Checkout">
                    <CheckoutDemo links={publicLinks} {...checkout} />
                </StyleSection>

                <StyleSection id="help" title="Help / FAQ">
                    <HelpDemo links={publicLinks} {...help} />
                </StyleSection>

                <StyleSection id="contact" title="Contact form">
                    <ContactDemo links={publicLinks} {...contact} />
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
            </main>
        </>
    );
}
