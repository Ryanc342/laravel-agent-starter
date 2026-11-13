import { parseDateValue, toDateValue } from '@/lib/date-value';

/** "2026-11" for a day, and the month a "YYYY-MM" value names (this month when it is not one). */
export function monthValue(date: Date): string {
    return toDateValue(date).slice(0, 7);
}

export function parseMonth(value: string, fallback = new Date()): Date {
    const day = /^\d{4}-\d{2}$/.test(value)
        ? parseDateValue(`${value}-01`)
        : undefined;

    return day ?? new Date(fallback.getFullYear(), fallback.getMonth(), 1);
}

/** The month before or after `value` ("2026-12" + 1 → "2027-01"). */
export function shiftMonth(value: string, by: number): string {
    const first = parseMonth(value);

    return monthValue(new Date(first.getFullYear(), first.getMonth() + by, 1));
}

/**
 * The days a month grid shows: whole weeks, from the week that holds the 1st to
 * the week that holds the last day. `weekStartsOn` is 0 for Sunday, 1 for
 * Monday. Built with `Date` only; `outside` marks the days of the next and
 * previous month.
 */
export function monthGrid(
    value: string,
    weekStartsOn = 0,
): { date: string; day: number; outside: boolean }[][] {
    const first = parseMonth(value);
    const lead = (first.getDay() - weekStartsOn + 7) % 7;
    const start = new Date(first.getFullYear(), first.getMonth(), 1 - lead);
    const days = new Date(
        first.getFullYear(),
        first.getMonth() + 1,
        0,
    ).getDate();
    const weeks = Math.ceil((lead + days) / 7);

    return Array.from({ length: weeks }, (_, week) =>
        Array.from({ length: 7 }, (_, offset) => {
            const day = new Date(
                start.getFullYear(),
                start.getMonth(),
                start.getDate() + week * 7 + offset,
            );

            return {
                date: toDateValue(day),
                day: day.getDate(),
                outside: day.getMonth() !== first.getMonth(),
            };
        }),
    );
}

/** The short weekday names in the grid's order, in the given language. */
export function weekdayNames(locale: string, weekStartsOn = 0): string[] {
    const format = new Intl.DateTimeFormat(locale, { weekday: 'short' });

    // 2026-11-01 is a Sunday.
    return Array.from({ length: 7 }, (_, index) =>
        format.format(new Date(2026, 10, 1 + ((index + weekStartsOn) % 7))),
    );
}

/** "November 2026" in the given language. */
export function monthTitle(value: string, locale: string): string {
    return new Intl.DateTimeFormat(locale, {
        month: 'long',
        year: 'numeric',
    }).format(parseMonth(value));
}
