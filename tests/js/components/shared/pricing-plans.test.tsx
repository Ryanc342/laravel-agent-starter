import {
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import type { ReactNode } from 'react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import PricingPlans, { type Plan } from '@/components/shared/pricing-plans';

const state = vi.hoisted(() => ({ url: '/pricing', get: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    router: { get: state.get },
    usePage: () => ({ url: state.url }),
    Link: ({ href, children }: { href: unknown; children: ReactNode }) => (
        <a
            href={
                typeof href === 'string' ? href : (href as { url: string }).url
            }
        >
            {children}
        </a>
    ),
}));

const nbsp = '\u00a0';
// Testing Library normalises a no-break space in the text it reads to a plain one.
const shown = (text: string) => text.replace(/\u00a0/g, ' ');

const plans: Plan[] = [
    {
        id: 'starter',
        name: 'Starter',
        recommended: false,
        currency: 'MYR',
        price: { monthly: 0, yearly: 0 },
        features: ['One project', 'Email support'],
        action: { label: 'Choose Starter', href: '/register?plan=starter' },
    },
    {
        id: 'team',
        name: 'Team',
        recommended: true,
        currency: 'MYR',
        price: { monthly: 4900, yearly: 49000 },
        features: ['Ten projects', 'Priority support', 'Audit log'],
        action: { label: 'Choose Team', href: '/register?plan=team' },
    },
];

beforeEach(() => {
    state.url = '/pricing';
    state.get.mockReset();
});

function plan(name: string): HTMLElement {
    return screen.getByRole('heading', { name }).closest('[data-slot="card"]')!;
}

test('the page title is the level-one heading', () => {
    render(<PricingPlans title="Plans and prices" plans={plans} />);

    expect(
        screen.getByRole('heading', { level: 1, name: 'Plans and prices' }),
    ).toBeTruthy();
});

test('with no billing in the URL the monthly prices show, with their period', () => {
    render(<PricingPlans title="Plans and prices" plans={plans} />);

    expect(
        within(plan('Team')).getByText(shown(`RM${nbsp}49.00`)),
    ).toBeTruthy();
    expect(within(plan('Team')).getByText('/ month')).toBeTruthy();
    expect(
        screen
            .getByRole('radio', { name: 'Monthly' })
            .getAttribute('aria-checked'),
    ).toBe('true');
});

test('billing=yearly in the URL shows the yearly prices', () => {
    state.url = '/pricing?billing=yearly';
    render(<PricingPlans title="Plans and prices" plans={plans} />);

    expect(
        within(plan('Team')).getByText(shown(`RM${nbsp}490.00`)),
    ).toBeTruthy();
    expect(within(plan('Team')).getByText('/ year')).toBeTruthy();
    expect(
        screen
            .getByRole('radio', { name: 'Yearly' })
            .getAttribute('aria-checked'),
    ).toBe('true');
});

test('an unknown billing value counts as monthly', () => {
    state.url = '/pricing?billing=weekly';
    render(<PricingPlans title="Plans and prices" plans={plans} />);

    expect(within(plan('Team')).getByText('/ month')).toBeTruthy();
});

test('choosing Yearly puts it in the URL, and choosing Monthly takes it out again', async () => {
    render(<PricingPlans title="Plans and prices" plans={plans} />);
    fireEvent.click(screen.getByRole('radio', { name: 'Yearly' }));

    await waitFor(() => expect(state.get).toHaveBeenCalledTimes(1));
    expect(state.get.mock.calls[0][0]).toBe('/pricing?billing=yearly');

    state.url = '/pricing?billing=yearly';
    state.get.mockReset();
    const { unmount } = render(
        <PricingPlans title="Plans and prices" plans={plans} />,
    );
    fireEvent.click(screen.getAllByRole('radio', { name: 'Monthly' })[1]);
    unmount();

    await waitFor(() => expect(state.get).toHaveBeenCalledTimes(1));
    expect(state.get.mock.calls[0][0]).toBe('/pricing');
});

test('only the recommended plan has the primary border, the badge and the main button', () => {
    render(<PricingPlans title="Plans and prices" plans={plans} />);

    expect(within(plan('Team')).getByText('Recommended')).toBeTruthy();
    expect(within(plan('Starter')).queryByText('Recommended')).toBeNull();
    expect(screen.getAllByText('Recommended')).toHaveLength(1);
});

test('each plan lists its features and has one button to its own address', () => {
    render(<PricingPlans title="Plans and prices" plans={plans} />);

    expect(within(plan('Team')).getAllByRole('listitem')).toHaveLength(3);
    expect(within(plan('Starter')).getAllByRole('listitem')).toHaveLength(2);
    expect(
        screen.getByRole('link', { name: 'Choose Team' }).getAttribute('href'),
    ).toBe('/register?plan=team');
    expect(within(plan('Team')).getAllByRole('link')).toHaveLength(1);
});
