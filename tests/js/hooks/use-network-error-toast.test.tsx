import { renderHook } from '@testing-library/react';
import { toast } from 'sonner';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import {
    NETWORK_ERROR_MESSAGE,
    useNetworkErrorToast,
} from '@/hooks/use-network-error-toast';

vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));

/** What Inertia fires when a request cannot reach the server. */
function failRequest(): void {
    document.dispatchEvent(
        new CustomEvent('inertia:networkError', {
            cancelable: true,
            detail: { error: new Error('offline') },
        }),
    );
}

afterEach(() => {
    vi.mocked(toast.error).mockClear();
});

test('a request that cannot reach the server shows the network toast', () => {
    renderHook(() => useNetworkErrorToast());

    failRequest();

    expect(toast.error).toHaveBeenCalledWith(NETWORK_ERROR_MESSAGE, {
        id: 'network-error',
    });
});

test('several failures share one toast', () => {
    renderHook(() => useNetworkErrorToast());

    failRequest();
    failRequest();

    const ids = vi
        .mocked(toast.error)
        .mock.calls.map(([, options]) => options?.id);
    expect(ids).toEqual(['network-error', 'network-error']);
});

test('nothing is shown after the page that listened is gone', () => {
    const { unmount } = renderHook(() => useNetworkErrorToast());
    unmount();

    failRequest();

    expect(toast.error).not.toHaveBeenCalled();
});
