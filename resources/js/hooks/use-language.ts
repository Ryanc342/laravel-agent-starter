import { router, usePage } from '@inertiajs/react';
import { update } from '@/routes/locale';

/** The languages of the app, each named in its own language (never translated). */
export const languages = [
    { value: 'en', name: 'English', short: 'EN' },
    { value: 'zh_CN', name: '中文', short: '中文' },
] as const;

export type Language = (typeof languages)[number]['value'];

/**
 * The page's language and the way to change it. One route saves the choice
 * (on the user when signed in, and in the `locale` cookie) and redirects back,
 * so the same page returns in the new language.
 */
export function useLanguage() {
    const { locale } = usePage().props;

    const changeLanguage = (next: Language) => {
        if (next !== locale) {
            router.put(
                update.url(),
                { locale: next },
                { preserveScroll: true },
            );
        }
    };

    return { language: locale as Language, changeLanguage };
}
