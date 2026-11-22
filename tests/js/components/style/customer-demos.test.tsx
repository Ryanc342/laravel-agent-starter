import { fireEvent, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { expect, test, vi } from 'vite-plus/test';
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
import HelpDemo from '@/components/style/help-demo';
import LandingDemo from '@/components/style/landing-demo';
import PricingDemo from '@/components/style/pricing-demo';
import ProductDemo from '@/components/style/product-demo';
import { TooltipProvider } from '@/components/ui/tooltip';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('sonner', () => ({ toast: { success: vi.fn(), error: vi.fn() } }));
vi.mock('@inertiajs/react', () => ({
    router: { get: vi.fn() },
    useFormContext: () => undefined,
    usePage: () => ({
        url: '/style',
        props: {
            name: 'Acme',
            auth: { user: null },
            today: { date: '2026-11-14', year: 2026 },
        },
    }),
    // Only what reaches the DOM: Inertia's own options (prefetch) stay out, as the real Link keeps them.
    Link: ({
        href,
        children,
        className,
        'aria-current': current,
        'aria-label': label,
    }: {
        href: unknown;
        children: ReactNode;
        className?: string;
        'aria-current'?: 'page' | 'true';
        'aria-label'?: string;
    }) => (
        <a
            href={
                typeof href === 'string' ? href : (href as { url: string }).url
            }
            className={className}
            aria-current={current}
            aria-label={label}
        >
            {children}
        </a>
    ),
}));

function withTooltips(node: ReactNode) {
    return render(<TooltipProvider>{node}</TooltipProvider>);
}

test('the checkout shows its three steps as fixed states, with the summary the server priced', () => {
    withTooltips(<CheckoutDemo links={publicLinks} {...checkout} />);

    expect(screen.getAllByRole('list', { name: 'Steps' })).toHaveLength(3);
    expect(
        screen.getByRole('spinbutton', { name: /Quantity of Spare parts/ }),
    ).toBeTruthy();
    expect(screen.getAllByText('Subtotal')).toHaveLength(3);
    expect(
        screen.getAllByText('Total')[0].nextElementSibling?.textContent,
    ).toContain('524.70');
});

test('the details step shows the errors of the data on their fields', () => {
    withTooltips(<CheckoutDemo links={publicLinks} {...checkout} />);

    expect(
        screen
            .getByRole('textbox', { name: 'Email' })
            .getAttribute('aria-invalid'),
    ).toBe('true');
    expect(
        screen
            .getByRole('textbox', { name: 'Delivery address' })
            .getAttribute('aria-invalid'),
    ).toBeNull();
});

test('the review shows the details and leaves payment to the provider: no card field, the last button pays', () => {
    withTooltips(<CheckoutDemo links={publicLinks} {...checkout} />);

    expect(screen.getByText('Tan Mei Ling')).toBeTruthy();
    expect(screen.getByText(/payment element goes here/)).toBeTruthy();
    expect(
        document.querySelector('[autocomplete="cc-number"], [name*="card"]'),
    ).toBeNull();
    expect(
        screen.getByRole('button', { name: 'Pay' }).getAttribute('type'),
    ).toBe('submit');
});

test('the contact page shows the empty form and the one the server sent back with errors', () => {
    render(<ContactDemo links={publicLinks} {...contact} />);

    const emails = screen.getAllByLabelText('Email');

    expect(emails).toHaveLength(2);
    expect(emails[0].getAttribute('aria-invalid')).toBeNull();
    expect(emails[1].getAttribute('aria-invalid')).toBe('true');
    expect(
        (screen.getAllByLabelText('Name')[1] as HTMLInputElement).value,
    ).toBe('Tan Mei Ling');
});

test('the landing demo shows the hero in the display size, the features and a closing button', () => {
    render(<LandingDemo links={publicLinks} {...landing} />);

    expect(screen.getByRole('heading', { level: 1 }).textContent).toBe(
        'Start building your app',
    );
    expect(screen.getAllByRole('listitem').length).toBeGreaterThanOrEqual(3);
    expect(
        screen.getAllByRole('link', { name: 'Get started' }).length,
    ).toBeGreaterThan(1);
});

test('the pricing demo has the billing choice and the plans', () => {
    render(<PricingDemo links={publicLinks} {...pricing} />);

    expect(screen.getByRole('radio', { name: 'Yearly' })).toBeTruthy();
    expect(screen.getByText('Recommended')).toBeTruthy();
    expect(screen.getByText('Team')).toBeTruthy();
    expect(document.body.textContent).toContain('49.00');
});

test('the product demo alternates its sections and ends with one button', () => {
    render(<ProductDemo links={publicLinks} {...product} />);

    const sections = document.querySelectorAll('section');

    expect(sections).toHaveLength(2);
    expect(screen.getAllByRole('img', { name: 'Picture' })).toHaveLength(2);
    expect(screen.getByRole('link', { name: 'Request a quote' })).toBeTruthy();
});

test('the customer area demo has Dashboard in the bar, the account navigation and the orders as a list', () => {
    const { container } = render(
        <CustomerAreaDemo links={publicLinks} {...customerArea} />,
    );

    expect(screen.getByRole('link', { name: 'Dashboard' })).toBeTruthy();
    expect(screen.queryByRole('link', { name: 'Log in' })).toBeNull();
    expect(screen.getByRole('navigation', { name: 'Account' })).toBeTruthy();
    expect(
        screen
            .getByRole('link', { name: 'Overview' })
            .getAttribute('aria-current'),
    ).toBe('page');
    expect(container.querySelectorAll('tbody tr')).toHaveLength(3);
});

test('the help demo searches its questions and links to the contact page', () => {
    render(<HelpDemo links={publicLinks} {...help} />);

    fireEvent.change(screen.getByRole('searchbox', { name: 'Search help' }), {
        target: { value: 'invoice' },
    });

    expect(
        screen
            .getAllByRole('button')
            .filter((button) => button.hasAttribute('aria-expanded')),
    ).toHaveLength(1);
    expect(
        screen.getByRole('link', { name: 'Contact us', description: '' }),
    ).toBeTruthy();
});
