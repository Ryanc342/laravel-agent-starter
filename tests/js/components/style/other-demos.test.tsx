import { fireEvent, render, screen, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import AuthDemo from '@/components/style/auth-demo';
import CalendarDemo from '@/components/style/calendar-demo';
import FeedDemo from '@/components/style/feed-demo';
import KanbanDemo from '@/components/style/kanban-demo';
import UiActionsDemo from '@/components/style/ui-actions-demo';
import { TooltipProvider } from '@/components/ui/tooltip';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('sonner', () => ({ toast: { success: vi.fn(), error: vi.fn() } }));
vi.mock('@inertiajs/react', () => ({
    router: { get: vi.fn() },
    usePage: () => ({
        url: '/style',
        props: { today: { date: '2026-11-14', year: 2026 } },
    }),
    useFormContext: () => undefined,
    Link: ({ href, children }: { href: string; children: ReactNode }) => (
        <a href={href}>{children}</a>
    ),
}));

afterEach(() => {
    vi.mocked(toast.success).mockClear();
    vi.mocked(toast.error).mockClear();
});

function withTooltips(node: ReactNode) {
    return render(<TooltipProvider>{node}</TooltipProvider>);
}

test('the sign-in demo shows the status line, the fields with their labels and the reset link under the button', () => {
    withTooltips(
        <AuthDemo
            title="Log in"
            status="Your password has been reset."
            submitLabel="Log in"
            footer="Don't have an account?"
            footerLink="Sign up"
        />,
    );

    expect(screen.getByRole('status').textContent).toBe(
        'Your password has been reset.',
    );
    expect(screen.getByLabelText('Email address')).toBeTruthy();
    const button = screen.getByRole('button', { name: 'Log in' });
    const reset = screen.getByRole('link', { name: 'Forgot your password?' });
    expect(
        button.compareDocumentPosition(reset) &
            Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
});

test('an event of the calendar opens in a dialog with its date', () => {
    withTooltips(
        <CalendarDemo
            events={[{ id: 1, date: '2026-11-05', title: 'Supplier call' }]}
            today="2026-11-14"
            month="2026-11"
        />,
    );

    fireEvent.click(screen.getByRole('button', { name: 'Supplier call' }));

    const dialog = screen.getByRole('dialog', { name: 'Supplier call' });
    expect(within(dialog).getByText('5 Nov 2026')).toBeTruthy();
});

test('the feed demo shows its rows, the unread one as a button, and the empty box', () => {
    render(
        <FeedDemo
            notifications={[
                {
                    id: '1',
                    message: 'Password changed.',
                    time: 'now',
                    unread: true,
                },
                {
                    id: '2',
                    message: 'Passkey added.',
                    time: 'later',
                    unread: false,
                },
            ]}
        />,
    );

    expect(
        screen.getByRole('button', { name: /Password changed\./ }),
    ).toBeTruthy();
    expect(
        screen.queryByRole('button', { name: /Passkey added\./ }),
    ).toBeNull();
    expect(screen.getByText('You have no notifications.')).toBeTruthy();
});

test('a card moved on the kanban demo lands in its new column', async () => {
    withTooltips(
        <KanbanDemo
            columns={[
                {
                    id: 'new',
                    label: 'New',
                    cards: [{ id: 'a', title: 'Order 1', owner: 'Mei' }],
                },
                { id: 'done', label: 'Done', cards: [] },
            ]}
        />,
    );

    fireEvent.pointerDown(
        screen.getByRole('button', { name: 'Move Order 1 to' }),
        {
            button: 0,
            ctrlKey: false,
        },
    );
    fireEvent.click(await screen.findByRole('menuitem', { name: 'Done' }));

    expect(
        within(screen.getByRole('region', { name: 'Done' })).getByText(
            'Order 1',
        ),
    ).toBeTruthy();
    expect(toast.success).toHaveBeenCalledWith('Order moved.', {
        id: 'kanban-move',
    });
});

test('the toast buttons of the actions demo show a success and the network error', () => {
    withTooltips(<UiActionsDemo />);

    fireEvent.click(screen.getByRole('button', { name: 'Success toast' }));
    fireEvent.click(screen.getByRole('button', { name: 'Error toast' }));

    expect(toast.success).toHaveBeenCalledTimes(1);
    expect(toast.error).toHaveBeenCalledTimes(1);
});
