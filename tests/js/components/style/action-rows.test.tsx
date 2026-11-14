import { render, screen, within } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import ApprovalDemo from '@/components/style/approval-demo';
import DetailDemo from '@/components/style/detail-demo';
import ImportExportDemo from '@/components/style/import-export-demo';
import PrintDemo from '@/components/style/print-demo';
import type { ExampleInvoice } from '@/components/style/print-demo';
import type { ExampleTask } from '@/components/style/types';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('sonner', () => ({ toast: vi.fn() }));

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

    expect(buttonNames(actions)).toEqual([
        'Edit task',
        'Delete task',
        'Complete task',
    ]);
});

test('an approval header keeps its actions at the end, Approve last', () => {
    render(
        <ApprovalDemo
            title="Leave request"
            facts={[]}
            events={[]}
            states={{
                pending: {
                    label: 'Pending',
                    variant: 'warning',
                    can: { approve: true, reject: true },
                },
                approved: {
                    label: 'Approved',
                    variant: 'success',
                    can: { approve: false, reject: false },
                },
                rejected: {
                    label: 'Rejected',
                    variant: 'destructive',
                    can: { approve: false, reject: false },
                },
            }}
            answer={() => null}
        />,
    );
    const actions = screen.getByTestId('record-actions');

    expect(buttonNames(actions)).toEqual(['Edit request', 'Reject', 'Approve']);
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

    expect(buttonNames(bar)).toEqual(['Download PDF', 'Print']);
});

test('the list toolbar sits at the right end with the create button last', () => {
    render(<ImportExportDemo rowErrors={[]} more="" />);
    const actions = screen.getByTestId('list-actions');

    expect(buttonNames(actions).at(-1)).toBe('Add task');
});
