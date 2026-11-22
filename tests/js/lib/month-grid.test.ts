import { expect, test } from 'vite-plus/test';
import {
    monthGrid,
    monthTitle,
    monthValue,
    parseMonth,
    shiftMonth,
    weekdayNames,
} from '@/lib/month-grid';

test("a month value is read, and anything else means the fallback month (the backend's today, never the browser clock)", () => {
    const fallback = new Date(2026, 5, 17);

    expect(monthValue(parseMonth('2026-11', fallback))).toBe('2026-11');
    expect(monthValue(parseMonth('', fallback))).toBe('2026-06');
    expect(monthValue(parseMonth('2026-13', fallback))).toBe('2026-06');
    expect(monthValue(parseMonth('nonsense', fallback))).toBe('2026-06');
});

test('the month before and after, across a year', () => {
    expect(shiftMonth('2026-12', 1)).toBe('2027-01');
    expect(shiftMonth('2026-01', -1)).toBe('2025-12');
    expect(shiftMonth('2026-11', 0)).toBe('2026-11');
});

test('November 2026 starts on a Sunday: five whole weeks, Sunday first', () => {
    const grid = monthGrid('2026-11');

    expect(grid).toHaveLength(5);
    expect(grid.every((week) => week.length === 7)).toBe(true);
    expect(grid[0][0]).toEqual({ date: '2026-11-01', day: 1, outside: false });
    expect(grid[4][1]).toEqual({ date: '2026-11-30', day: 30, outside: false });
    expect(grid[4][2]).toEqual({ date: '2026-12-01', day: 1, outside: true });
});

test('with Monday first the days of the month before fill the first week', () => {
    const grid = monthGrid('2026-11', 1);

    expect(grid[0][0]).toEqual({ date: '2026-10-26', day: 26, outside: true });
    expect(grid[0][6]).toEqual({ date: '2026-11-01', day: 1, outside: false });
    expect(grid).toHaveLength(6);
});

test('a leap February has its 29th, and the grid has no gaps', () => {
    const days = monthGrid('2028-02').flat();
    const dates = days.map((day) => day.date);

    expect(dates).toContain('2028-02-29');
    expect(new Set(dates).size).toBe(dates.length);
});

test('weekday names and the month title follow the language', () => {
    expect(weekdayNames('en', 0)[0]).toBe('Sun');
    expect(weekdayNames('en', 1)[0]).toBe('Mon');
    expect(weekdayNames('en', 1)).toHaveLength(7);
    expect(monthTitle('2026-11', 'en')).toBe('November 2026');
});

test('a month that is not a "YYYY-MM" value is a programming error past the URL', () => {
    expect(() => shiftMonth('2026-13', 1)).toThrow('"2026-13" is not a month');
    expect(() => monthGrid('')).toThrow('"" is not a month');
});
