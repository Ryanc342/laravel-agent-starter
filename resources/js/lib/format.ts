/**
 * Every number and date React shows goes through these functions, so the
 * whole app formats them one way. The backend sends the values (money as
 * integer minor units (cents), quantities as decimal strings, dates as YYYY-MM-DD); these
 * only turn them into text. Set the Intl locale and the currency for the project here.
 */
const locale = 'en-MY';
const currency = 'MYR';

/** The Intl locale every number, date and month name is shown in. */
export function intlLocale(): string {
    return locale;
}

const cache = new Map<string, Intl.NumberFormat | Intl.DateTimeFormat>();

function formatter<T extends Intl.NumberFormat | Intl.DateTimeFormat>(
    id: string,
    create: () => T,
): T {
    let found = cache.get(id) as T | undefined;

    if (!found) {
        found = create();
        cache.set(id, found);
    }

    return found;
}

/**
 * An amount in minor units and its currency code. The currency is the
 * project's own when it is left out; the minor unit (two decimals for ringgit
 * and dollars, none for yen) comes from the currency.
 * 16400 → "RM 164.00", (4900, 'JPY') → "JP¥4,900"
 */
export function formatMoney(minor: number, code: string = currency): string {
    const format = formatter(
        `money|${code}`,
        () =>
            new Intl.NumberFormat(locale, {
                style: 'currency',
                currency: code,
            }),
    );

    const digits = format.resolvedOptions().maximumFractionDigits ?? 2;

    return format.format(minor / 10 ** digits);
}

/** "2.550" → "2.55", "1000" → "1,000" */
export function formatQuantity(value: number | string): string {
    return formatter(
        'quantity',
        () => new Intl.NumberFormat(locale, { maximumFractionDigits: 3 }),
    ).format(Number(value));
}

/** "2026-11-04" → "4 Nov 2026" */
export function formatDate(value: string): string {
    return formatter(
        'date',
        () =>
            new Intl.DateTimeFormat(locale, {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                timeZone: 'UTC',
            }),
    ).format(new Date(`${value}T00:00:00Z`));
}
