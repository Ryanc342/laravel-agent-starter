import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
import { TooltipProvider } from '@/components/ui/tooltip';
import TwoFactorSetupStep from '@/components/users/two-factor-setup-step';

// Dark mode is on: the QR code must look the same as in light mode.
vi.mock('@/hooks/use-appearance', () => ({
    useAppearance: () => ({ resolvedAppearance: 'dark' }),
}));
vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));

const writeText = vi.fn();

beforeEach(() => {
    writeText.mockResolvedValue(undefined);
    Object.defineProperty(navigator, 'clipboard', {
        value: { writeText },
        configurable: true,
    });
});

afterEach(() => {
    writeText.mockReset();
});

function step({
    qrCodeSvg,
    manualSetupKey,
}: {
    qrCodeSvg: string | null;
    manualSetupKey: string | null;
}) {
    return render(
        <TooltipProvider>
            <TwoFactorSetupStep
                qrCodeSvg={qrCodeSvg}
                manualSetupKey={manualSetupKey}
                buttonText="Continue"
                onNextStep={() => {}}
                errors={[]}
            />
        </TooltipProvider>,
    );
}

test('while the QR code and the setup key load, each place shows its shape, not a spinner', () => {
    const { container } = step({ qrCodeSvg: null, manualSetupKey: null });

    expect(screen.queryByRole('status')).toBeNull();
    expect(container.querySelectorAll('[data-slot="skeleton"]')).toHaveLength(
        2,
    );
});

test('the setup key has a label and the copy button has a name', async () => {
    step({ qrCodeSvg: '<svg></svg>', manualSetupKey: 'ABCD EFGH' });

    const key = screen.getByRole('textbox', { name: 'Setup key' });
    expect((key as HTMLInputElement).value).toBe('ABCD EFGH');
    expect((key as HTMLInputElement).readOnly).toBe(true);

    fireEvent.click(screen.getByRole('button', { name: 'Copy setup key' }));

    await waitFor(() => expect(writeText).toHaveBeenCalledWith('ABCD EFGH'));
});

test('the QR code stays dark on white in dark mode, so a phone can scan it', () => {
    const { container } = step({
        qrCodeSvg: '<svg></svg>',
        manualSetupKey: 'ABCD EFGH',
    });
    const code = container.querySelector('svg')?.parentElement as HTMLElement;

    expect(code.style.filter).toBe('');
});
