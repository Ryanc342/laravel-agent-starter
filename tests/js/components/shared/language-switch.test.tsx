import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import LanguageSwitch from '@/components/shared/language-switch';
import LanguageToggle from '@/components/users/language-toggle';

const page = vi.hoisted(() => ({
    locale: 'en',
    put: vi.fn(),
    locales: [
        { value: 'en', label: 'English', short: 'EN' },
        { value: 'zh_CN', label: '中文', short: '中文' },
    ],
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({
        props: { locale: page.locale, locales: page.locales },
    }),
    router: { put: page.put },
}));

afterEach(() => {
    page.locale = 'en';
    page.put.mockReset();
});

test('the guest switch shows EN and 中文 and marks the current language', () => {
    page.locale = 'zh_CN';
    render(<LanguageSwitch />);

    const [english, chinese] = screen.getAllByRole('button');

    expect(english.textContent).toBe('EN');
    expect(chinese.textContent).toBe('中文');
    expect(chinese.getAttribute('aria-current')).toBe('true');
    expect(english.getAttribute('aria-current')).toBeNull();
});

test('choosing the other language sends it to the one locale route and keeps the scroll', () => {
    render(<LanguageSwitch />);

    fireEvent.click(screen.getByRole('button', { name: '中文' }));

    expect(page.put).toHaveBeenCalledWith(
        '/locale',
        { locale: 'zh_CN' },
        { preserveScroll: true },
    );
});

test('choosing the language that is on sends nothing', () => {
    render(<LanguageSwitch />);

    fireEvent.click(screen.getByRole('button', { name: 'English' }));

    expect(page.put).not.toHaveBeenCalled();
});

test('the Appearance choice names each language in its own language and switches', () => {
    render(<LanguageToggle />);

    expect(
        screen
            .getAllByRole('radio')
            .map((item) => [
                item.textContent,
                item.getAttribute('aria-checked'),
            ]),
    ).toEqual([
        ['English', 'true'],
        ['中文', 'false'],
    ]);

    fireEvent.click(screen.getByRole('radio', { name: '中文' }));

    expect(page.put).toHaveBeenCalledWith(
        '/locale',
        { locale: 'zh_CN' },
        { preserveScroll: true },
    );
});
