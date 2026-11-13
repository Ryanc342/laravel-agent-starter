import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { expect, test, vi } from 'vite-plus/test';
import Welcome from '@/pages/welcome/index';

const page = vi.hoisted(() => ({
    props: { name: 'Acme', auth: { user: null } as { user: unknown } },
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
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

test('signed out, the hero offers Get started and Log in, and the page has no invented proof', () => {
    page.props.auth = { user: null };
    const { container } = render(<Welcome />);

    expect(screen.getByRole('heading', { level: 1 })).toBeTruthy();
    expect(
        screen.getAllByRole('link', { name: 'Get started' }).length,
    ).toBeGreaterThan(0);
    expect(screen.getByRole('link', { name: 'Log in' })).toBeTruthy();
    expect(screen.queryByRole('link', { name: /dashboard/i })).toBeNull();
    expect(container.querySelector('img, blockquote')).toBeNull();
});

test('signed in, the hero and the closing line go to the dashboard', () => {
    page.props.auth = { user: { id: 1 } };
    render(<Welcome />);

    expect(
        screen.getAllByRole('link', { name: 'Open dashboard' }),
    ).toHaveLength(2);
    expect(screen.queryByRole('link', { name: 'Get started' })).toBeNull();
    expect(screen.queryByRole('link', { name: 'Log in' })).toBeNull();
});

test('three to six features are listed', () => {
    page.props.auth = { user: null };
    render(<Welcome />);

    const count = screen.getAllByRole('listitem').length;

    expect(count).toBeGreaterThanOrEqual(3);
    expect(count).toBeLessThanOrEqual(6);
});
