import { fireEvent, render, screen, within } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import ApprovalDemo from '@/components/style/approval-demo';
import DetailDemo from '@/components/style/detail-demo';
import ImportExportDemo from '@/components/style/import-export-demo';
import PrintDemo from '@/components/style/print-demo';
import type { ExampleInvoice } from '@/components/style/print-demo';
import type { ExampleTask } from '@/components/style/types';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('sonner', () => ({ toast: vi.fn() }));
vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/react')>()),
    usePage: () => ({ url: '/style' }),
}));

const longTitle =
    'Prepare the quarterly report for the whole finance team and send it before the meeting on Friday';

const task: ExampleTask = {
    id: 1,
    title: longTitle,
    status: 'Open',
    overdue: false,
    due_on: '2026-11-10',
    amount: 1000,
};

function buttonNames(container: HTMLElement) {
    return within(container)
        .getAllByRole('button')
        .map((button) => button.textContent);
}

test('a record header keeps its actions at the end of the row, even when a long title wraps, with the main one last', () => {
    render(<DetailDemo task={task} />);
    const actions = screen.getByTestId('record-actions');

    expect(buttonNames(actions)).toEqual(['Edit', 'Delete', 'Complete']);
});

test('an approval header keeps its actions at the end, Approve last; after a decision only Edit is left', () => {
    const view = {
        label: 'Pending',
        variant: 'warning' as const,
        can: { approve: true, reject: true },
        events: [],
    };
    render(
        <ApprovalDemo
            title="Leave request"
            facts={[]}
            views={[
                { ...view, moment: 'Waiting' },
                {
                    ...view,
                    moment: 'Approved',
                    label: 'Approved',
                    variant: 'success',
                    can: { approve: false, reject: false },
                },
            ]}
        />,
    );

    expect(buttonNames(document.body)).toEqual([
        'Edit',
        'Reject',
        'Approve',
        'Edit',
    ]);
});

test('the print bar sits at the right end with Print last', () => {
    const invoice: ExampleInvoice = {
        number: '2026-0012',
        date: '2026-11-04',
        seller: 'Seller',
        buyer: 'Buyer',
        lines: [],
        subtotal: 0,
        tax: 0,
        total: 0,
    };
    render(<PrintDemo invoice={invoice} />);
    const bar = screen.getByTestId('print-bar');

    expect(buttonNames(bar)).toEqual(['Print']);
});

test('the list toolbar sits at the right end with the create button last', () => {
    render(<ImportExportDemo rowErrors={[]} />);

    expect(
        [...document.body.querySelectorAll('button, a')]
            .slice(0, 3)
            .map((element) => element.textContent),
    ).toEqual(['Import', 'Export', 'Add']);
});

test('the export demo keeps the download and says what it would download', async () => {
    const { toast } = await import('sonner');
    render(<ImportExportDemo rowErrors={[]} />);

    const link = screen.getByRole('link', { name: 'Export' });
    expect(link.getAttribute('href')).toBe('/style?export=csv');
    expect(fireEvent.click(link)).toBe(false);
    expect(toast).toHaveBeenCalledWith(
        'The export downloads tasks.csv, as the list is shown.',
    );
});

test('the failed import shows the row errors of the data, as Inertia reports them', () => {
    const reported = vi.fn();
    const listen = (event: Event) =>
        reported((event as CustomEvent).detail.errors);
    document.addEventListener('inertia:error', listen);

    render(<ImportExportDemo rowErrors={['Row 2: Title is required.']} />);
    fireEvent.click(screen.getByRole('button', { name: 'Show the errors' }));

    document.removeEventListener('inertia:error', listen);
    expect(reported).toHaveBeenCalledWith({
        'rows.2.row': 'Row 2: Title is required.',
    });
});
