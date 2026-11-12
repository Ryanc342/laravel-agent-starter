import { render, screen } from '@testing-library/react';
import { toast } from 'sonner';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import TwoFactorSetupStep from '@/components/users/two-factor-setup-step';

vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));
vi.mock('@/hooks/use-appearance', () => ({
    useAppearance: () => ({ resolvedAppearance: 'light' }),
}));

afterEach(() => {
    vi.mocked(toast.error).mockClear();
});

function step(errors: string[]) {
    return (
        <TwoFactorSetupStep
            qrCodeSvg={null}
            manualSetupKey={null}
            buttonText="Continue"
            onNextStep={() => {}}
            errors={errors}
        />
    );
}

test('a QR code that could not be loaded is a toast, not an Alert on the page', () => {
    const { container } = render(step(['The QR code could not be loaded.']));

    expect(toast.error).toHaveBeenCalledWith(
        'The QR code could not be loaded.',
        { id: 'two-factor-load-error' },
    );
    expect(container.querySelector('[data-slot="alert"]')).toBeNull();
});

test('the region that failed says what to do, so it is not left blank or spinning', () => {
    render(step(['The QR code could not be loaded.']));

    expect(screen.getByRole('alert').textContent).toContain('try again');
    expect(screen.queryByRole('button', { name: 'Continue' })).toBeNull();
});

test('without errors the setup step shows no toast', () => {
    render(step([]));

    expect(toast.error).not.toHaveBeenCalled();
    expect(screen.getByRole('button', { name: 'Continue' })).toBeTruthy();
});
