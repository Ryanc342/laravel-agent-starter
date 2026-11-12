import { renderHook } from '@testing-library/react';
import { toast } from 'sonner';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import { useErrorsToast } from '@/hooks/use-errors-toast';

vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));

afterEach(() => {
    vi.mocked(toast.error).mockClear();
});

test('errors show as one toast, each message once', () => {
    renderHook(() =>
        useErrorsToast(['No QR code.', 'No QR code.', 'Try later.'], 'setup'),
    );

    expect(toast.error).toHaveBeenCalledTimes(1);
    expect(toast.error).toHaveBeenCalledWith('No QR code. Try later.', {
        id: 'setup',
    });
});

test('no errors, no toast', () => {
    renderHook(() => useErrorsToast([], 'setup'));
    renderHook(() => useErrorsToast(undefined, 'setup'));

    expect(toast.error).not.toHaveBeenCalled();
});

test('the same errors in a new array do not toast again', () => {
    const { rerender } = renderHook(
        ({ errors }) => useErrorsToast(errors, 'setup'),
        { initialProps: { errors: ['No QR code.'] } },
    );

    rerender({ errors: ['No QR code.'] });

    expect(toast.error).toHaveBeenCalledTimes(1);
});

test('different errors toast again', () => {
    const { rerender } = renderHook(
        ({ errors }) => useErrorsToast(errors, 'setup'),
        { initialProps: { errors: ['No QR code.'] } },
    );

    rerender({ errors: ['The code expired.'] });

    expect(toast.error).toHaveBeenCalledTimes(2);
});
