import { usePage } from '@inertiajs/react';
import { fireEvent, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
import Pagination from '@/components/shared/pagination';
import type { ResourceCollection } from '@/types/resource';

const get = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/react', () => ({
    router: { get },
    usePage: vi.fn(),
    Link: ({ href, children }: { href: string; children: ReactNode }) => (
        <a href={href}>{children}</a>
    ),
}));

// Radix's Select needs a real pointer; a native select stands in for its value and change.
vi.mock('@/components/ui/select', () => ({
    Select: ({
        value,
        onValueChange,
        children,
    }: {
        value: string;
        onValueChange: (value: string) => void;
        children: ReactNode;
    }) => (
        <select
            aria-label="Rows per page"
            value={value}
            onChange={(event) => onValueChange(event.target.value)}
        >
            {children}
        </select>
    ),
    SelectTrigger: ({ children }: { children: ReactNode }) => <>{children}</>,
    SelectValue: () => null,
    SelectContent: ({ children }: { children: ReactNode }) => <>{children}</>,
    SelectItem: ({
        value,
        children,
    }: {
        value: string;
        children: ReactNode;
    }) => <option value={value}>{children}</option>,
}));

function collection(
    total: number,
    perPage: number,
    page = 1,
): ResourceCollection<unknown> {
    const last = Math.max(1, Math.ceil(total / perPage));

    return {
        data: [],
        links: {
            first: null,
            last: null,
            prev: page > 1 ? `/tasks?page=${page - 1}` : null,
            next: page < last ? `/tasks?page=${page + 1}` : null,
        },
        meta: {
            current_page: page,
            from: null,
            last_page: last,
            per_page: perPage,
            to: null,
            total,
        },
    };
}

beforeEach(() => {
    vi.mocked(usePage).mockReturnValue({
        url: '/tasks?status=open&page=2',
        props: { perPageOptions: [10, 20, 50, 100] },
    } as unknown as ReturnType<typeof usePage>);
});

afterEach(() => {
    get.mockReset();
    vi.mocked(usePage).mockReset();
});

test('the page links and the rows-per-page choice sit in one row', () => {
    render(<Pagination collection={collection(45, 20, 2)} />);
    const nav = screen.getByRole('navigation', { name: 'Pagination' });

    expect(nav.textContent).toContain('Page 2 of 3');
    expect(screen.getByRole('link', { name: 'Previous' })).toBeTruthy();
    expect(screen.getByRole('link', { name: 'Next' })).toBeTruthy();
    expect(screen.getByLabelText('Rows per page').parentElement).toBeTruthy();
});

test('the choice offers the four sizes the backend sends and shows the current one', () => {
    render(<Pagination collection={collection(45, 50)} />);
    const choice = screen.getByLabelText('Rows per page') as HTMLSelectElement;

    expect(Array.from(choice.options).map((option) => option.value)).toEqual([
        '10',
        '20',
        '50',
        '100',
    ]);
    expect(choice.value).toBe('50');
});

test('picking a size reloads the list on its first page and keeps the filters', () => {
    render(<Pagination collection={collection(45, 20, 2)} />);

    fireEvent.change(screen.getByLabelText('Rows per page'), {
        target: { value: '100' },
    });

    expect(get).toHaveBeenCalledTimes(1);
    expect(get.mock.calls[0][0]).toBe('/tasks?status=open&per_page=100');
});

test('a list on one page still offers the choice when it has more rows than the smallest size', () => {
    render(<Pagination collection={collection(15, 20)} />);

    expect(screen.getByLabelText('Rows per page')).toBeTruthy();
    expect(screen.queryByRole('link', { name: 'Next' })).toBeNull();
    expect(screen.queryByText(/Page 1 of/)).toBeNull();
});

test('a list that fits the smallest size shows nothing', () => {
    const { container } = render(<Pagination collection={collection(8, 20)} />);

    expect(container.firstChild).toBeNull();
});

test('a collection that is not paginated shows nothing', () => {
    const { container } = render(<Pagination collection={{ data: [] }} />);

    expect(container.firstChild).toBeNull();
});
