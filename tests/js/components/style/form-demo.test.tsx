import { fireEvent, render, screen, within } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import { priceCart } from '@/components/style/customer-examples';
import FormDemo from '@/components/style/form-demo';
import { TooltipProvider } from '@/components/ui/tooltip';
import { formatMoney } from '@/lib/format';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { today: { date: '2026-11-20', year: 2026 } } }),
}));

const labels = { subtotal: 'Subtotal', tax: 'Tax (6%)', total: 'Total' };

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
                            name: 'Website design',
                            unit: 800000,
                            total: 0,
                            quantity: '1',
                        },
                        {
                            id: 2,
                            name: 'Domain renewal',
                            unit: 12000,
                            total: 0,
                            quantity: '1',
                        },
                    ],
                }}
                price={(items) => priceCart(items, labels)}
            />
        </TooltipProvider>,
    );

    fireEvent.click(
        screen.getByRole('button', { name: 'Wide: an invoice with lines' }),
    );

    return screen.getByRole('dialog', { name: 'New invoice' });
}

function total(dialog: HTMLElement): string {
    return (
        within(dialog).getByText('Total').nextElementSibling?.textContent ?? ''
    );
}

test('the invoice opens in the wide dialog with its lines and totals', () => {
    const dialog = openWide();

    expect(dialog.dataset.size).toBe('wide');
    expect(within(dialog).getAllByRole('listitem')).toHaveLength(2);
    expect(total(dialog)).toBe(formatMoney(860720));
});

test('removing a line or changing a quantity asks for new totals', () => {
    const dialog = openWide();

    fireEvent.click(
        within(dialog).getByRole('button', { name: 'Remove Domain renewal' }),
    );

    expect(within(dialog).getAllByRole('listitem')).toHaveLength(1);
    expect(total(dialog)).toBe(formatMoney(848000));

    fireEvent.change(
        within(dialog).getByLabelText('Quantity of Website design'),
        { target: { value: '2' } },
    );

    expect(total(dialog)).toBe(formatMoney(1696000));
});
