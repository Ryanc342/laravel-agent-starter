import { act, renderHook } from '@testing-library/react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import { useLatestNotifications } from '@/hooks/use-latest-notifications';

const state = vi.hoisted(() => ({
    reload: vi.fn(),
    props: {} as Record<string, unknown>,
}));

vi.mock('@inertiajs/react', () => ({
    router: { reload: state.reload },
    usePage: () => ({ props: state.props }),
}));

type Options = {
    only: string[];
    onHttpException: () => boolean;
    onNetworkError: () => void;
};

function lastOptions(): Options {
    const calls = state.reload.mock.calls;

    return calls[calls.length - 1][0] as Options;
}

beforeEach(() => {
    state.reload.mockReset();
    state.props = {};
});

test('the notifications are undefined until they arrive, and are the prop after', () => {
    const { result, rerender } = renderHook(() => useLatestNotifications());

    expect(result.current.latest).toBeUndefined();

    state.props = { latestNotifications: [{ id: 'a' }] };
    rerender();

    expect(result.current.latest).toEqual([{ id: 'a' }]);
    expect(result.current.failed).toBe(false);
});

test('load asks the server for only the latest notifications', () => {
    const { result } = renderHook(() => useLatestNotifications());

    act(() => result.current.load());

    expect(state.reload).toHaveBeenCalledTimes(1);
    expect(lastOptions().only).toEqual(['latestNotifications']);
});

test('a server error ends the visit here and marks the load as failed', () => {
    const { result } = renderHook(() => useLatestNotifications());

    act(() => result.current.load());
    let handled = true;
    act(() => {
        handled = lastOptions().onHttpException();
    });

    expect(handled).toBe(false);
    expect(result.current.failed).toBe(true);
});

test('a network error marks the load as failed too', () => {
    const { result } = renderHook(() => useLatestNotifications());

    act(() => result.current.load());
    act(() => lastOptions().onNetworkError());

    expect(result.current.failed).toBe(true);
});

test('asking again clears the failure until the next one', () => {
    const { result } = renderHook(() => useLatestNotifications());

    act(() => result.current.load());
    act(() => lastOptions().onNetworkError());
    expect(result.current.failed).toBe(true);

    act(() => result.current.load());
    expect(result.current.failed).toBe(false);
    expect(state.reload).toHaveBeenCalledTimes(2);
});
