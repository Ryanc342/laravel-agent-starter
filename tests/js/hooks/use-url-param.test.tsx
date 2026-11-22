import { act, renderHook } from '@testing-library/react';
import type { ChangeEvent, KeyboardEvent } from 'react';
import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
import { useListQuery } from '@/hooks/use-list-query';
import { useUrlParam } from '@/hooks/use-url-param';

const state = vi.hoisted(() => ({ url: '/tasks', get: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    router: { get: state.get },
    usePage: () => ({ url: state.url }),
}));

beforeEach(() => {
    state.url = '/tasks?page=3&per_page=50';
    state.get.mockClear();
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
});

const typed = (value: string) =>
    ({ target: { value } }) as ChangeEvent<HTMLInputElement>;

test('changing the list goes back to page 1, keeps the rows per page and keeps the scroll and the open menus', () => {
    const { result } = renderHook(() => useListQuery());

    result.current.change({ search: 'invoice' });

    expect(state.get).toHaveBeenCalledWith(
        expect.stringMatching(/^\/tasks\?/),
        {},
        { preserveScroll: true, preserveState: true },
    );
    const asked = new URL(state.get.mock.calls[0][0], 'http://x').searchParams;
    expect(asked.get('search')).toBe('invoice');
    expect(asked.get('per_page')).toBe('50');
    expect(asked.has('page')).toBe(false);
});

test('typing shows at once and asks for the list after a pause, once', () => {
    const { result } = renderHook(() => useUrlParam('search'));

    act(() => result.current.inputProps.onChange(typed('inv')));
    act(() => result.current.inputProps.onChange(typed('invoice')));

    expect(result.current.value).toBe('invoice');
    expect(state.get).not.toHaveBeenCalled();

    act(() => {
        vi.advanceTimersByTime(400);
    });

    expect(state.get).toHaveBeenCalledTimes(1);
});

test('Enter asks at once', () => {
    const { result } = renderHook(() => useUrlParam('search'));

    act(() => result.current.inputProps.onChange(typed('invoice')));
    act(() =>
        result.current.inputProps.onKeyDown({
            key: 'Enter',
            preventDefault: () => undefined,
        } as KeyboardEvent<HTMLInputElement>),
    );

    expect(state.get).toHaveBeenCalledTimes(1);
});

test('with no pause (a picked date) it asks at once', () => {
    const { result } = renderHook(() => useUrlParam('due_from', 0));

    act(() => result.current.inputProps.onChange(typed('2026-11-01')));

    expect(state.get).toHaveBeenCalledTimes(1);
});

test('when the address changes another way (Clear), the box follows it', () => {
    state.url = '/tasks?search=invoice';
    const { result, rerender } = renderHook(() => useUrlParam('search'));

    expect(result.current.value).toBe('invoice');

    state.url = '/tasks';
    rerender();

    expect(result.current.value).toBe('');
});
