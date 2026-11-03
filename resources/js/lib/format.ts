/**
 * Every number and date React shows goes through these functions, so the
 * whole app formats them one way. The backend sends the values (money as
 * integer cents, quantities as decimal strings, dates as YYYY-MM-DD); these
 * only turn them into text. Set the locale and currency for the project here.
 */
const locale = 'en-MY';
const currency = 'MYR';

const money = new Intl.NumberFormat(locale, { style: 'currency', currency });
const quantity = new Intl.NumberFormat(locale, { maximumFractionDigits: 3 });
const date = new Intl.DateTimeFormat(locale, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
});

/** 16400 → "RM 164.00" */
export function formatMoney(cents: number): string {
    return money.format(cents / 100);
}

/** "2.550" → "2.55", "1000" → "1,000" */
export function formatQuantity(value: number | string): string {
    return quantity.format(Number(value));
}

/** "2026-11-04" → "4 Nov 2026" */
export function formatDate(value: string): string {
    return date.format(new Date(`${value}T00:00:00Z`));
}
