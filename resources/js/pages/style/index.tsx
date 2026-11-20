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
import CheckoutDemo from '@/components/style/checkout-demo';
import ContactDemo from '@/components/style/contact-demo';
import CustomerAreaDemo from '@/components/style/customer-area-demo';
import {
    answerCheckout,
    answerContact,
    checkout,
    contact,
    customerArea,
    help,
    landing,
    priceCart,
    pricing,
    product,
    publicLinks,
    publicLinksZh,
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
import StyleSection, {
    DemoFrame,
    Variant,
} from '@/components/style/style-section';
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
import { t, msg } from '@/lib/i18n';

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
        name: msg('Team'),
        fields: [{ id: 'team-name', label: msg('Team name') }],
    },
    {
        name: msg('Members'),
        fields: [
            { id: 'member-1', label: msg('First member') },
            { id: 'member-2', label: msg('Second member') },
        ],
    },
    {
        name: msg('Review'),
        fields: [{ id: 'team-note', label: msg('Note for the team') }],
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
            .map((field) => [
                field.id,
                t('Enter :field.', { field: t(field.label).toLowerCase() }),
            ]),
    );

// Stands in for what the backend sends with each state of the request: the
// label, the badge and the `can.*` flags.
const approvalStates: Record<
    'pending' | 'approved' | 'rejected',
    ApprovalState
> = {
    pending: {
        label: msg('Pending'),
        variant: 'warning',
        can: { approve: true, reject: true },
    },
    approved: {
        label: msg('Approved'),
        variant: 'success',
        can: { approve: false, reject: false },
    },
    rejected: {
        label: msg('Rejected'),
        variant: 'destructive',
        can: { approve: false, reject: false },
    },
};

// A list without a detail page: its rows carry their own actions.
const categories: ExampleCategory[] = [
    { id: 1, name: 'Office supplies', expenses: 12 },
    { id: 2, name: 'Travel', expenses: 7 },
    { id: 3, name: 'Meals with clients', expenses: 4 },
];

// Stands in for the FormRequest that checks a category's name.
const answerCategoryName = (name: string) =>
    name.trim() ? null : t('Enter a name for the category.');

// Stands in for the FormRequest that checks the reason.
const answerRejection = (reason: string) =>
    reason.trim() ? null : t('Enter a reason.');

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
    { label: msg('Customer'), value: 'Invoice Desk Sdn Bhd' },
    { label: msg('Order date'), value: '4 Nov 2026' },
    { label: msg('Delivery to'), value: 'Jalan Ampang 12, Kuala Lumpur' },
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
            name: 'Website design',
            unit: 800000,
            total: 0,
            quantity: '1',
        },
        {
            id: 2,
            name: 'Front-end development (days)',
            unit: 120000,
            total: 0,
            quantity: '12',
        },
        {
            id: 3,
            name: 'Back-end development (days)',
            unit: 130000,
            total: 0,
            quantity: '10',
        },
        {
            id: 4,
            name: 'Server (months)',
            unit: 45000,
            total: 0,
            quantity: '3',
        },
        { id: 5, name: 'Domain renewal', unit: 12000, total: 0, quantity: '1' },
        {
            id: 6,
            name: 'Launch support (days)',
            unit: 100000,
            total: 0,
            quantity: '2',
        },
    ],
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
        label: msg('New'),
        cards: [
            { id: 'o1', title: 'Order 1041, spare parts', owner: 'Mei Ling' },
            { id: 'o2', title: 'Order 1042, delivery to Ipoh', owner: 'Ahmad' },
        ],
    },
    {
        id: 'packing',
        label: msg('Packing'),
        cards: [{ id: 'o3', title: 'Order 1040, 20 boxes', owner: 'Siti' }],
    },
    { id: 'shipped', label: msg('Shipped'), cards: [] },
];

const menu = [
    ['tokens', msg('Tokens')],
    ['ui', msg('UI components')],
    ['shared', msg('Shared blocks')],
    ['list', msg('List')],
    ['detail', msg('Detail')],
    ['detail-tabs', msg('Detail with tabs')],
    ['form', msg('Form')],
    ['wizard', msg('Multi-step wizard')],
    ['approval', msg('Approval')],
    ['list-detail', msg('List beside detail')],
    ['bulk-actions', msg('Bulk actions')],
    ['import-export', msg('Import and export')],
    ['search', msg('Search results')],
    ['report', msg('Charts and reports')],
    ['calendar', msg('Calendar')],
    ['kanban', msg('Kanban')],
    ['print', msg('Print page')],
    ['states', msg('States')],
    ['dashboard', msg('Dashboard')],
    ['settings', msg('Settings')],
    ['feed', msg('Feed')],
    ['auth', msg('Login and register')],
    ['landing', msg('Home / landing')],
    ['pricing', msg('Pricing')],
    ['product', msg('Product page')],
    ['customer-area', msg('Customer area')],
    ['checkout', msg('Checkout')],
    ['help', msg('Help / FAQ')],
    ['contact', msg('Contact form')],
    ['error-page', msg('Error pages')],
];

