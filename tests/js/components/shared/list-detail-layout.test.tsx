import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import ListDetailLayout, {
    ListDetailRow,
} from '@/components/shared/list-detail-layout';
import { useIsMobile } from '@/hooks/use-mobile';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: vi.fn() }));
vi.mock('@inertiajs/react', () => ({
    Link: ({
        href,
        children,
        ...props
    }: {
        href: string;
        children: ReactNode;
    }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

afterEach(() => {
    vi.mocked(useIsMobile).mockReset();
});

function layout(selected: boolean) {
    return render(
        <ListDetailLayout
            list={<p>The list</p>}
            detail={<p>The detail</p>}
            selected={selected}
            backHref="/tickets"
            backLabel="Tickets"
        />,
    );
}

test('on a computer the list is a third of the width and the detail the rest, side by side', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    layout(true);

    expect(screen.getByText('The list')).toBeTruthy();
    expect(screen.getByText('The detail')).toBeTruthy();
});

test('on a phone the list comes first and fills the screen', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    layout(false);

    expect(screen.getByText('The list')).toBeTruthy();
    expect(screen.queryByText('The detail')).toBeNull();
});

test('on a phone a selected record is its own screen with a back link at the top left', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    layout(true);

    const back = screen.getByRole('link', { name: 'Tickets' });

    expect(screen.queryByText('The list')).toBeNull();
    expect(screen.getByText('The detail')).toBeTruthy();
    expect(back.getAttribute('href')).toBe('/tickets');
    expect(
        back.compareDocumentPosition(screen.getByText('The detail')) &
            Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
});

test('the selected row is a link in the selected colour; the others have hover and press', () => {
    render(
        <>
            <ListDetailRow href="/tickets/1" selected>
                One
            </ListDetailRow>
            <ListDetailRow href="/tickets/2" selected={false}>
                Two
            </ListDetailRow>
        </>,
    );

    const [one, two] = screen.getAllByRole('link');

    expect(one.getAttribute('aria-current')).toBe('true');
    expect(two.getAttribute('aria-current')).toBeNull();
});
