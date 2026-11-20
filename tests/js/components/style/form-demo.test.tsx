import { fireEvent, render, screen, within } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import FormDemo from '@/components/style/form-demo';
import { TooltipProvider } from '@/components/ui/tooltip';
import { formatMoney } from '@/lib/format';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { today: { date: '2026-11-20', year: 2026 } } }),
}));

function openWide() {
    render(
        <TooltipProvider>
            <FormDemo
                task={{
                    id: 1,
                    title: 'Call the supplier',
                    status: 'open',
                    overdue: false,
                    due_on: '2026-11-24',
                    amount: 0,
                }}
                requiredMessage="The title field is required."
                invoice={{
                    customer: 'Invoice Desk Sdn Bhd',
                    date: '2026-11-20',
                    due: '2026-12-20',
                    note: 'Pay before the due date',
                    currency: 'MYR',
                    lines: [
                        {
                            id: 1,
                            description: 'Website design',
                            quantity: '1',
                            unit: '8,000.00',
                            total: 800000,
                        },
                        {
                            id: 2,
                            description: 'Domain renewal',
                            quantity: '1',
                            unit: '120.00',
                            total: 12000,
                        },
                    ],
                    total: 812000,
                }}
            />
        </TooltipProvider>,
    );

    fireEvent.click(
        screen.getByRole('button', { name: 'Wide: an invoice with lines' }),
    );

    return screen.getByRole('dialog', { name: 'New invoice' });
}

test('the invoice opens in the wide dialog with its lines, their amounts and the total from the data', () => {
    const dialog = openWide();

    expect(dialog.dataset.size).toBe('wide');
    expect(
        within(dialog).getByLabelText('Description of line 2'),
    ).toHaveProperty('value', 'Domain renewal');
    expect(dialog.textContent).toContain(formatMoney(12000, 'MYR'));
    expect(dialog.textContent).toContain(`Total ${formatMoney(812000, 'MYR')}`);
});

test('a changed, removed or added line shows no amount and no total until saved, so nothing is added up here', () => {
    const dialog = openWide();

    fireEvent.change(within(dialog).getByLabelText('Quantity of line 1'), {
        target: { value: '2' },
    });

    expect(dialog.textContent).not.toContain(formatMoney(800000, 'MYR'));
    expect(within(dialog).getByText('Total —')).toBeTruthy();

    fireEvent.click(
        within(dialog).getByRole('button', { name: 'Remove line 2' }),
    );
    fireEvent.click(within(dialog).getByRole('button', { name: 'Add line' }));

    expect(
        within(dialog).getByLabelText('Description of line 2'),
    ).toHaveProperty('value', '');
    expect(within(dialog).queryByLabelText('Description of line 3')).toBeNull();
    expect(within(dialog).getByText('Total —')).toBeTruthy();
});
