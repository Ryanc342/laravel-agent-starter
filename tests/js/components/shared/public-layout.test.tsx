import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { expect, test, vi } from 'vite-plus/test';
import PublicLayout from '@/layouts/public-layout';

const page = vi.hoisted(() => ({
    props: {
        name: 'Acme',
        auth: { user: null } as { user: unknown },
        locale: 'en',
        locales: [
            { value: 'en', label: 'English', short: 'EN' },
            { value: 'zh_CN', label: '中文', short: '中文' },
        ],
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
    page.props.auth = { user: null };
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
    page.props.auth = { user: { id: 1 } };
    render(
        <PublicLayout>
            <p>The page</p>
        </PublicLayout>,
    );

    expect(screen.getByRole('link', { name: 'Dashboard' })).toBeTruthy();
    expect(screen.queryByRole('link', { name: 'Log in' })).toBeNull();
});
