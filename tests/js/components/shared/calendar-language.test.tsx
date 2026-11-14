import { render, screen } from '@testing-library/react';
import { afterEach, expect, test } from 'vite-plus/test';
import { Calendar } from '@/components/ui/calendar';
import { setLanguage } from '@/lib/i18n';

afterEach(() => {
    setLanguage('en', {});
});

test('the calendar names the month and the weekdays in English by default', () => {
    render(<Calendar defaultMonth={new Date(2026, 10, 1)} />);

    expect(screen.getByText('November 2026')).toBeTruthy();
    expect(screen.getByText('Mo')).toBeTruthy();
});

test('in Chinese the calendar names the month and the weekdays in Chinese', () => {
    setLanguage('zh_CN', {});
    render(<Calendar defaultMonth={new Date(2026, 10, 1)} />);

    expect(screen.getByText('2026年11月')).toBeTruthy();
    expect(screen.getByText('一')).toBeTruthy();
});
