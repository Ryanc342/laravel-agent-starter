import { render, screen } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import LocaleLayout from '@/layouts/locale-layout';
import { setLanguage, t } from '@/lib/i18n';

const page = vi.hoisted(() => ({
    props: { locale: 'en', translations: {} as Record<string, string> },
}));

vi.mock('@inertiajs/react', () => ({ usePage: () => page }));

afterEach(() => {
    page.props = { locale: 'en', translations: {} };
    setLanguage('en', {});
});

function Sentence() {
    return <p>{t('Save changes')}</p>;
}

test("the page below reads the sentences of the page's language", () => {
    page.props = {
        locale: 'zh_CN',
        translations: { 'Save changes': '保存更改' },
    };

    render(
        <LocaleLayout>
            <Sentence />
        </LocaleLayout>,
    );

    expect(screen.getByText('保存更改')).toBeTruthy();
});

test('a language switch shows the new language even though the page below did not change', () => {
    const body = <Sentence />;
    const { rerender } = render(<LocaleLayout>{body}</LocaleLayout>);

    expect(screen.getByText('Save changes')).toBeTruthy();

    page.props = {
        locale: 'zh_CN',
        translations: { 'Save changes': '保存更改' },
    };
    rerender(<LocaleLayout>{body}</LocaleLayout>);

    expect(screen.getByText('保存更改')).toBeTruthy();
});
