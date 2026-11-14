import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import EmptyState from '@/components/shared/empty-state';
import ErrorState from '@/components/shared/error-state';
import LoadingState from '@/components/shared/loading-state';
import { useIsMobile } from '@/hooks/use-mobile';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: vi.fn() }));

afterEach(() => {
    vi.mocked(useIsMobile).mockReset();
});

test('an empty list shows one short line', () => {
    render(<EmptyState message="You have no tasks yet." />);

    expect(screen.getByText('You have no tasks yet.')).toBeTruthy();
    expect(screen.queryByRole('button')).toBeNull();
});

test('an empty state is centred with a faint icon above its line', () => {
    const { container } = render(
        <EmptyState message="You have no tasks yet." />,
    );
    const box = container.firstElementChild as HTMLElement;
    const icon = box.querySelector('svg');

    expect(icon?.getAttribute('aria-hidden')).toBe('true');
});

test('an empty list shows the create button when the page passes it', () => {
    render(
        <EmptyState
            message="You have no tasks yet."
            action={<button type="button">Add task</button>}
        />,
    );

    expect(screen.getByRole('button', { name: 'Add task' })).toBeTruthy();
});

test('a region that failed to load says what happened', () => {
    render(<ErrorState message="Could not load your tasks." />);

    expect(screen.getByRole('alert').textContent).toContain(
        'Could not load your tasks.',
    );
    expect(screen.queryByRole('button')).toBeNull();
});

test('a failed region offers a retry only when trying again can help', () => {
    const retry = vi.fn();

    render(<ErrorState message="Could not load your tasks." onRetry={retry} />);
    fireEvent.click(screen.getByRole('button', { name: 'Try again' }));

    expect(retry).toHaveBeenCalledTimes(1);
});

test('a loading list shows rows on desktop', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    const { container } = render(<LoadingState rows={4} />);

    expect(screen.getByRole('status').textContent).toContain('Loading');
    expect(
        container.querySelectorAll('[data-slot="loading-row"]'),
    ).toHaveLength(4);
    expect(
        container.querySelectorAll('[data-slot="loading-card"]'),
    ).toHaveLength(0);
});

test('a loading list shows cards on a phone, like the list it stands for', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);

    const { container } = render(<LoadingState rows={2} />);

    expect(
        container.querySelectorAll('[data-slot="loading-card"]'),
    ).toHaveLength(2);
    expect(
        container.querySelectorAll('[data-slot="loading-row"]'),
    ).toHaveLength(0);
});
