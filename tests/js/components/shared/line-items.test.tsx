import { fireEvent, render, screen, within } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import LineItems, { type LineItem } from '@/components/shared/line-items';
import OrderSummary from '@/components/shared/order-summary';
import { TooltipProvider } from '@/components/ui/tooltip';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));

const nbsp = '\u00a0';
// Testing Library normalises a no-break space in the text it reads to a plain one.
const shown = (text: string) => text.replace(/\u00a0/g, ' ');

const items: LineItem[] = [
    {
        id: 1,
        name: 'Spare parts, box of 20',
        unit: 15000,
        quantity: '3',
        total: 45000,
    },
    { id: 2, name: '寄送服务', unit: 4500, quantity: '1', total: 4500 },
];

function lines(props: Partial<React.ComponentProps<typeof LineItems>> = {}) {
    const onQuantityChange = vi.fn();
    const onRemove = vi.fn();

    render(
        <TooltipProvider>
            <LineItems
                items={items}
                currency="MYR"
                onQuantityChange={onQuantityChange}
                onRemove={onRemove}
                {...props}
            />
        </TooltipProvider>,
    );

    return { onQuantityChange, onRemove };
}

test('each quantity input is named items[index][quantity] and shows what was typed', () => {
    lines();

    const first = screen.getByRole('spinbutton', {
        name: 'Quantity of Spare parts, box of 20',
    });
    const second = screen.getByRole('spinbutton', {
        name: 'Quantity of 寄送服务',
    });

    expect(first.getAttribute('name')).toBe('items[0][quantity]');
    expect(second.getAttribute('name')).toBe('items[1][quantity]');
    expect((first as HTMLInputElement).value).toBe('3');
});

test('the unit price and the line total come from the backend through formatMoney', () => {
    lines();

    const row = screen.getByText('Spare parts, box of 20').closest('li')!;

    expect(within(row).getByText(shown(`RM${nbsp}150.00`))).toBeTruthy();
    expect(within(row).getByText(shown(`RM${nbsp}450.00`))).toBeTruthy();
});

test('typing a quantity reports the item and the text, and removing reports the item', () => {
    const { onQuantityChange, onRemove } = lines();

    fireEvent.change(
        screen.getByRole('spinbutton', { name: 'Quantity of 寄送服务' }),
        {
            target: { value: '4' },
        },
    );
    expect(onQuantityChange).toHaveBeenCalledWith(2, '4');

    fireEvent.click(screen.getByRole('button', { name: 'Remove 寄送服务' }));
    expect(onRemove).toHaveBeenCalledWith(2);
});

test('an error for items.1.quantity marks only the second quantity, with no visible text under it', () => {
    lines({ errors: { 'items.1.quantity': 'Enter a quantity of 1 or more.' } });

    expect(
        screen
            .getByRole('spinbutton', { name: 'Quantity of 寄送服务' })
            .getAttribute('aria-invalid'),
    ).toBe('true');
    expect(
        screen
            .getByRole('spinbutton', {
                name: 'Quantity of Spare parts, box of 20',
            })
            .getAttribute('aria-invalid'),
    ).toBeNull();
    // The message is a toast; on the page only screen readers get it.
    expect(
        screen.getByText('Enter a quantity of 1 or more.').className,
    ).toContain('sr-only');
});

test('the order summary lists its rows and ends with the total', () => {
    const { container } = render(
        <OrderSummary
            currency="MYR"
            rows={[
                { label: 'Subtotal', amount: 49500 },
                { label: 'Tax', amount: 2970 },
            ]}
            total={{ label: 'Total', amount: 52470 }}
        />,
    );

    const terms = [...container.querySelectorAll('dt')].map(
        (term) => term.textContent,
    );
    const values = [...container.querySelectorAll('dd')].map(
        (value) => value.textContent,
    );

    expect(terms).toEqual(['Subtotal', 'Tax', 'Total']);
    expect(values).toEqual([
        `RM${nbsp}495.00`,
        `RM${nbsp}29.70`,
        `RM${nbsp}524.70`,
    ]);
    expect(container.querySelectorAll('dd')[2].className).toContain(
        'tabular-nums',
    );
});
