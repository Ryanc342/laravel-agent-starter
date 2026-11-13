import { expect, test } from 'vite-plus/test';
import { parseDateValue, toDateValue } from '@/lib/date-value';

test('a date value is read as that local day', () => {
    const date = parseDateValue('2026-11-04');

    expect([date?.getFullYear(), date?.getMonth(), date?.getDate()]).toEqual([
        2026, 10, 4,
    ]);
});

test('a value that is not a real day is not read', () => {
    for (const value of ['', '2026-11', '2026-13-01', '2026-02-30', 'x']) {
        expect(parseDateValue(value)).toBeUndefined();
    }
});

test('a day is written as YYYY-MM-DD and reads back unchanged', () => {
    expect(toDateValue(new Date(2026, 0, 5))).toBe('2026-01-05');

    for (const value of ['2026-01-05', '2026-12-31', '2028-02-29']) {
        expect(toDateValue(parseDateValue(value)!)).toBe(value);
    }
});
