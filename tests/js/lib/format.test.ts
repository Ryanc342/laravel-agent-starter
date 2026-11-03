import { expect, test } from 'vite-plus/test';
import { formatDate, formatMoney, formatQuantity } from '@/lib/format';

// Intl puts a no-break space between the currency and the amount.
const nbsp = ' ';

test('money shows integer cents as ringgit with two decimals and grouping', () => {
    expect(formatMoney(16400)).toBe(`RM${nbsp}164.00`);
    expect(formatMoney(5)).toBe(`RM${nbsp}0.05`);
    expect(formatMoney(123456750)).toBe(`RM${nbsp}1,234,567.50`);
});

test('quantities show without trailing zeros, up to three decimals', () => {
    expect(formatQuantity('2.550')).toBe('2.55');
    expect(formatQuantity('8.000')).toBe('8');
    expect(formatQuantity(1000.125)).toBe('1,000.125');
});

test('a date shows the same day in every time zone', () => {
    expect(formatDate('2026-11-04')).toBe('4 Nov 2026');
    expect(formatDate('2026-12-31')).toBe('31 Dec 2026');
});
