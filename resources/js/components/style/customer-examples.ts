import {
    Bell,
    ListChecks,
    LogIn,
    SunMoon,
    UserRound,
    ShieldCheck,
} from 'lucide-react';
import type { Feature } from '@/components/shared/feature-grid';
import type { FaqTopic } from '@/components/shared/faq-list';
import type { LineItem } from '@/components/shared/line-items';
import type { Plan } from '@/components/shared/pricing-plans';
import type { PublicLink } from '@/components/shared/public-header';
import type { PricedCart } from '@/components/style/checkout-demo';
import type { ExampleOrder } from '@/components/style/customer-area-demo';
import { index as notificationsIndex } from '@/routes/notifications';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

/**
 * Example data for the customer-page demos on the style overview page. It is
 * not backend data, but it is what a backend would send: the priced cart, the
 * errors of a checkout step or a contact message keyed the way Laravel sends
 * them. Nothing here is worked out.
 */

// The pages that exist on a real site; here they jump to this page's sections.
export const publicLinks: PublicLink[] = [
    { title: 'Product', href: '#product' },
    { title: 'Pricing', href: '#pricing' },
    { title: 'Help', href: '#help' },
    { title: 'Contact', href: '#contact' },
];

const features: Feature[] = [
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
        icon: ListChecks,
        title: 'Checked',
        description: 'Tests and checks run before every change is done.',
    },
];

export const landing = {
    title: 'Start building your app',
    summary:
        'A Laravel and React starter with sign-in, settings and notifications in place.',
    primary: 'Get started',
    secondary: 'Log in',
    features,
    closing: 'Ready to build?',
};

const plans: Plan[] = [
    {
        id: 'starter',
        name: 'Starter',
        recommended: false,
        currency: 'MYR',
        price: { monthly: 0, yearly: 0 },
        features: ['One project', 'Community support'],
        action: { label: 'Choose Starter', href: '#pricing' },
    },
    {
        id: 'team',
        name: 'Team',
        recommended: true,
        currency: 'MYR',
        price: { monthly: 4900, yearly: 49000 },
        features: ['Ten projects', 'Priority support', 'Audit log'],
        action: { label: 'Choose Team', href: '#pricing' },
    },
    {
        id: 'business',
        name: 'Business',
        recommended: false,
        currency: 'MYR',
        price: { monthly: 19900, yearly: 199000 },
        features: ['Unlimited projects', 'Dedicated support', 'Single sign-on'],
        action: { label: 'Contact sales', href: '#contact' },
    },
];

export const pricing = {
    title: 'Pricing',
    plans,
    labels: {
        billing: 'Billing period',
        monthly: 'Monthly',
        yearly: 'Yearly',
        recommended: 'Recommended',
        perMonth: '/ month',
        perYear: '/ year',
    },
    helpLabel: 'Questions about plans?',
};

export const product = {
    title: 'Spare parts subscription',
    summary: 'Parts for your machines, delivered before you run out.',
    sections: [
        {
            title: 'What you get',
            text: 'A monthly box of the parts you use most, packed and labelled by machine.',
        },
        {
            title: 'How it works',
            text: 'Tell us which machines you run. We send a box on the first of each month, and you change the list whenever you like.',
        },
    ],
    facts: [
        { label: 'Delivery', value: 'Within 3 working days' },
        { label: 'Minimum term', value: 'None' },
    ],
    price: { label: 'From', amount: 15000, currency: 'MYR' },
    action: 'Request a quote',
};

const orders: ExampleOrder[] = [
    {
        id: 1,
        number: 'Order 1042',
        status: 'Shipped',
        variant: 'success',
        placed_on: '2026-11-10',
        total: 52470,
    },
    {
        id: 2,
        number: 'Order 1038',
        status: 'Packing',
        variant: 'warning',
        placed_on: '2026-11-06',
        total: 18000,
    },
    {
        id: 3,
        number: 'Order 1031',
        status: 'Delivered',
        variant: 'secondary',
        placed_on: '2026-10-28',
        total: 9900,
    },
];

export const customerArea = {
    nav: [
        { title: 'Overview', href: '#customer-area', isActive: true },
        { title: 'Orders', href: '#customer-area' },
        { title: 'Profile', href: '#customer-area' },
    ],
    title: 'Overview',
    ordersTitle: 'Recent orders',
    linksTitle: 'Quick links',
    orders,
    currency: 'MYR',
    columns: {
        order: 'Order',
        status: 'Status',
        placed: 'Placed',
        total: 'Total',
    },
    emptyMessage: 'You have no orders yet.',
    quickLinks: [
        { title: 'Notifications', href: notificationsIndex(), icon: Bell },
        { title: 'Profile', href: editProfile(), icon: UserRound },
        { title: 'Security', href: editSecurity(), icon: ShieldCheck },
    ],
};

const cartItems: LineItem[] = [
    {
        id: 1,
        name: 'Spare parts, box of 20',
        unit: 15000,
        quantity: '3',
        total: 45000,
    },
    { id: 2, name: 'Delivery', unit: 4500, quantity: '1', total: 4500 },
];

export const checkout = {
    title: 'Checkout',
    steps: ['Cart', 'Details', 'Review'] as [string, string, string],
    currency: 'MYR',
    // The cart as the server priced it.
    cart: {
        items: cartItems,
        rows: [
            { label: 'Subtotal', amount: 49500 },
            { label: 'Tax (6%)', amount: 2970 },
        ],
        total: { label: 'Total', amount: 52470 },
    } satisfies PricedCart,
    field: 'items',
    details: [
        { id: 'name', label: 'Name' },
        { id: 'email', label: 'Email' },
        { id: 'address', label: 'Delivery address' },
    ],
    // What was typed in Details, and the errors the server answered with.
    typed: { name: '', email: 'mei@example', address: 'Jalan Ampang 12' },
    detailErrors: {
        name: 'Enter your name.',
        email: 'Enter an email address like name@example.com.',
    },
    filled: {
        name: 'Tan Mei Ling',
        email: 'mei@example.com',
        address: 'Jalan Ampang 12, Kuala Lumpur',
    },
    paymentNote:
        "The payment provider's own payment element goes here. This page never asks for a card number.",
};

const faqTopics: FaqTopic[] = [
    {
        id: 'orders',
        title: 'Orders',
        items: [
            {
                id: 'o1',
                question: 'Where is my order?',
                answer: 'Open Orders in your account. Each order shows its status and, once it ships, its tracking number.',
            },
            {
                id: 'o2',
                question: 'Can I change an order after I placed it?',
                answer: 'Yes, until it is packed. Contact us with the order number and what you want to change.',
            },
        ],
    },
    {
        id: 'billing',
        title: 'Billing',
        items: [
            {
                id: 'b1',
                question: 'How do I get an invoice?',
                answer: 'Open the order and choose Print. The invoice has the same figures as the order.',
            },
            {
                id: 'b2',
                question: 'Which payment methods do you accept?',
                answer: 'The payment provider shows the methods it supports when you place the order.',
            },
        ],
    },
];

export const help = {
    title: 'Help',
    topics: faqTopics,
    searchLabel: 'Search help',
    emptyMessage: 'No questions match your search.',
    stillNeedHelp: 'Still need help?',
    contactLabel: 'Contact us',
};

export const contact = {
    title: 'Contact us',
    // What was typed, and the errors the server answered with.
    typed: {
        name: 'Tan Mei Ling',
        email: 'mei@example',
        subject: '',
        message: 'Where is order 1042?',
    },
    errors: {
        email: 'Enter an email address like name@example.com.',
        subject: 'Enter a subject.',
    },
};
