import { router, usePage } from '@inertiajs/react';
import { update } from '@/routes/locale';

/**
 * The page's language, the languages to offer (each named in its own language,
 * sent by the backend from the one list the endpoint accepts) and the way to
 * change it. One route saves the choice (on the user when signed in, and in the
 * `locale` cookie) and redirects back, so the same page returns in the new
 * language.
 */
export function useLanguage() {
    const { locale, locales } = usePage().props;

    const changeLanguage = (next: string) => {
        if (next !== locale) {
            router.put(
                update.url(),
                { locale: next },
                { preserveScroll: true },
            );
        }
    };

    return { language: locale, languages: locales, changeLanguage };
}

/** The HTML `lang` of a language value: zh_CN is zh-CN. */
export function htmlLang(value: string): string {
    return value.replace('_', '-');
}
