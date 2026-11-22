import { act, renderHook } from '@testing-library/react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';

const answers = vi.hoisted(() => ({
    submit: vi.fn<(route: { url: string }) => Promise<unknown>>(),
}));

vi.mock('@inertiajs/react', () => ({
    useHttp: () => ({ submit: answers.submit }),
}));

beforeEach(() => {
    answers.submit.mockReset();
});

function answering(byUrl: Record<string, unknown>) {
    answers.submit.mockImplementation((route) => {
        const found = Object.entries(byUrl).find(([url]) =>
            route.url.includes(url),
        );

        return found === undefined || found[1] instanceof Error
            ? Promise.reject(found?.[1] ?? new Error('no answer'))
            : Promise.resolve(found[1]);
    });
}

test('the setup data is the QR code and the key, read from the server', async () => {
    answering({
        'qr-code': { svg: '<svg />' },
        'secret-key': { secretKey: 'ABCD1234' },
    });
    const { result } = renderHook(() => useTwoFactorAuth());

    expect(result.current.hasSetupData).toBe(false);

    await act(() => result.current.fetchSetupData());

    expect(result.current.qrCodeSvg).toBe('<svg />');
    expect(result.current.manualSetupKey).toBe('ABCD1234');
    expect(result.current.hasSetupData).toBe(true);
    expect(result.current.errors).toEqual([]);
});

test('a part the server cannot give is an error, and no setup data counts', async () => {
    answering({ 'qr-code': { svg: '<svg />' }, 'secret-key': new Error() });
    const { result } = renderHook(() => useTwoFactorAuth());

    await act(() => result.current.fetchSetupData());

    expect(result.current.hasSetupData).toBe(false);
    expect(result.current.errors).toEqual(['Failed to fetch a setup key']);
});

test('the recovery codes are read from the server, and a failure leaves none', async () => {
    answering({ 'recovery-codes': ['one', 'two'] });
    const { result } = renderHook(() => useTwoFactorAuth());

    await act(() => result.current.fetchRecoveryCodes());
    expect(result.current.recoveryCodesList).toEqual(['one', 'two']);

    answering({});
    await act(() => result.current.fetchRecoveryCodes());
    expect(result.current.recoveryCodesList).toEqual([]);
    expect(result.current.errors).toEqual(['Failed to fetch recovery codes']);
});

test('clearing forgets the setup data, the codes and the errors', async () => {
    answering({
        'qr-code': { svg: '<svg />' },
        'secret-key': { secretKey: 'ABCD1234' },
        'recovery-codes': ['one'],
    });
    const { result } = renderHook(() => useTwoFactorAuth());

    await act(() => result.current.fetchSetupData());
    await act(() => result.current.fetchRecoveryCodes());
    act(() => result.current.clearTwoFactorAuthData());

    expect(result.current.hasSetupData).toBe(false);
    expect(result.current.recoveryCodesList).toEqual([]);
    expect(result.current.errors).toEqual([]);
});
