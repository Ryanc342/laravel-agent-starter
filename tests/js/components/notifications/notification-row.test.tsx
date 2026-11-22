import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import NotificationRow from '@/components/notifications/notification-row';

const visit = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/react', () => ({ router: { visit } }));

function notification(
    overrides: Partial<App.Http.Resources.NotificationResource> = {},
): App.Http.Resources.NotificationResource {
    return {
        id: 'abc',
        message: 'Your password was changed.',
        created_at_diff: '5 minutes ago',
        is_unread: true,
        can: { mark_read: true },
        ...overrides,
    };
}

type VisitOptions = {
    preserveScroll?: boolean;
    onSuccess?: () => void;
};

afterEach(() => {
    visit.mockReset();
});

test('an unread notification is one button and has no Mark as read button inside', () => {
    render(
        <ul>
            <NotificationRow notification={notification()} />
        </ul>,
    );

    const buttons = screen.getAllByRole('button');

    expect(buttons).toHaveLength(1);
    expect(buttons[0].textContent).toContain('Your password was changed.');
    expect(screen.queryByText('Mark as read')).toBeNull();
});

test('clicking an unread notification marks it as read and keeps the scroll position', () => {
    render(
        <ul>
            <NotificationRow notification={notification()} />
        </ul>,
    );

    fireEvent.click(screen.getByRole('button'));

    expect(visit).toHaveBeenCalledTimes(1);
    expect(visit.mock.calls[0][0]).toMatchObject({
        url: '/notifications/abc/read',
        method: 'post',
    });
    expect((visit.mock.calls[0][1] as VisitOptions).preserveScroll).toBe(true);
});

test('the bell reloads its list once the notification is marked as read', () => {
    const onMarkedRead = vi.fn();

    render(
        <ul>
            <NotificationRow
                notification={notification()}
                variant="menu"
                onMarkedRead={onMarkedRead}
            />
        </ul>,
    );
    fireEvent.click(screen.getByRole('button'));
    (visit.mock.calls[0][1] as VisitOptions).onSuccess?.();

    expect(onMarkedRead).toHaveBeenCalledTimes(1);
});

test('a read notification is not clickable', () => {
    render(
        <ul>
            <NotificationRow
                notification={notification({
                    is_unread: false,
                    can: { mark_read: false },
                })}
            />
        </ul>,
    );

    expect(screen.queryByRole('button')).toBeNull();
    expect(screen.getByText('Your password was changed.')).toBeTruthy();
});
