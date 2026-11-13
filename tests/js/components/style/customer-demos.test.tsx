import { fireEvent, render, screen, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
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
import HelpDemo from '@/components/style/help-demo';
import LandingDemo from '@/components/style/landing-demo';
import PricingDemo from '@/components/style/pricing-demo';
import ProductDemo from '@/components/style/product-demo';
import { TooltipProvider } from '@/components/ui/tooltip';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('sonner', () => ({ toast: { success: vi.fn(), error: vi.fn() } }));
vi.mock('@inertiajs/react', () => ({
    router: { get: vi.fn() },
    usePage: () => ({
        url: '/style',
        props: { name: 'Acme', auth: { user: null } },
    }),
    Link: ({
        href,
        children,
        ...rest
    }: {
        href: unknown;
        children: ReactNode;
    }) => (
        <a
            href={
                typeof href === 'string' ? href : (href as { url: string }).url
            }
            {...rest}
        >
            {children}
        </a>
    ),
}));

const failed = vi.fn();
const listen = (event: Event) => failed((event as CustomEvent).detail.errors);

beforeEach(() => {
    failed.mockReset();
    vi.mocked(toast.success).mockClear();
    document.addEventListener('inertia:error', listen);
});

afterEach(() => {
    document.removeEventListener('inertia:error', listen);
});

function withTooltips(node: ReactNode) {
    return render(<TooltipProvider>{node}</TooltipProvider>);
}

function renderCheckout(zh = false) {
    const data = zh ? checkout.zh : checkout.en;

    return withTooltips(
        <CheckoutDemo
            links={zh ? publicLinksZh : publicLinks}
            {...data}
            price={(items) => priceCart(items, data.labels)}
            answer={(step, values, items) =>
                answerCheckout(step, values, items, zh ? 'lines' : 'items', zh)
            }
        />,
    );
}

test('the checkout starts at the cart with its steps and a summary the server priced', () => {
    renderCheckout();

    const steps = screen.getByRole('list', { name: 'Steps' });

    expect(within(steps).getAllByRole('listitem')).toHaveLength(3);
    expect(
        screen.getByRole('spinbutton', { name: /Quantity of Spare parts/ }),
    ).toBeTruthy();
    expect(screen.getByText('Subtotal')).toBeTruthy();
    expect(screen.getByText('Tax (6%)')).toBeTruthy();
});

test('changing a quantity asks the stand-in server for new totals', () => {
    renderCheckout();

    const before = screen.getByText('Total').nextElementSibling?.textContent;

    fireEvent.change(
        screen.getByRole('spinbutton', { name: /Quantity of Spare parts/ }),
        { target: { value: '4' } },
    );

    expect(screen.getByText('Total').nextElementSibling?.textContent).not.toBe(
        before,
    );
});

test('a bad quantity fires the validation event with the Laravel key and stays on the cart', () => {
    renderCheckout();

    fireEvent.change(
        screen.getByRole('spinbutton', { name: /Quantity of Spare parts/ }),
        { target: { value: '0' } },
    );
    fireEvent.click(screen.getByRole('button', { name: 'Next' }));

    expect(failed).toHaveBeenCalledTimes(1);
    expect(Object.keys(failed.mock.calls[0][0])).toEqual(['items.0.quantity']);
    expect(
        screen
            .getByRole('spinbutton', { name: /Quantity of Spare parts/ })
            .getAttribute('aria-invalid'),
    ).toBe('true');
    expect(screen.queryByRole('textbox', { name: 'Name' })).toBeNull();
});

test('the steps go Cart, Details, Review; details are checked; there is no card field; the last button places the order', () => {
    renderCheckout();

    fireEvent.click(screen.getByRole('button', { name: 'Next' }));
    expect(screen.getByRole('textbox', { name: 'Name' })).toBeTruthy();

    fireEvent.click(screen.getByRole('button', { name: 'Next' }));
    expect(Object.keys(failed.mock.calls[0][0])).toEqual([
        'name',
        'email',
        'address',
    ]);

    fireEvent.change(screen.getByRole('textbox', { name: 'Name' }), {
        target: { value: 'Mei Ling' },
    });
    fireEvent.change(screen.getByRole('textbox', { name: 'Email' }), {
        target: { value: 'mei@example.com' },
    });
    fireEvent.change(
        screen.getByRole('textbox', { name: 'Delivery address' }),
        { target: { value: 'Jalan Ampang 12' } },
    );
    fireEvent.click(screen.getByRole('button', { name: 'Next' }));

    expect(screen.getByText('Mei Ling')).toBeTruthy();
    expect(screen.getByText(/payment element goes here/)).toBeTruthy();
    expect(
        document.querySelector('[autocomplete="cc-number"], [name*="card"]'),
    ).toBeNull();

    fireEvent.click(screen.getByRole('button', { name: 'Place order' }));
    expect(toast.success).toHaveBeenCalledWith('Order placed.');
    expect(
        screen.getByRole('spinbutton', { name: /Quantity of Spare parts/ }),
    ).toBeTruthy();
});

test('the Chinese checkout has Chinese steps, errors and CNY amounts', () => {
    renderCheckout(true);

    expect(screen.getByRole('list', { name: 'Steps' }).textContent).toContain(
        '购物车',
    );

    fireEvent.change(
        screen.getByRole('spinbutton', { name: /Quantity of 备件/ }),
        { target: { value: '' } },
    );
    fireEvent.click(screen.getByRole('button', { name: 'Next' }));

    expect(failed.mock.calls[0][0]['lines.0.quantity']).toContain('请输入');
    expect(document.body.textContent).toContain('CN¥');
});

test('an empty contact form fires the event with all four errors; a good one says it was sent and clears itself', () => {
    render(
        <ContactDemo
            links={publicLinks}
            {...contact.en}
            answer={answerContact}
        />,
    );

    fireEvent.click(screen.getByRole('button', { name: 'Send message' }));
    expect(Object.keys(failed.mock.calls[0][0])).toEqual([
        'name',
        'email',
        'subject',
        'message',
    ]);
    expect(screen.getByLabelText('Email').getAttribute('aria-invalid')).toBe(
        'true',
    );

    fireEvent.change(screen.getByLabelText('Name'), {
        target: { value: 'Mei Ling' },
    });
    fireEvent.change(screen.getByLabelText('Email'), {
        target: { value: 'mei@example.com' },
    });
    fireEvent.change(screen.getByLabelText('Subject'), {
        target: { value: 'Order 1042' },
    });
    fireEvent.change(screen.getByLabelText('Message'), {
        target: { value: 'Where is it?' },
    });
    fireEvent.click(screen.getByRole('button', { name: 'Send message' }));

    expect(failed).toHaveBeenCalledTimes(1);
    expect(toast.success).toHaveBeenCalledWith('Message sent.');
    expect((screen.getByLabelText('Name') as HTMLInputElement).value).toBe('');
});

test('a failed contact send keeps what was typed', () => {
    render(
        <ContactDemo
            links={publicLinks}
            {...contact.en}
            answer={answerContact}
        />,
    );

    fireEvent.change(screen.getByLabelText('Name'), {
        target: { value: 'Mei Ling' },
    });
    fireEvent.click(screen.getByRole('button', { name: 'Send message' }));

    expect((screen.getByLabelText('Name') as HTMLInputElement).value).toBe(
        'Mei Ling',
    );
});

test('the landing demo shows the hero in the display size, the features and a closing button, in English and in Chinese', () => {
    const { unmount } = render(
        <LandingDemo links={publicLinks} {...landing.en} />,
    );

    expect(screen.getByRole('heading', { level: 1 }).className).toContain(
        'text-display',
    );
    expect(screen.getAllByRole('listitem').length).toBeGreaterThanOrEqual(3);
    expect(
        screen.getAllByRole('link', { name: 'Get started' }).length,
    ).toBeGreaterThan(1);
    unmount();

    render(<LandingDemo links={publicLinksZh} {...landing.zh} />);
    expect(screen.getByRole('heading', { level: 1 }).textContent).toBe(
        '开始搭建你的应用',
    );
});

test('the pricing demo has the title in the display size and the plans, in Chinese with yuan', () => {
    render(<PricingDemo links={publicLinksZh} {...pricing.zh} />);

    expect(screen.getByRole('heading', { level: 1 }).className).toContain(
        'text-display',
    );
    expect(screen.getByText('团队版')).toBeTruthy();
    expect(document.body.textContent).toContain('CN¥99.00');
});

test('the product demo alternates its sections and ends with one button', () => {
    render(<ProductDemo links={publicLinks} {...product.en} />);

    const sections = document.querySelectorAll('section');
    const second = sections[1].firstElementChild as HTMLElement;

    expect(sections).toHaveLength(2);
    expect(
        (sections[0].firstElementChild as HTMLElement).className,
    ).not.toContain('md:order-last');
    expect(second.className).toContain('md:order-last');
    expect(screen.getAllByRole('img', { name: 'Picture' })).toHaveLength(2);
    expect(screen.getByRole('link', { name: 'Request a quote' })).toBeTruthy();
});

test('the customer area demo has Dashboard in the bar, the account navigation and the orders as a list', () => {
    const { container } = render(
        <CustomerAreaDemo links={publicLinks} {...customerArea.en} />,
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
    render(<HelpDemo links={publicLinks} {...help.zh} />);

    fireEvent.change(screen.getByRole('searchbox', { name: '搜索帮助' }), {
        target: { value: '发票' },
    });

    expect(
        screen
            .getAllByRole('button')
            .filter((button) => button.hasAttribute('aria-expanded')),
    ).toHaveLength(1);
    expect(
        screen.getByRole('link', { name: '联系我们', description: '' }),
    ).toBeTruthy();
});
