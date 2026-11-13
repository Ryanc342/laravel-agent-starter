import { render, screen, within } from '@testing-library/react';
import { expect, test } from 'vite-plus/test';
import FeedList from '@/components/shared/feed-list';
import FeedRow from '@/components/shared/feed-row';

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

    expect(list.className).toContain('rounded-lg');
    expect(list.className).toContain('border');
    expect(list.className).toContain('divide-y');
    expect(within(list).getAllByRole('listitem')).toHaveLength(2);
    expect(screen.queryByText('You have no notifications.')).toBeNull();
});

test('a row is not a card of its own and has no grey background', () => {
    render(
        <FeedList emptyMessage="You have no notifications.">{rows()}</FeedList>,
    );

    for (const row of screen.getAllByRole('listitem')) {
        expect(row.className).not.toContain('border');
        expect(row.className).not.toContain('rounded');
        expect(row.className).not.toContain('bg-');
    }
});

test('an unread row has the blue dot and darker text, a read row neither', () => {
    render(
        <FeedList emptyMessage="You have no notifications.">{rows()}</FeedList>,
    );
    const [unread, read] = screen.getAllByRole('listitem');

    expect(unread.querySelector('.bg-primary')).not.toBeNull();
    expect(within(unread).getByText('Unread one').className).toContain(
        'text-foreground',
    );
    expect(read.querySelector('.bg-primary')).toBeNull();
    expect(within(read).getByText('Read one').className).toContain(
        'text-muted-foreground',
    );
});

test('with no rows the box stays and the empty line is inside it', () => {
    const { container } = render(
        <FeedList emptyMessage="You have no notifications.">{[]}</FeedList>,
    );
    const box = container.firstElementChild as HTMLElement;

    expect(box.className).toContain('rounded-lg');
    expect(box.className).toContain('border');
    expect(within(box).getByText('You have no notifications.')).toBeTruthy();
    expect(screen.queryAllByRole('listitem')).toHaveLength(0);
});
