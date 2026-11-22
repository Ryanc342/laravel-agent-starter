import { fireEvent, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { expect, test, vi } from 'vite-plus/test';
import ErrorContent from '@/components/shared/error-content';

const router = vi.hoisted(() => ({ reload: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    router,
    Link: ({
        href,
        children,
    }: {
        href: { url: string };
        children: ReactNode;
    }) => <a href={href.url}>{children}</a>,
}));

test('a 404 leads back to the dashboard, or to the login page for someone signed out', () => {
    const { unmount } = render(<ErrorContent status={404} signedIn />);

    expect(
        screen
            .getByRole('link', { name: 'Go to dashboard' })
            .getAttribute('href'),
    ).toBe('/dashboard');
    unmount();

    render(<ErrorContent status={404} signedIn={false} />);
    expect(
        screen.getByRole('link', { name: 'Log in' }).getAttribute('href'),
    ).toBe('/login');
});

test('a 500 offers to try again, which asks for the page again', () => {
    render(<ErrorContent status={500} signedIn />);

    fireEvent.click(screen.getByRole('button', { name: 'Try again' }));

    expect(router.reload).toHaveBeenCalledTimes(1);
    expect(screen.queryByRole('link')).toBeNull();
});