/** Example data: the names of the demo records, shown as they are. */
const exampleRecord = { order: 'Order 1042', claim: 'Expense claim 218' };

export default function Style() {
    return (
        <>
            <Head title={t('Style overview')} />

            <div className="mx-auto flex max-w-5xl flex-col gap-12 p-4 md:p-6">
                <header className="flex flex-col gap-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <Heading title={t('Style overview')} />
                        <AppearanceToggleTab />
                    </div>

                    <nav
                        aria-label={t('Sections')}
                        className="flex flex-wrap gap-x-5 gap-y-2 text-sm"
                    >
                        {menu.map(([id, label]) => (
                            <a
                                key={id}
                                href={`#${id}`}
                                className="text-muted-foreground transition-colors hover:text-foreground active:text-primary-pressed"
                            >
                                {t(label)}
                            </a>
                        ))}
                    </nav>
                </header>

                <StyleSection id="tokens" title={t('Tokens')}>
                    <TokensDemo />
                </StyleSection>

                <StyleSection id="ui" title={t('UI components')}>
                    <UiActionsDemo />
                    <UiFieldsDemo />
                    <UiSurfacesDemo />
                </StyleSection>

                <StyleSection id="shared" title={t('Shared blocks')}>
                    <SharedDemo tasks={tasks} pagination={pagination} />
                </StyleSection>

                <StyleSection id="list" title={t('List')}>
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
                            answer={answerCategoryName}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="detail" title={t('Detail')}>
                    <DemoFrame>
                        <DetailDemo task={tasks[0]} />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="detail-tabs" title={t('Detail with tabs')}>
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

                <StyleSection id="form" title={t('Form')}>
                    <FormDemo
                        task={tasks[1]}
                        requiredMessage={t('The title field is required.')}
                        invoice={invoiceForm}
                        price={(items) =>
                            priceCart(items, {
                                subtotal: t('Subtotal'),
                                tax: t('Tax (6%)'),
                                total: t('Total'),
                            })
                        }
                    />
                </StyleSection>

                <StyleSection id="wizard" title={t('Multi-step wizard')}>
                    <DemoFrame>
                        <WizardDemo
                            title={t('Set up team')}
                            steps={wizardSteps}
                            submitLabel={t('Create team')}
                            answer={answerWizardStep}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="approval" title={t('Approval')}>
                    <DemoFrame>
                        <ApprovalDemo
                            title={exampleRecord.claim}
                            facts={[
                                { label: t('Amount'), value: 'RM 480.00' },
                                { label: t('Requested by'), value: 'Mei Ling' },
                            ]}
                            events={approvalEvents}
                            states={approvalStates}
                            answer={answerRejection}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="list-detail" title={t('List beside detail')}>
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

                <StyleSection id="bulk-actions" title={t('Bulk actions')}>
                    <DemoFrame>
                        <BulkActionsDemo
                            tasks={tasks}
                            total={pagination.meta?.total ?? tasks.length}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="import-export" title={t('Import and export')}>
                    <DemoFrame>
                        <ImportExportDemo
                            rowErrors={[
                                t('Row :index: Due date is not a date.', {
                                    index: 2,
                                }),
                                t('Row :index: Title is required.', {
                                    index: 3,
                                }),
                                t('Row :index: Due date is not a date.', {
                                    index: 4,
                                }),
                                t('Row :index: Due date is not a date.', {
                                    index: 5,
                                }),
                                t('Row :index: Due date is not a date.', {
                                    index: 6,
                                }),
                                t('Row :index: Due date is not a date.', {
                                    index: 7,
                                }),
                                t('Row :index: Title is required.', {
                                    index: 8,
                                }),
                            ]}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="search" title={t('Search results')}>
                    <DemoFrame flush>
                        <SearchDemo
                            query="invoice"
                            action={style().url}
                            groups={searchGroups}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="report" title={t('Charts and reports')}>
                    <DemoFrame>
                        <ReportDemo
                            tiles={reportTiles}
                            daily={reportDaily}
                            dailySeries={[{ key: 'done', label: t('Done') }]}
                            teams={reportTeams}
                            teamSeries={[
                                { key: 'open', label: t('Open') },
                                { key: 'done', label: t('Done') },
                                { key: 'overdue', label: t('Overdue') },
                            ]}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="calendar" title={t('Calendar')}>
                    <DemoFrame>
                        <CalendarDemo
                            events={calendarEvents}
                            today="2026-11-14"
                            month="2026-11"
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="kanban" title={t('Kanban')}>
                    <DemoFrame>
                        <KanbanDemo columns={kanbanColumns} />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="print" title={t('Print page')}>
                    <DemoFrame>
                        <PrintDemo invoice={invoice} />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="states" title={t('States')}>
                    <StatesDemo subject={t('tasks')} />
                </StyleSection>

                <StyleSection id="dashboard" title={t('Dashboard')}>
                    <DemoFrame>
                        <DashboardDemo
                            notifications={notifications}
                            links={links}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="settings" title={t('Settings')}>
                    <DemoFrame flush>
                        <SettingsDemo
                            name="Test User"
                            email="test@example.com"
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="feed" title={t('Feed')}>
                    <DemoFrame>
                        <FeedDemo notifications={notifications} />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="auth" title={t('Login and register')}>
                    <DemoFrame>
                        <AuthDemo
                            title={t('Log in to your account')}
                            fields={[
                                {
                                    id: 'email',
                                    label: t('Email address'),
                                    type: 'email',
                                    placeholder: 'email@example.com',
                                },
                                {
                                    id: 'password',
                                    label: t('Password'),
                                    type: 'password',
                                    placeholder: t('Password'),
                                },
                            ]}
                            submitLabel={t('Log in')}
                            footer={t("Don't have an account?")}
                            footerLink={t('Sign up')}
                        />
                    </DemoFrame>
                </StyleSection>

                <StyleSection id="landing" title={t('Home / landing')}>
                    <Variant label={t('English')}>
                        <LandingDemo links={publicLinks} {...landing.en} />
                    </Variant>
                    <Variant label={t('Chinese')}>
                        <LandingDemo links={publicLinksZh} {...landing.zh} />
                    </Variant>
                </StyleSection>

                <StyleSection id="pricing" title={t('Pricing')}>
                    <Variant label={t('English')}>
                        <PricingDemo links={publicLinks} {...pricing.en} />
                    </Variant>
                    <Variant label={t('Chinese')}>
                        <PricingDemo links={publicLinksZh} {...pricing.zh} />
                    </Variant>
                </StyleSection>

                <StyleSection id="product" title={t('Product page')}>
                    <Variant label={t('English')}>
                        <ProductDemo links={publicLinks} {...product.en} />
                    </Variant>
                    <Variant label={t('Chinese')}>
                        <ProductDemo links={publicLinksZh} {...product.zh} />
                    </Variant>
                </StyleSection>

                <StyleSection id="customer-area" title={t('Customer area')}>
                    <Variant label={t('English')}>
                        <CustomerAreaDemo
                            links={publicLinks}
                            {...customerArea.en}
                        />
                    </Variant>
                    <Variant label={t('Chinese')}>
                        <CustomerAreaDemo
                            links={publicLinksZh}
                            {...customerArea.zh}
                        />
                    </Variant>
                </StyleSection>

                <StyleSection id="checkout" title={t('Checkout')}>
                    <Variant label={t('English')}>
                        <CheckoutDemo
                            links={publicLinks}
                            {...checkout.en}
                            price={(items) =>
                                priceCart(items, checkout.en.labels)
                            }
                            answer={answerCheckout}
                        />
                    </Variant>
                    <Variant label={t('Chinese')}>
                        <CheckoutDemo
                            links={publicLinksZh}
                            {...checkout.zh}
                            price={(items) =>
                                priceCart(items, checkout.zh.labels)
                            }
                            answer={(step, values, items) =>
                                answerCheckout(
                                    step,
                                    values,
                                    items,
                                    'lines',
                                    true,
                                )
                            }
                        />
                    </Variant>
                </StyleSection>

                <StyleSection id="help" title={t('Help / FAQ')}>
                    <Variant label={t('English')}>
                        <HelpDemo links={publicLinks} {...help.en} />
                    </Variant>
                    <Variant label={t('Chinese')}>
                        <HelpDemo links={publicLinksZh} {...help.zh} />
                    </Variant>
                </StyleSection>

                <StyleSection id="contact" title={t('Contact form')}>
                    <Variant label={t('English')}>
                        <ContactDemo
                            links={publicLinks}
                            {...contact.en}
                            answer={answerContact}
                        />
                    </Variant>
                    <Variant label={t('Chinese')}>
                        <ContactDemo
                            links={publicLinksZh}
                            {...contact.zh}
                            answer={(values) => answerContact(values, true)}
                        />
                    </Variant>
                </StyleSection>

                <StyleSection id="error-page" title={t('Error pages')}>
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
