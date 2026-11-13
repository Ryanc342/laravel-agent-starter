import { fireEvent, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import SearchBox from '@/components/shared/search-box';
import SearchResults, {
    Highlight,
    type SearchGroup,
} from '@/components/shared/search-results';

const get = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/react', () => ({
    router: { get },
    Link: ({ href, children }: { href: string; children: ReactNode }) => (
        <a href={href}>{children}</a>
    ),
}));

beforeEach(() => {
    get.mockReset();
});

test('Enter opens the results page with the text in the URL', () => {
    render(<SearchBox action="/search" />);

    const box = screen.getByRole('searchbox', { name: 'Search' });
    fireEvent.change(box, { target: { value: ' invoice ' } });
    fireEvent.submit(box.closest('form')!);

    expect(get).toHaveBeenCalledWith('/search', { q: 'invoice' });
});

test('an empty box does nothing, and the results page puts its text back', () => {
    const { unmount } = render(<SearchBox action="/search" />);

    fireEvent.submit(screen.getByRole('search'));
    expect(get).not.toHaveBeenCalled();
    unmount();

    render(<SearchBox action="/search" defaultValue="invoice" />);
    expect((screen.getByRole('searchbox') as HTMLInputElement).value).toBe(
        'invoice',
    );
});

test('the matched text is marked, whatever its case, and the rest is left alone', () => {
    const { container } = render(
        <p>
            <Highlight text="Send Invoice to invoice team" query="invoice" />
        </p>,
    );

    const marks = container.querySelectorAll('mark');

    expect(Array.from(marks).map((mark) => mark.textContent)).toEqual([
        'Invoice',
        'invoice',
    ]);
    expect(container.textContent).toBe('Send Invoice to invoice team');
});

test('a query with characters of a pattern is matched as plain text', () => {
    const { container } = render(
        <p>
            <Highlight text="Cost (RM) is 5+5" query="(RM)" />
        </p>,
    );

    expect(container.querySelector('mark')?.textContent).toBe('(RM)');
});

const groups: SearchGroup[] = [
    {
        type: 'tasks',
        label: 'Tasks',
        count: 5,
        viewAllHref: '/tasks?search=invoice',
        results: [
            {
                id: 1,
                title: 'Send invoice',
                href: '/tasks/1',
                status: { label: 'Open', variant: 'secondary' },
            },
            { id: 2, title: 'Chase invoice', href: '/tasks/2' },
        ],
    },
    { type: 'customers', label: 'Customers', count: 0, results: [] },
];

test('results are grouped by type with a count, as links with their status', () => {
    render(<SearchResults query="invoice" groups={groups} />);

    expect(screen.getByRole('heading', { name: 'Tasks (5)' })).toBeTruthy();
    expect(screen.queryByText(/Customers/)).toBeNull();
    expect(
        screen.getByRole('link', { name: 'Send invoice' }).getAttribute('href'),
    ).toBe('/tasks/1');
    expect(screen.getByText('Open')).toBeTruthy();
});

test('"View all" shows only when more records match than are shown', () => {
    const { unmount } = render(
        <SearchResults query="invoice" groups={groups} />,
    );

    expect(
        screen.getByRole('link', { name: 'View all 5' }).getAttribute('href'),
    ).toBe('/tasks?search=invoice');
    unmount();

    render(
        <SearchResults query="invoice" groups={[{ ...groups[0], count: 2 }]} />,
    );
    expect(screen.queryByRole('link', { name: /View all/ })).toBeNull();
});

test('with nothing found one line says so', () => {
    render(<SearchResults query="zzz" groups={[groups[1]]} />);

    expect(screen.getByText('No results for "zzz"')).toBeTruthy();
});
