import { usePage } from '@inertiajs/react';
import { act, fireEvent, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
import LatestNotifications from '@/components/notifications/latest-notifications';

const reload = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/react', () => ({
    router: { reload },
    usePage: vi.fn(),
    Link: ({
        href,
        children,
    }: {
        href: string | { url: string };
        children: ReactNode;
    }) => <a href={typeof href === 'string' ? href : href.url}>{children}</a>,
}));
vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('@/components/notifications/notification-row', () => ({
    default: ({
        notification,
    }: {
        notification: App.Http.Resources.NotificationResource;
    }) => <li>{notification.message}</li>,
}));

function notification(
    id: string,
    message: string,
): App.Http.Resources.NotificationResource {
    return {
        id,
        message,
        created_at_diff: '5 minutes ago',
        is_unread: true,
        can: { mark_read: true },
    };
}

function propsAre(
    latestNotifications: App.Http.Resources.NotificationResource[] | undefined,
) {
    vi.mocked(usePage).mockReturnValue({
        props: { latestNotifications },
    } as unknown as ReturnType<typeof usePage>);
}

type ReloadOptions = {
    only?: string[];
    onHttpException?: () => boolean | void;
    onNetworkError?: () => void;
};

function reloadOptions(call: number): ReloadOptions {
    return reload.mock.calls[call][0] as ReloadOptions;
}

beforeEach(() => {
    propsAre(undefined);
});

afterEach(() => {
    reload.mockReset();
    vi.mocked(usePage).mockReset();
});

test('the block asks the backend for the latest notifications when it opens', () => {
    render(<LatestNotifications />);

    expect(reload).toHaveBeenCalledTimes(1);
    expect(reloadOptions(0).only).toEqual(['latestNotifications']);
});

test('it shows a loading state until the notifications arrive', () => {
    render(<LatestNotifications />);

    expect(screen.getByRole('status').getAttribute('aria-busy')).toBe('true');
    expect(screen.queryByRole('list')).toBeNull();
});

test('it shows a row for each notification and a link to all of them', () => {
    propsAre([
        notification('1', 'Your password was changed.'),
        notification('2', 'A new passkey was added.'),
    ]);

    render(<LatestNotifications />);

    expect(
        screen.getAllByRole('listitem').map((row) => row.textContent),
    ).toEqual(['Your password was changed.', 'A new passkey was added.']);
    expect(
        screen.getByRole('link', { name: 'View all' }).getAttribute('href'),
    ).toBe('/notifications');
    expect(screen.queryByRole('status')).toBeNull();
});

test('with no notifications it shows one short line and no link', () => {
    propsAre([]);

    render(<LatestNotifications />);

    expect(screen.getByText('You have no notifications.')).toBeTruthy();
    expect(screen.queryByRole('link')).toBeNull();
    expect(screen.queryByRole('status')).toBeNull();
});

test('a server error ends the visit here and shows an error state in the block', () => {
    render(<LatestNotifications />);

    let handled: boolean | void = undefined;

    act(() => {
        handled = reloadOptions(0).onHttpException?.();
    });

    // `false` keeps Inertia from opening its own error modal over the page.
    expect(handled).toBe(false);
    expect(screen.getByRole('alert').textContent).toContain(
        'Could not load your notifications.',
    );
    expect(screen.queryByRole('status')).toBeNull();
});

test('a network error also shows the error state in the block', () => {
    render(<LatestNotifications />);

    act(() => {
        reloadOptions(0).onNetworkError?.();
    });

    expect(screen.getByRole('alert')).toBeTruthy();
});

test('"Try again" asks for the notifications again and hides the error', () => {
    render(<LatestNotifications />);

    act(() => {
        reloadOptions(0).onHttpException?.();
    });
    fireEvent.click(screen.getByRole('button', { name: 'Try again' }));

    expect(reload).toHaveBeenCalledTimes(2);
    expect(reloadOptions(1).only).toEqual(['latestNotifications']);
    expect(screen.queryByRole('alert')).toBeNull();
    expect(screen.getByRole('status')).toBeTruthy();
});
