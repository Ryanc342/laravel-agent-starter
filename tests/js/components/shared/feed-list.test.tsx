import { render, screen, within } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import FeedList from '@/components/shared/feed-list';
import FeedRow from '@/components/shared/feed-row';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));

function rows() {
    return [
        <FeedRow key="1" message="Unread one" time="1 minute ago" unread />,
        <FeedRow key="2" message="Read one" time="1 day ago" unread={false} />,
    ];
}

test('the rows are one bordered box, separated by lines', () => {
    render(
        <FeedList emptyMessage="You have no notifications.">{rows()}</FeedList>,
    );
    const list = screen.getByRole('list');

    expect(within(list).getAllByRole('listitem')).toHaveLength(2);
    expect(screen.queryByText('You have no notifications.')).toBeNull();
});

test('with no rows the box stays and the empty line is inside it', () => {
    const { container } = render(
        <FeedList emptyMessage="You have no notifications.">{[]}</FeedList>,
    );
    const box = container.firstElementChild as HTMLElement;

    expect(within(box).getByText('You have no notifications.')).toBeTruthy();
    expect(screen.queryAllByRole('listitem')).toHaveLength(0);
});

test('while loading the box stays and the skeleton is inside it', () => {
    const { container } = render(
        <FeedList emptyMessage="You have no notifications." loading>
            {[]}
        </FeedList>,
    );
    const box = container.firstElementChild as HTMLElement;

    expect(within(box).getByRole('status')).toBeTruthy();
    expect(screen.queryByText('You have no notifications.')).toBeNull();
});

test('when it failed to load the box stays and the failure is inside it', () => {
    const { container } = render(
        <FeedList
            emptyMessage="You have no notifications."
            error={{ message: 'Could not load your notifications.' }}
        >
            {[]}
        </FeedList>,
    );
    const box = container.firstElementChild as HTMLElement;

    expect(within(box).getByRole('alert').textContent).toContain(
        'Could not load your notifications.',
    );
    expect(screen.queryByText('You have no notifications.')).toBeNull();
});
