import { fireEvent, render, screen, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import PublicFooter from '@/components/shared/public-footer';
import PublicHeader from '@/components/shared/public-header';
import { TooltipProvider } from '@/components/ui/tooltip';
import { useIsMobile } from '@/hooks/use-mobile';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: vi.fn() }));
vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { name: 'Acme' } }),
    Link: ({ href, children }: { href: unknown; children: ReactNode }) => (
        <a
            href={
                typeof href === 'string' ? href : (href as { url: string }).url
            }
        >
            {children}
        </a>
    ),
}));

afterEach(() => {
    vi.mocked(useIsMobile).mockReset();
});

const links = [
    { title: 'Product', href: '/product' },
    { title: 'Pricing', href: '/pricing' },
    { title: 'Help', href: '/help' },
    { title: 'Contact', href: '/contact' },
];

// The app mounts one TooltipProvider (app.tsx).
function header(signedIn: boolean, pageLinks = links) {
    return render(
        <TooltipProvider>
            <PublicHeader links={pageLinks} signedIn={signedIn} />
        </TooltipProvider>,
    );
}

test('signed out, the bar has the logo, the page links, Log in and Get started', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    header(false);

    expect(screen.getByRole('link', { name: /Acme/ })).toBeTruthy();

    for (const link of links) {
        expect(
            screen.getByRole('link', { name: link.title }).getAttribute('href'),
        ).toBe(link.href);
    }

    expect(screen.getByRole('link', { name: 'Log in' })).toBeTruthy();
    expect(screen.getByRole('link', { name: 'Get started' })).toBeTruthy();
    expect(screen.queryByRole('link', { name: 'Dashboard' })).toBeNull();
    expect(screen.queryByRole('button', { name: 'Open menu' })).toBeNull();
});

test('signed in, the bar shows Dashboard instead of Log in and Get started', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    header(true);

    expect(screen.getByRole('link', { name: 'Dashboard' })).toBeTruthy();
    expect(screen.queryByRole('link', { name: 'Log in' })).toBeNull();
    expect(screen.queryByRole('link', { name: 'Get started' })).toBeNull();
});

test('a bar with no page links shows only the logo and the buttons', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    header(false, []);

    expect(screen.queryByRole('navigation', { name: 'Pages' })).toBeNull();
    expect(screen.getByRole('link', { name: 'Get started' })).toBeTruthy();
});

test('on a phone the links and buttons wait in a sheet that a menu button opens', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    header(false);

    expect(screen.queryByRole('link', { name: 'Pricing' })).toBeNull();
    expect(screen.queryByRole('link', { name: 'Log in' })).toBeNull();

    fireEvent.click(screen.getByRole('button', { name: 'Open menu' }));

    const menu = screen.getByRole('dialog');

    for (const link of links) {
        expect(
            within(menu).getByRole('link', { name: link.title }),
        ).toBeTruthy();
    }

    expect(within(menu).getByRole('link', { name: 'Log in' })).toBeTruthy();
    expect(
        within(menu).getByRole('link', { name: 'Get started' }),
    ).toBeTruthy();
});

test('on a phone, signed in, the sheet holds the links and Dashboard', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    header(true);

    fireEvent.click(screen.getByRole('button', { name: 'Open menu' }));

    const menu = screen.getByRole('dialog');

    expect(within(menu).getByRole('link', { name: 'Dashboard' })).toBeTruthy();
    expect(within(menu).queryByRole('link', { name: 'Log in' })).toBeNull();
});

test('the footer has one row of links and the copyright line with the app name and year', () => {
    render(<PublicFooter links={links} year={2026} />);

    const row = screen.getByRole('navigation', { name: 'Footer' });

    expect(within(row).getAllByRole('link')).toHaveLength(4);
    expect(screen.getByText('© 2026 Acme')).toBeTruthy();
});

test('the words on the bar can be given in another language, on a computer and in the phone menu', () => {
    const labels = {
        login: '登录',
        getStarted: '立即开始',
        dashboard: '控制台',
        openMenu: '打开菜单',
        menu: '菜单',
    };

    vi.mocked(useIsMobile).mockReturnValue(false);
    const { unmount } = render(
        <TooltipProvider>
            <PublicHeader links={links} signedIn={false} labels={labels} />
        </TooltipProvider>,
    );

    expect(screen.getByRole('link', { name: '登录' })).toBeTruthy();
    expect(screen.getByRole('link', { name: '立即开始' })).toBeTruthy();
    unmount();

    vi.mocked(useIsMobile).mockReturnValue(true);
    render(
        <TooltipProvider>
            <PublicHeader links={links} signedIn labels={labels} />
        </TooltipProvider>,
    );
    fireEvent.click(screen.getByRole('button', { name: '打开菜单' }));

    expect(
        within(screen.getByRole('dialog')).getByRole('link', {
            name: '控制台',
        }),
    ).toBeTruthy();
});
