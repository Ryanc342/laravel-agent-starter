import { fireEvent, render, screen, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import ManagePasskeys from '@/components/users/manage-passkeys';
import PasskeyRegistration from '@/components/users/passkey-register';
import PasskeyVerify from '@/components/users/passkey-verify';
import { TooltipProvider } from '@/components/ui/tooltip';

const passkeys = vi.hoisted(() => ({
    supported: true,
    loading: false,
    error: null as string | null,
    register: vi.fn(),
    verify: vi.fn(),
    sent: [] as string[],
}));

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));
vi.mock('@laravel/passkeys/react', () => ({
    usePasskeyRegister: () => ({
        register: passkeys.register,
        isLoading: passkeys.loading,
        error: passkeys.error,
        isSupported: passkeys.supported,
    }),
    usePasskeyVerify: () => ({
        verify: passkeys.verify,
        isLoading: passkeys.loading,
        error: passkeys.error,
        isSupported: passkeys.supported,
    }),
}));
vi.mock('@inertiajs/react', () => ({
    router: { reload: vi.fn(), visit: vi.fn() },
    useFormContext: () => ({ processing: false }),
    Form: ({
        action,
        children,
    }: {
        action: string;
        children: (slot: {
            errors: Record<string, string>;
            resetAndClearErrors: () => void;
        }) => ReactNode;
    }) => (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                passkeys.sent.push(action);
            }}
        >
            {children({ errors: {}, resetAndClearErrors: () => undefined })}
        </form>
    ),
}));

beforeEach(() => {
    passkeys.supported = true;
    passkeys.loading = false;
    passkeys.error = null;
    passkeys.register.mockReset();
    passkeys.verify.mockReset();
    passkeys.sent.length = 0;
});

const passkey = {
    id: '0198a1b2-0000-7000-8000-000000000001',
    name: 'MacBook',
    authenticator: null,
    created_at_diff: '2 days ago',
    last_used_at_diff: null,
};

function manage(props: Parameters<typeof ManagePasskeys>[0]) {
    render(
        <TooltipProvider>
            <ManagePasskeys {...props} />
        </TooltipProvider>,
    );
}

test('passkeys are not shown when the app does not offer them', () => {
    manage({ canManagePasskeys: false, passkeys: [passkey] });

    expect(screen.queryByText('Passkeys')).toBeNull();
});

test('with none, the section says so', () => {
    manage({ canManagePasskeys: true, passkeys: [] });

    expect(screen.getByText('You have no passkeys yet.')).toBeTruthy();
});

test('deleting a passkey asks first, then sends it to the delete route', () => {
    manage({ canManagePasskeys: true, passkeys: [passkey] });

    fireEvent.click(screen.getByRole('button', { name: 'Delete' }));
    expect(passkeys.sent).toEqual([]);

    const dialog = screen.getByRole('dialog');
    expect(dialog.textContent).toContain('“MacBook” will be deleted');
    fireEvent.click(within(dialog).getByRole('button', { name: 'Delete' }));

    expect(passkeys.sent).toEqual([`/settings/passkeys/${passkey.id}/delete`]);
});

test('a browser without passkeys says so instead of the form', () => {
    passkeys.supported = false;
    render(<PasskeyRegistration onSuccess={() => undefined} />);

    expect(
        screen.getByText('Passkeys are not supported in this browser.'),
    ).toBeTruthy();
});

test('Add opens the form; Register waits for a name and sends it to the browser prompt', () => {
    render(
        <TooltipProvider>
            <PasskeyRegistration onSuccess={() => undefined} />
        </TooltipProvider>,
    );

    fireEvent.click(screen.getByRole('button', { name: 'Add' }));
    const register = screen.getByRole('button', { name: 'Register' });

    expect((register as HTMLButtonElement).disabled).toBe(true);

    fireEvent.change(screen.getByLabelText('Passkey name'), {
        target: { value: 'iPhone' },
    });
    fireEvent.submit(register.closest('form')!);

    expect(passkeys.register).toHaveBeenCalledWith('iPhone');
});

test('while the browser prompt is open, Register is disabled and busy', () => {
    passkeys.loading = true;
    render(
        <TooltipProvider>
            <PasskeyRegistration onSuccess={() => undefined} />
        </TooltipProvider>,
    );

    fireEvent.click(screen.getByRole('button', { name: 'Add' }));
    const register = screen.getByRole('button', { name: /Register/ });

    expect(register.getAttribute('aria-busy')).toBe('true');
});

test('signing in with a passkey starts the browser prompt; without passkey support nothing shows', () => {
    const { unmount } = render(<PasskeyVerify />);

    fireEvent.click(
        screen.getByRole('button', { name: 'Sign in with a passkey' }),
    );
    expect(passkeys.verify).toHaveBeenCalledTimes(1);
    unmount();

    passkeys.supported = false;
    const { container } = render(<PasskeyVerify />);
    expect(container.innerHTML).toBe('');
});
