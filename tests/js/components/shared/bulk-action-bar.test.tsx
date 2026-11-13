import { fireEvent, render, screen } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import BulkActionBar from '@/components/shared/bulk-action-bar';
import { Button } from '@/components/ui/button';
import { TooltipProvider } from '@/components/ui/tooltip';

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
