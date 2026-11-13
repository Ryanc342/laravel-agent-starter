import { fireEvent, render, screen } from '@testing-library/react';
import { toast } from 'sonner';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import BulkActionsDemo from '@/components/style/bulk-actions-demo';
import type { ExampleTask } from '@/components/style/types';
import { TooltipProvider } from '@/components/ui/tooltip';
import { useIsMobile } from '@/hooks/use-mobile';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: vi.fn() }));
vi.mock('@inertiajs/react', () => ({ usePage: () => ({ url: '/style' }) }));
vi.mock('sonner', () => ({ toast: { success: vi.fn() } }));

const tasks: ExampleTask[] = [1, 2, 3].map((id) => ({
    id,
    title: `Task ${id}`,
    status: 'Open',
    overdue: false,
    due_on: '2026-11-10',
    amount: 1000,
}));

beforeEach(() => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    vi.mocked(toast.success).mockClear();
});

function demo() {
    render(
        <TooltipProvider>
            <BulkActionsDemo tasks={tasks} total={83} />
        </TooltipProvider>,
    );
}

test('on a phone, one ticked card offers "Select this page", then "Select all 83 results", and the action takes all of them', () => {
    demo();

    expect(screen.queryByRole('region', { name: 'Bulk actions' })).toBeNull();

    fireEvent.click(screen.getByRole('checkbox', { name: 'Select Task 2' }));

    expect(screen.getByRole('region').textContent).toContain('1 selected');
    expect(
        screen.queryByRole('button', { name: /Select all 83 results/ }),
    ).toBeNull();

    fireEvent.click(screen.getByRole('button', { name: 'Select this page' }));

    expect(screen.getByRole('region').textContent).toContain('3 selected');
    expect(
        screen.queryByRole('button', { name: 'Select this page' }),
    ).toBeNull();

    fireEvent.click(
        screen.getByRole('button', { name: 'Select all 83 results' }),
    );

    expect(screen.getByRole('region').textContent).toContain('All 83 selected');

    fireEvent.click(screen.getByRole('button', { name: 'Complete' }));

    expect(toast.success).toHaveBeenCalledWith('83 tasks completed.');
});

test('on a computer the bar never offers "Select this page"', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    demo();

    fireEvent.click(screen.getByRole('checkbox', { name: 'Select Task 2' }));

    expect(
        screen.queryByRole('button', { name: 'Select this page' }),
    ).toBeNull();
});
