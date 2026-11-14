import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { setLanguage } from '@/lib/i18n';

/**
 * The outermost layout of every page. It hands the page's language and its
 * sentences to `t()` before anything below renders, so a page that comes back
 * after the language was switched shows the new language at once.
 */
export default function LocaleLayout({ children }: { children: ReactNode }) {
    const { locale, translations } = usePage().props;

    setLanguage(locale, translations);

    return children;
}
