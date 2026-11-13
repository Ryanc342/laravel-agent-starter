import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import BulkActionBar from '@/components/shared/bulk-action-bar';
import { Button } from '@/components/ui/button';
import { TooltipProvider } from '@/components/ui/tooltip';
import { useIsMobile } from '@/hooks/use-mobile';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: vi.fn() }));

beforeEach(() => {
    vi.mocked(useIsMobile).mockReturnValue(false);
});

function bar(props: Partial<React.ComponentProps<typeof BulkActionBar>> = {}) {
    const onClear = vi.fn();

    render(
        <TooltipProvider>
            <BulkActionBar count={2} onClear={onClear} {...props}>
                <Button>Complete</Button>
                <Button variant="outline">Delete</Button>
            </BulkActionBar>
        </TooltipProvider>,
    );

    return onClear;
}

test('nothing shows while no row is ticked', () => {
    bar({ count: 0 });

    expect(screen.queryByRole('region', { name: 'Bulk actions' })).toBeNull();
});

test('it says how many rows are ticked and holds the actions', () => {
    bar();

    const region = screen.getByRole('region', { name: 'Bulk actions' });

    expect(region.textContent).toContain('2 selected');
    expect(screen.getByRole('button', { name: 'Complete' })).toBeTruthy();
    expect(screen.getByRole('button', { name: 'Delete' })).toBeTruthy();
});

test('it floats at the bottom of the page above the content', () => {
    bar();

    const wrapper = screen.getByRole('region').parentElement!;

    expect(wrapper.className).toContain('fixed');
    expect(wrapper.className).toContain('bottom-4');
    expect(screen.getByRole('region').className).toContain('shadow-md');
});

test('it leaves room under itself, so it never hides the end of the page', () => {
    bar();

    const room =
        screen.getByRole('region').parentElement!.previousElementSibling;

    expect(room?.getAttribute('aria-hidden')).toBe('true');
    expect(room?.className).toContain('h-20');
});

test('the close button clears the selection', () => {
    const onClear = bar();

    fireEvent.click(screen.getByRole('button', { name: 'Clear selection' }));

    expect(onClear).toHaveBeenCalledTimes(1);
});

test('a whole page ticked with more results offers "Select all N results"', () => {
    const onSelectAllResults = vi.fn();
    bar({ total: 83, onSelectAllResults });

    fireEvent.click(
        screen.getByRole('button', { name: 'Select all 83 results' }),
    );

    expect(onSelectAllResults).toHaveBeenCalledTimes(1);
});

test('once every result is selected it says so and stops offering the choice', () => {
    bar({
        count: 20,
        total: 83,
        allResults: true,
        onSelectAllResults: vi.fn(),
    });

    expect(screen.getByRole('region').textContent).toContain('All 83 selected');
    expect(screen.queryByRole('button', { name: /Select all/ })).toBeNull();
});

test('on a phone, where cards have no header checkbox, the bar offers "Select this page"', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    const onSelectPage = vi.fn();
    bar({ count: 1, onSelectPage });

    fireEvent.click(screen.getByRole('button', { name: 'Select this page' }));

    expect(onSelectPage).toHaveBeenCalledTimes(1);
});

test('on a computer the header checkbox selects the page, so the bar does not offer it', () => {
    bar({ count: 1, onSelectPage: vi.fn() });

    expect(
        screen.queryByRole('button', { name: 'Select this page' }),
    ).toBeNull();
});

test('it does not offer "Select this page" without the caller\'s choice, or once all results are selected', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    bar({ count: 1 });

    expect(
        screen.queryByRole('button', { name: 'Select this page' }),
    ).toBeNull();
});
