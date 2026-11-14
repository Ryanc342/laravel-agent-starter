/**
 * Every sentence a user reads goes through `t()` or `tChoice()`
 * (docs/rules/translations.md). A sentence is keyed by its English text, so
 * English needs no table: a missing key is shown as it is. The backend sends the
 * current language's sentences (Chinese: lang/zh_CN.json) as the `translations`
 * page prop, and `LocaleLayout` hands them here before the page renders.
 *
 * `:name` placeholders and the `a|b` plural syntax follow Laravel's, so the same
 * sentence reads the same in PHP and in React.
 */
export type Replacements = Record<string, string | number>;

let language = 'en';
let strings: Record<string, string> = {};

/** Called once per render of the root layout, with the page's `locale` and `translations`. */
export function setLanguage(
    locale: string,
    translations: Record<string, string> | undefined,
): void {
    language = locale;
    strings = translations ?? {};
}

/** `en` or `zh_CN`: the language the page is in. */
export function currentLanguage(): string {
    return language;
}

/** Marks a sentence that is translated later, where it is shown: `t(item.title)`. */
export function msg(english: string): string {
    return english;
}

/** Fills `:name`, `:Name` and `:NAME` the way Laravel does. */
function fill(text: string, replace: Replacements): string {
    return Object.entries(replace)
        .sort(([a], [b]) => b.length - a.length)
        .reduce((result, [name, value]) => {
            const text = String(value);

            return result
                .replaceAll(`:${name}`, text)
                .replaceAll(
                    `:${name.charAt(0).toUpperCase()}${name.slice(1)}`,
                    text.charAt(0).toUpperCase() + text.slice(1),
                )
                .replaceAll(`:${name.toUpperCase()}`, text.toUpperCase());
        }, text);
}

export function t(key: string, replace: Replacements = {}): string {
    return fill(strings[key] ?? key, replace);
}

/** Which plural form a count takes: Chinese has one, English has "one" and "other". */
function pluralIndex(count: number): number {
    return language === 'en' ? (count === 1 ? 0 : 1) : 0;
}

/** `{1} one task|[2,*] :count tasks`, or plain `one task|:count tasks`, as Laravel's trans_choice. */
function choose(line: string, count: number): string {
    const segments = line.split('|');
    const explicit = /^[{[]/;

    for (const segment of segments) {
        const match =
            /^(?:\{(\d+)\}|\[(\d+|\*),(\d+|\*)\]|\{(\d+)\})\s*(.*)$/s.exec(
                segment.trim(),
            );

        if (!match || !explicit.test(segment.trim())) {
            continue;
        }

        if (match[1] !== undefined || match[4] !== undefined) {
            if (count === Number(match[1] ?? match[4])) {
                return match[5];
            }

            continue;
        }

        const from = match[2] === '*' ? -Infinity : Number(match[2]);
        const to = match[3] === '*' ? Infinity : Number(match[3]);

        if (count >= from && count <= to) {
            return match[5];
        }
    }

    const plain = segments.map((segment) =>
        segment.trim().replace(/^(?:\{\d+\}|\[[^\]]*\])\s*/, ''),
    );

    return plain[Math.min(pluralIndex(count), plain.length - 1)];
}

export function tChoice(
    key: string,
    count: number,
    replace: Replacements = {},
): string {
    return fill(choose(strings[key] ?? key, count), { count, ...replace });
}
