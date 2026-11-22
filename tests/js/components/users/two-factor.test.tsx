import { fireEvent, render, screen, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import ManageTwoFactor from '@/components/users/manage-two-factor';
import TwoFactorRecoveryCodes from '@/components/users/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/users/two-factor-setup-modal';
import TwoFactorVerificationStep from '@/components/users/two-factor-verification-step';
import { TooltipProvider } from '@/components/ui/tooltip';

const sent = vi.hoisted(() => [] as string[]);

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('@/hooks/use-appearance', () => ({
    useAppearance: () => ({ resolvedAppearance: 'light' }),
}));
vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));
vi.mock('@/hooks/use-two-factor-auth', () => ({
    OTP_MAX_LENGTH: 6,
    useTwoFactorAuth: () => ({
        qrCodeSvg: null,
        manualSetupKey: null,
        hasSetupData: false,
        recoveryCodesList: [],
        errors: [],
        clearSetupData: vi.fn(),
        clearTwoFactorAuthData: vi.fn(),
        fetchSetupData: vi.fn(() => Promise.resolve()),
        fetchRecoveryCodes: vi.fn(() => Promise.resolve()),
    }),
}));
vi.mock('@inertiajs/react', () => ({
    useFormContext: () => ({ processing: false }),
    Form: ({
        action,
        children,
    }: {
        action: string;
        children:
            | ReactNode
            | ((slot: {
                  errors: Record<string, string>;
                  processing: boolean;
                  resetAndClearErrors: () => void;
              }) => ReactNode);
    }) => (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                sent.push(action);
            }}
        >
            {typeof children === 'function'
                ? children({
                      errors: {},
                      processing: false,
                      resetAndClearErrors: () => undefined,
                  })
                : children}
        </form>
    ),
}));

beforeEach(() => {
    sent.length = 0;
    // jsdom does not scroll; the codes scroll into view when shown.
    Element.prototype.scrollIntoView = vi.fn();
});

function withTooltips(node: ReactNode) {
    return render(<TooltipProvider>{node}</TooltipProvider>);
}

test('two-factor is not shown when the app does not offer it', () => {
    const { container } = withTooltips(
        <ManageTwoFactor canManageTwoFactor={false} />,
    );

    expect(container.textContent).toBe('');
});

test('turned off, it offers Enable; turned on, Disable and the recovery codes', () => {
    const { unmount } = withTooltips(
        <ManageTwoFactor canManageTwoFactor twoFactorEnabled={false} />,
    );
    expect(screen.getByRole('button', { name: 'Enable' })).toBeTruthy();
    unmount();

    withTooltips(<ManageTwoFactor canManageTwoFactor twoFactorEnabled />);
    expect(screen.getByRole('button', { name: 'Disable' })).toBeTruthy();
    expect(screen.getByText('2FA recovery codes')).toBeTruthy();
});

test('the recovery codes load when shown, and View shows them', () => {
    const fetchRecoveryCodes = vi.fn(() => Promise.resolve());
    withTooltips(
        <TwoFactorRecoveryCodes
            recoveryCodesList={['code-one', 'code-two']}
            fetchRecoveryCodes={fetchRecoveryCodes}
            errors={[]}
        />,
    );

    const view = screen.getByRole('button', { name: 'View' });
    expect(view.getAttribute('aria-expanded')).toBe('false');

    fireEvent.click(view);

    expect(
        screen
            .getByRole('button', { name: 'Hide' })
            .getAttribute('aria-expanded'),
    ).toBe('true');
    expect(
        screen.getByRole('list', { name: 'Recovery codes' }).textContent,
    ).toContain('code-one');
});

test('regenerating the codes asks first, because the old ones stop working', async () => {
    withTooltips(
        <TwoFactorRecoveryCodes
            recoveryCodesList={['code-one']}
            fetchRecoveryCodes={() => Promise.resolve()}
            errors={[]}
        />,
    );

    fireEvent.click(screen.getByRole('button', { name: 'View' }));
    fireEvent.click(await screen.findByRole('button', { name: 'Regenerate' }));
    expect(sent).toEqual([]);

    const dialog = screen.getByRole('dialog');
    expect(dialog.textContent).toContain('The old codes stop working');
    fireEvent.click(within(dialog).getByRole('button', { name: 'Regenerate' }));

    expect(sent).toEqual(['/user/two-factor-recovery-codes']);
});

test('Confirm waits for all six digits; Back goes back without sending', () => {
    const onBack = vi.fn();
    withTooltips(
        <TwoFactorVerificationStep onClose={() => undefined} onBack={onBack} />,
    );

    expect(
        (screen.getByRole('button', { name: 'Confirm' }) as HTMLButtonElement)
            .disabled,
    ).toBe(true);

    fireEvent.click(screen.getByRole('button', { name: 'Back' }));

    expect(onBack).toHaveBeenCalledTimes(1);
    expect(sent).toEqual([]);
});

test('the setup dialog asks for the QR code and the key when it opens', () => {
    const fetchSetupData = vi.fn(() => Promise.resolve());
    withTooltips(
        <TwoFactorSetupModal
            isOpen
            onClose={() => undefined}
            requiresConfirmation
            twoFactorEnabled={false}
            qrCodeSvg={null}
            manualSetupKey={null}
            clearSetupData={() => undefined}
            fetchSetupData={fetchSetupData}
            errors={[]}
        />,
    );

    expect(fetchSetupData).toHaveBeenCalledTimes(1);
    expect(
        screen.getByRole('dialog', {
            name: 'Enable two-factor authentication',
        }),
    ).toBeTruthy();
});
