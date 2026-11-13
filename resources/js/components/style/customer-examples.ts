import {
    Bell,
    Languages,
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
 * Example data for the customer-page demos on the style overview page (English,
 * and one Chinese example of each where text length matters). It is not backend
 * data. The three functions at the end stand in for what a backend would
 * answer, as the other demos' `answer` props do: new totals for a cart, and
 * the errors of a checkout step or a contact message, keyed the way Laravel
 * sends them.
 */

// The pages that exist on a real site; here they jump to this page's sections.
export const publicLinks: PublicLink[] = [
    { title: 'Product', href: '#product' },
    { title: 'Pricing', href: '#pricing' },
    { title: 'Help', href: '#help' },
    { title: 'Contact', href: '#contact' },
];

export const barLabelsZh = {
    login: '登录',
    getStarted: '立即开始',
    dashboard: '控制台',
    openMenu: '打开菜单',
    menu: '菜单',
};

export const publicLinksZh: PublicLink[] = [
    { title: '产品', href: '#product' },
    { title: '价格', href: '#pricing' },
    { title: '帮助', href: '#help' },
    { title: '联系我们', href: '#contact' },
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

const featuresZh: Feature[] = [
    {
        icon: LogIn,
        title: '登录',
        description: '注册、登录、重置密码、通行密钥和双重验证。',
    },
    {
        icon: UserRound,
        title: '账户设置',
        description: '个人资料、密码、安全和外观。',
    },
    {
        icon: Bell,
        title: '通知',
        description: '顶栏里的铃铛，和一个显示完整列表的页面。',
    },
    { icon: SunMoon, title: '浅色和深色', description: '外观跟随你的系统。' },
    {
        icon: Languages,
        title: '中文和英文',
        description: '每个页面都能显示两种语言。',
    },
    {
        icon: ListChecks,
        title: '经过检查',
        description: '每次修改完成前都会运行测试和检查。',
    },
];

export const landing = {
    en: {
        title: 'Start building your app',
        summary:
            'A Laravel and React starter with sign-in, settings and notifications in place.',
        primary: 'Get started',
        secondary: 'Log in',
        features,
        closing: 'Ready to build?',
    },
    zh: {
        barLabels: barLabelsZh,
        title: '开始搭建你的应用',
        summary: '一个已经带有登录、设置和通知的 Laravel 和 React 起步项目。',
        primary: '立即开始',
        secondary: '登录',
        features: featuresZh,
        closing: '准备好开始了吗？',
    },
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

const plansZh: Plan[] = [
    {
        id: 'starter',
        name: '入门版',
        recommended: false,
        currency: 'CNY',
        price: { monthly: 0, yearly: 0 },
        features: ['一个项目', '社区支持'],
        action: { label: '选择入门版', href: '#pricing' },
    },
    {
        id: 'team',
        name: '团队版',
        recommended: true,
        currency: 'CNY',
        price: { monthly: 9900, yearly: 99000 },
        features: ['十个项目', '优先支持', '操作记录'],
        action: { label: '选择团队版', href: '#pricing' },
    },
    {
        id: 'business',
        name: '企业版',
        recommended: false,
        currency: 'CNY',
        price: { monthly: 39900, yearly: 399000 },
        features: ['不限项目数量', '专属支持', '单点登录'],
        action: { label: '联系销售', href: '#contact' },
    },
];

export const pricing = {
    en: {
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
    },
    zh: {
        barLabels: barLabelsZh,
        title: '价格',
        plans: plansZh,
        labels: {
            billing: '计费周期',
            monthly: '按月',
            yearly: '按年',
            recommended: '推荐',
            perMonth: '/ 月',
            perYear: '/ 年',
        },
        helpLabel: '对套餐有疑问？',
    },
};

export const product = {
    en: {
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
    },
    zh: {
        barLabels: barLabelsZh,
        title: '备件订阅服务',
        summary: '在你用完之前，把机器需要的备件送到。',
        sections: [
            {
                title: '你会收到什么',
                text: '每月一箱你最常用的备件，按机器分类包装并贴好标签。',
            },
            {
                title: '如何运作',
                text: '告诉我们你使用哪些机器，我们每月一号寄出一箱，你可以随时修改清单。',
            },
        ],
        facts: [
            { label: '送货时间', value: '三个工作日内' },
            { label: '最短期限', value: '没有' },
        ],
        price: { label: '起价', amount: 9900, currency: 'CNY' },
        action: '索取报价',
    },
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

const ordersZh: ExampleOrder[] = [
    {
        id: 1,
        number: '订单 1042',
        status: '已发货',
        variant: 'success',
        placed_on: '2026-11-10',
        total: 52470,
    },
    {
        id: 2,
        number: '订单 1038',
        status: '备货中',
        variant: 'warning',
        placed_on: '2026-11-06',
        total: 18000,
    },
    {
        id: 3,
        number: '订单 1031',
        status: '已送达',
        variant: 'secondary',
        placed_on: '2026-10-28',
        total: 9900,
    },
];

export const customerArea = {
    en: {
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
    },
    zh: {
        barLabels: barLabelsZh,
        nav: [
            { title: '概览', href: '#customer-area', isActive: true },
            { title: '订单', href: '#customer-area' },
            { title: '个人资料', href: '#customer-area' },
        ],
        title: '概览',
        ordersTitle: '最近的订单',
        linksTitle: '快捷入口',
        orders: ordersZh,
        currency: 'CNY',
        columns: {
            order: '订单',
            status: '状态',
            placed: '下单日期',
            total: '合计',
        },
        emptyMessage: '你还没有订单。',
        quickLinks: [
            { title: '通知', href: notificationsIndex(), icon: Bell },
            { title: '个人资料', href: editProfile(), icon: UserRound },
            { title: '安全', href: editSecurity(), icon: ShieldCheck },
        ],
    },
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

const cartItemsZh: LineItem[] = [
    {
        id: 1,
        name: '备件，每箱 20 件',
        unit: 9900,
        quantity: '3',
        total: 29700,
    },
    { id: 2, name: '送货服务', unit: 1500, quantity: '1', total: 1500 },
];

export const checkout = {
    en: {
        title: 'Checkout',
        steps: ['Cart', 'Details', 'Review'] as [string, string, string],
        currency: 'MYR',
        items: cartItems,
        field: 'items',
        details: [
            { id: 'name', label: 'Name' },
            { id: 'email', label: 'Email' },
            { id: 'address', label: 'Delivery address' },
        ],
        paymentNote:
            "The payment provider's own payment element goes here. This page never asks for a card number.",
        placeLabel: 'Place order',
        placed: 'Order placed.',
        nextLabel: 'Next',
        backLabel: 'Back',
        lineLabels: { quantityOf: 'Quantity of', remove: 'Remove' },
        labels: { subtotal: 'Subtotal', tax: 'Tax (6%)', total: 'Total' },
    },
    zh: {
        barLabels: barLabelsZh,
        title: '结账',
        steps: ['购物车', '收货信息', '确认'] as [string, string, string],
        currency: 'CNY',
        items: cartItemsZh,
        // Another array name than the English demo's, so the two carts on one
        // page do not share input names and each focuses its own field.
        field: 'lines',
        details: [
            { id: 'name', label: '姓名' },
            { id: 'email', label: '电子邮箱' },
            { id: 'address', label: '收货地址' },
        ],
        paymentNote:
            '支付服务商自己的支付组件显示在这里。本页不会索要银行卡号。',
        placeLabel: '提交订单',
        placed: '订单已提交。',
        nextLabel: '下一步',
        backLabel: '返回',
        lineLabels: { quantityOf: '数量：', remove: '移除' },
        labels: { subtotal: '小计', tax: '税费（6%）', total: '合计' },
    },
};

// Stands in for the server. Each rule below is written once and every
// stand-in answer calls it: what counts as a quantity, a name and an email.
const isQuantity = (value: string) =>
    Number.isInteger(Number(value)) && Number(value) >= 1;

function nameError(value: string | undefined, zh: boolean) {
    return value?.trim() ? null : zh ? '请输入姓名。' : 'Enter your name.';
}

function emailError(value: string | undefined, zh: boolean) {
    return /^\S+@\S+\.\S+$/.test(value ?? '')
        ? null
        : zh
          ? '请输入像 name@example.com 这样的邮箱。'
          : 'Enter an email address like name@example.com.';
}

/** Only the fields that failed, by name. */
function onlyErrors(found: Record<string, string | null>) {
    return Object.fromEntries(
        Object.entries(found).filter(
            (entry): entry is [string, string] => entry[1] !== null,
        ),
    );
}

// What the server answers to a changed quantity: a line that is not a whole
// number of 1 or more counts as nothing.
export function priceCart(
    items: LineItem[],
    labels: { subtotal: string; tax: string; total: string },
): PricedCart {
    const priced = items.map((item) => ({
        ...item,
        total: isQuantity(item.quantity)
            ? item.unit * Number(item.quantity)
            : 0,
    }));
    const subtotal = priced.reduce((sum, item) => sum + item.total, 0);
    const tax = Math.round(subtotal * 0.06);

    return {
        items: priced,
        rows: [
            { label: labels.subtotal, amount: subtotal },
            { label: labels.tax, amount: tax },
        ],
        total: { label: labels.total, amount: subtotal + tax },
    };
}

// The FormRequest of each checkout step. Errors are keyed as Laravel sends
// them: `items.0.quantity`, `email`.
export function answerCheckout(
    step: number,
    values: Record<string, string>,
    items: LineItem[],
    field = 'items',
    zh = false,
): Record<string, string> {
    if (step === 0) {
        return onlyErrors(
            Object.fromEntries(
                items.map((item, index) => [
                    `${field}.${index}.quantity`,
                    isQuantity(item.quantity)
                        ? null
                        : zh
                          ? `请输入 ${item.name} 的数量，至少为 1。`
                          : `Enter a quantity of 1 or more for ${item.name}.`,
                ]),
            ),
        );
    }

    if (step === 1) {
        return onlyErrors({
            name: nameError(values.name, zh),
            email: emailError(values.email, zh),
            address: values.address?.trim()
                ? null
                : zh
                  ? '请输入收货地址。'
                  : 'Enter the delivery address.',
        });
    }

    return {};
}

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
                answer: 'Open the order and choose Download PDF. The invoice has the same figures as the order.',
            },
            {
                id: 'b2',
                question: 'Which payment methods do you accept?',
                answer: 'The payment provider shows the methods it supports when you place the order.',
            },
        ],
    },
];

const faqTopicsZh: FaqTopic[] = [
    {
        id: 'orders',
        title: '订单',
        items: [
            {
                id: 'o1',
                question: '我的订单到哪里了？',
                answer: '打开账户里的“订单”。每个订单都会显示状态，发货后还会显示物流单号。',
            },
            {
                id: 'o2',
                question: '下单后还能修改订单吗？',
                answer: '可以，在订单打包之前都能修改。请联系我们，并告诉我们订单号和要修改的内容。',
            },
        ],
    },
    {
        id: 'billing',
        title: '账单',
        items: [
            {
                id: 'b1',
                question: '如何获取发票？',
                answer: '打开订单并选择“下载 PDF”。发票上的数字和订单完全一致。',
            },
            {
                id: 'b2',
                question: '支持哪些付款方式？',
                answer: '下单时，支付服务商会显示它支持的付款方式。',
            },
        ],
    },
];

export const help = {
    en: {
        title: 'Help',
        topics: faqTopics,
        searchLabel: 'Search help',
        emptyMessage: 'No questions match your search.',
        stillNeedHelp: 'Still need help?',
        contactLabel: 'Contact us',
    },
    zh: {
        barLabels: barLabelsZh,
        title: '帮助',
        topics: faqTopicsZh,
        searchLabel: '搜索帮助',
        emptyMessage: '没有符合搜索的问题。',
        stillNeedHelp: '还需要帮助？',
        contactLabel: '联系我们',
    },
};

// The FormRequest of the contact form.
export function answerContact(
    values: Record<string, string>,
    zh = false,
): Record<string, string> {
    return onlyErrors({
        name: nameError(values.name, zh),
        email: emailError(values.email, zh),
        subject: values.subject?.trim()
            ? null
            : zh
              ? '请输入主题。'
              : 'Enter a subject.',
        message: values.message?.trim()
            ? null
            : zh
              ? '请写下你的留言。'
              : 'Write your message.',
    });
}

export const contact = {
    en: {
        title: 'Contact us',
        sent: 'Message sent.',
        labels: {
            name: 'Name',
            email: 'Email',
            subject: 'Subject',
            message: 'Message',
        },
        submitLabel: 'Send message',
    },
    zh: {
        barLabels: barLabelsZh,
        title: '联系我们',
        sent: '留言已发送。',
        labels: {
            name: '姓名',
            email: '电子邮箱',
            subject: '主题',
            message: '留言',
        },
        submitLabel: '发送留言',
    },
};
