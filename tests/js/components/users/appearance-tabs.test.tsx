import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import AppearanceToggleTab from '@/components/users/appearance-tabs';
import { useAppearance } from '@/hooks/use-appearance';

vi.mock('@/hooks/use-appearance', () => ({ useAppearance: vi.fn() }));

const updateAppearance = vi.fn();

function appearanceIs(appearance: 'light' | 'dark' | 'system') {
    vi.mocked(useAppearance).mockReturnValue({
        appearance,
        resolvedAppearance: appearance === 'dark' ? 'dark' : 'light',
        updateAppearance,
    });
}

afterEach(() => {
    updateAppearance.mockReset();
    vi.mocked(useAppearance).mockReset();
});

test('the three modes are one control that says which mode is on', () => {
    appearanceIs('dark');

    render(<AppearanceToggleTab />);

    expect(screen.getByRole('radiogroup')).toBeTruthy();
    expect(
        screen
            .getAllByRole('radio')
            .map((mode) => [
                mode.textContent,
                mode.getAttribute('aria-checked'),
            ]),
    ).toEqual([
        ['Light', 'false'],
        ['Dark', 'true'],
        ['System', 'false'],
    ]);
});

test('choosing another mode applies it', () => {
    appearanceIs('light');

    render(<AppearanceToggleTab />);
    fireEvent.click(screen.getByRole('radio', { name: 'System' }));

    expect(updateAppearance).toHaveBeenCalledWith('system');
});

test('pressing the mode that is already on keeps it on', () => {
    appearanceIs('dark');

    render(<AppearanceToggleTab />);
    fireEvent.click(screen.getByRole('radio', { name: 'Dark' }));

    expect(updateAppearance).not.toHaveBeenCalled();
});
