/**
 * Every number and date React shows goes through these functions, so the
 * whole app formats them one way. The backend sends the values (money as
 * integer minor units (cents), quantities as decimal strings, dates as YYYY-MM-DD); these
 * only turn them into text, in the language the page is in (`lib/i18n.ts`).
 * Set the Intl locale of each language and the currency for the project here.
 */
import { currentLanguage } from '@/lib/i18n';

const locales: Record<string, string> = { en: 'en-MY', zh_CN: 'zh-CN' };
const currency = 'MYR';

/** The Intl locale of the page's language. */
export function intlLocale(): string {
    return locales[currentLanguage()] ?? locales.en;
}

// One formatter per language, so switching language never reuses another's.
const cache = new Map<string, Intl.NumberFormat | Intl.DateTimeFormat>();

function formatter<T extends Intl.NumberFormat | Intl.DateTimeFormat>(
    id: string,
    create: (locale: string) => T,
): T {
    const key = `${intlLocale()}|${id}`;
    let found = cache.get(key) as T | undefined;

    if (!found) {
        found = create(intlLocale());
        cache.set(key, found);
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
        (locale) =>
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
        (locale) => new Intl.NumberFormat(locale, { maximumFractionDigits: 3 }),
    ).format(Number(value));
}

/** "2026-11-04" → "4 Nov 2026", or "2026年11月4日" in Chinese */
export function formatDate(value: string): string {
    return formatter(
        'date',
        (locale) =>
            new Intl.DateTimeFormat(locale, {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                timeZone: 'UTC',
            }),
    ).format(new Date(`${value}T00:00:00Z`));
}
