import { parseDateValue, toDateValue } from '@/lib/date-value';

/** "2026-11" for a day. */
export function monthValue(date: Date): string {
    return toDateValue(date).slice(0, 7);
}

/** The first day of the month a "YYYY-MM" value names, or undefined when it names none. */
function firstDay(value: string): Date | undefined {
    return /^\d{4}-\d{2}$/.test(value)
        ? parseDateValue(`${value}-01`)
        : undefined;
}

/** The first day of the month a "YYYY-MM" value names; a value that names none is a programming error. */
function firstOf(value: string): Date {
    const day = firstDay(value);

    if (day === undefined) {
        throw new Error(`"${value}" is not a month (YYYY-MM).`);
    }

    return day;
}

/**
 * The month a "YYYY-MM" value from outside (the URL) names, or the month of
 * `fallback` when it names none. The fallback is the backend's today: the
 * browser's clock is never read.
 */
export function parseMonth(value: string, fallback: Date): Date {
    return (
        firstDay(value) ??
        new Date(fallback.getFullYear(), fallback.getMonth(), 1)
    );
}

/** The month before or after `value` ("2026-12" + 1 → "2027-01"). */
export function shiftMonth(value: string, by: number): string {
    const first = firstOf(value);

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
    const first = firstOf(value);
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
    }).format(firstOf(value));
}
