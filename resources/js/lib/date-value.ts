/**
 * The `YYYY-MM-DD` text a date field reads and writes (URLs and requests use
 * it as is) and the local `Date` the calendar shows. Local parts are used
 * both ways, so a picked day never moves with the time zone.
 */
const pattern = /^(\d{4})-(\d{2})-(\d{2})$/;

/** "2026-11-04" → that day at local midnight; anything else, or a day that does not exist, → undefined. */
export function parseDateValue(value: string): Date | undefined {
    const match = pattern.exec(value);

    if (!match) {
        return undefined;
    }

    const [year, month, day] = [match[1], match[2], match[3]].map(Number);
    const date = new Date(year, month - 1, day);

    return date.getFullYear() === year &&
        date.getMonth() === month - 1 &&
        date.getDate() === day
        ? date
        : undefined;
}

/** A local day → "2026-11-04". */
export function toDateValue(date: Date): string {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${String(date.getFullYear()).padStart(4, '0')}-${month}-${day}`;
}
