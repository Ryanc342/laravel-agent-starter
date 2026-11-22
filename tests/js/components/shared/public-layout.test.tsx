import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { expect, test, vi } from 'vite-plus/test';
import PublicLayout from '@/layouts/public-layout';

const page = vi.hoisted(() => ({
    props: {
        name: 'Acme',
        signedIn: false,
        today: { date: '2026-11-14', year: 2026 },
    },
}));

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('@inertiajs/react', () => ({
    usePage: () => page,
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

test('signed out, the layout puts Log in and Get started in the bar and the page in main', () => {
    page.props.signedIn = false;
    render(
        <PublicLayout>
            <p>The page</p>
        </PublicLayout>,
    );

    expect(screen.getByRole('link', { name: 'Log in' })).toBeTruthy();
    expect(screen.getByRole('link', { name: 'Get started' })).toBeTruthy();
    expect(screen.getByRole('main').textContent).toBe('The page');
    expect(screen.getByRole('contentinfo')).toBeTruthy();
});

test('signed in, the layout shows Dashboard', () => {
    page.props.signedIn = true;
    render(
        <PublicLayout>
            <p>The page</p>
        </PublicLayout>,
    );

    expect(screen.getByRole('link', { name: 'Dashboard' })).toBeTruthy();
    expect(screen.queryByRole('link', { name: 'Log in' })).toBeNull();
});

test("the footer's year is the backend's, not the clock's", () => {
    render(
        <PublicLayout>
            <p>Page</p>
        </PublicLayout>,
    );

    expect(screen.getByText(/© 2026 Acme/)).toBeTruthy();
});
