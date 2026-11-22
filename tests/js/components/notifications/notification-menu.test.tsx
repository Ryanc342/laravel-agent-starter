import { fireEvent, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import NotificationMenu from '@/components/notifications/notification-menu';
import { TooltipProvider } from '@/components/ui/tooltip';

const state = vi.hoisted(() => ({
    reload: vi.fn(),
    props: {} as Record<string, unknown>,
}));

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('@inertiajs/react', () => ({
    router: { reload: state.reload, post: vi.fn() },
    usePage: () => ({ props: state.props }),
    useFormContext: () => ({ processing: false }),
    Form: ({ children }: { children: ReactNode }) => <form>{children}</form>,
    Link: ({
        href,
        children,
    }: {
        href: { url: string };
        children: ReactNode;
    }) => <a href={href.url}>{children}</a>,
}));

beforeEach(() => {
    state.reload.mockReset();
    state.props = {
        notificationSummary: { unread: 3, can: { mark_all_read: true } },
    };
});

function menu() {
    render(
        <TooltipProvider>
            <NotificationMenu />
        </TooltipProvider>,
    );
}

function open() {
    fireEvent.click(
        screen.getByRole('button', { name: 'Notifications, 3 unread' }),
    );
}

test('the bell says how many are unread, and opening it asks for the newest', () => {
    menu();
    open();

    expect(state.reload).toHaveBeenCalledWith(
        expect.objectContaining({ only: ['latestNotifications'] }),
    );
    expect(screen.getByText('3')).toBeTruthy();
});

test('while they load, the card shows the loading state of a feed', () => {
    menu();
    open();

    expect(screen.getByRole('status').getAttribute('aria-busy')).toBe('true');
});

test('with none, the card says so', () => {
    state.props = {
        ...state.props,
        latestNotifications: [],
    };
    menu();
    open();

    expect(screen.getByText('You have no notifications.')).toBeTruthy();
});

test('when they could not load, the card says so and offers to try again', () => {
    state.reload.mockImplementation((options: { onNetworkError: () => void }) =>
        options.onNetworkError(),
    );
    menu();
    open();

    expect(screen.getByText('Could not load your notifications.')).toBeTruthy();
    expect(screen.getByRole('button', { name: 'Try again' })).toBeTruthy();
});

test('a signed-out page has no bell', () => {
    state.props = { notificationSummary: null };
    const { container } = render(<NotificationMenu />);

    expect(container.innerHTML).toBe('');
});
