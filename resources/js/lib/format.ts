/**
 * Every number and date React shows goes through these functions, so the
 * whole app formats them one way. The backend sends the values (money as
 * integer minor units (cents), quantities as decimal strings, dates as YYYY-MM-DD); these
 * only turn them into text. Set the locale and currency for the project here.
 */
const locale = 'en-MY';
const currency = 'MYR';

const moneyFormats = new Map<string, Intl.NumberFormat>();
const quantity = new Intl.NumberFormat(locale, { maximumFractionDigits: 3 });
const date = new Intl.DateTimeFormat(locale, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
});

/**
 * An amount in minor units and its currency code. The currency is the
 * project's own when it is left out; the minor unit (two decimals for ringgit
 * and dollars, none for yen) comes from the currency.
 * 16400 → "RM 164.00", (4900, 'JPY') → "JP¥4,900"
 */
export function formatMoney(minor: number, code: string = currency): string {
    let format = moneyFormats.get(code);

    if (!format) {
        format = new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: code,
        });
        moneyFormats.set(code, format);
    }

    const digits = format.resolvedOptions().maximumFractionDigits ?? 2;

    return format.format(minor / 10 ** digits);
}

/** "2.550" → "2.55", "1000" → "1,000" */
export function formatQuantity(value: number | string): string {
    return quantity.format(Number(value));
}

/** "2026-11-04" → "4 Nov 2026" */
export function formatDate(value: string): string {
    return date.format(new Date(`${value}T00:00:00Z`));
}
