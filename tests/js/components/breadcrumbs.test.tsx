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

const longTitle =
    'Reply to the supplier about the delayed shipment of spare parts and the revised invoice';

function renderBreadcrumbs() {
    return render(
        <Breadcrumbs
            breadcrumbs={[
                { title: 'Tasks', href: '/tasks' },
                { title: longTitle, href: '/tasks/2' },
            ]}
        />,
    );
}

test('a long page title is cut short instead of wrapping onto more lines', () => {
    const { container } = renderBreadcrumbs();

    expect(screen.getByText(longTitle).className).toContain('truncate');
    expect(
        container.querySelector('[data-slot="breadcrumb-list"]')?.className,
    ).toContain('flex-nowrap');
});

test('the way back stays whole: only the current page shrinks', () => {
    renderBreadcrumbs();
    const back = screen.getByText('Tasks').closest('li') as HTMLElement;
    const current = screen.getByText(longTitle).closest('li') as HTMLElement;

    expect(back.className).toContain('shrink-0');
    expect(current.className).toContain('min-w-0');
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
