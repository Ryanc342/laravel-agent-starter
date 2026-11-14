import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { expect, test, vi } from 'vite-plus/test';
import AccountNav from '@/components/shared/account-nav';

const state = vi.hoisted(() => ({ url: '/account/orders' }));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ url: state.url }),
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

const items = [
    { title: 'Overview', href: '/account' },
    { title: 'Orders', href: '/account/orders' },
    { title: 'Profile', href: '/account/profile' },
];

test('one row of links, the page the user is on marked as current in the selected colour', () => {
    render(<AccountNav items={items} />);

    const nav = screen.getByRole('navigation', { name: 'Account' });
    const links = screen.getAllByRole('link');

    expect(nav).toBeTruthy();
    expect(links.map((link) => link.textContent)).toEqual([
        'Overview',
        'Orders',
        'Profile',
    ]);

    const current = screen.getByRole('link', { name: 'Orders' });

    expect(current.getAttribute('aria-current')).toBe('page');
    expect(
        screen
            .getByRole('link', { name: 'Overview' })
            .getAttribute('aria-current'),
    ).toBeNull();
});

test('a link can say it is current itself, as a demo does', () => {
    render(
        <AccountNav
            items={[
                { title: 'Overview', href: '#overview', isActive: false },
                { title: 'Profile', href: '#profile', isActive: true },
            ]}
        />,
    );

    expect(
        screen
            .getByRole('link', { name: 'Profile' })
            .getAttribute('aria-current'),
    ).toBe('page');
    expect(
        screen
            .getByRole('link', { name: 'Overview' })
            .getAttribute('aria-current'),
    ).toBeNull();
});
