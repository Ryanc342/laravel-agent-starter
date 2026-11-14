import { expect, test } from 'vite-plus/test';
import { formatDate, formatMoney, formatQuantity } from '@/lib/format';

// Intl puts a no-break space between the currency and the amount.
const nbsp = ' ';

test('money shows integer cents as ringgit with two decimals and grouping', () => {
    expect(formatMoney(16400)).toBe(`RM${nbsp}164.00`);
    expect(formatMoney(5)).toBe(`RM${nbsp}0.05`);
    expect(formatMoney(123456750)).toBe(`RM${nbsp}1,234,567.50`);
});

test('money takes the currency of the amount, and reads the minor unit from it', () => {
    expect(formatMoney(4900, 'USD')).toBe('US$49.00');
    expect(formatMoney(4900, 'JPY')).toBe('JP¥4,900');
    expect(formatMoney(16400, 'MYR')).toBe(`RM${nbsp}164.00`);
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

test('dates, numbers and money follow the language the page is in', async () => {
    const { setLanguage } = await import('@/lib/i18n');

    setLanguage('zh_CN', {});

    try {
        expect(formatDate('2026-11-04')).toBe('2026年11月4日');
        expect(formatQuantity(1000.125)).toBe('1,000.125');
        expect(formatMoney(16400)).toContain('164.00');
        expect(formatMoney(16400, 'CNY')).toBe('¥164.00');
    } finally {
        setLanguage('en', {});
    }

    expect(formatDate('2026-11-04')).toBe('4 Nov 2026');
});
