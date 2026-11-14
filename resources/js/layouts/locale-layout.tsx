import { usePage } from '@inertiajs/react';
import { Fragment, type ReactNode } from 'react';
import { setLanguage } from '@/lib/i18n';

/**
 * The outermost layout of every page. It hands the page's language and its
 * sentences to `t()` before anything below renders. The page below is keyed by
 * the language: `t()` is not a subscription, and Inertia does not render a page
 * again when its own props did not change, so without the key a switch would
 * leave the old language on the screen until the next visit.
 */
export default function LocaleLayout({ children }: { children: ReactNode }) {
    const { locale, translations } = usePage().props;

    setLanguage(locale, translations);

    return <Fragment key={locale}>{children}</Fragment>;
}
