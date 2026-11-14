import { render, screen } from '@testing-library/react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import { Breadcrumbs } from '@/components/breadcrumbs';

let currentPath = '/tasks/2';

vi.mock('@/hooks/use-current-url', () => ({
    useCurrentUrl: () => ({
        isCurrentUrl: (href: string) => href === currentPath,
    }),
}));

beforeEach(() => {
    currentPath = '/tasks/2';
});

test('a record page ends its breadcrumb at the list, and that last crumb is the way back', () => {
    currentPath = '/tasks/2';

    render(<Breadcrumbs breadcrumbs={[{ title: 'Tasks', href: '/tasks' }]} />);

    expect(
        screen.getByRole('link', { name: 'Tasks' }).getAttribute('href'),
    ).toBe('/tasks');
});

test('the last crumb is plain text when it is the page the user is on', () => {
    currentPath = '/notifications';

    const { container } = render(
        <Breadcrumbs
            breadcrumbs={[{ title: 'Notifications', href: '/notifications' }]}
        />,
    );

    expect(container.querySelector('a')).toBeNull();
    expect(screen.getByText('Notifications').getAttribute('aria-current')).toBe(
        'page',
    );
});
