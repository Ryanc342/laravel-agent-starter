import { act, renderHook } from '@testing-library/react';
import { expect, test } from 'vite-plus/test';
import { useRowSelection } from '@/hooks/use-row-selection';

function selection(ids: number[], key = '/tasks') {
    return renderHook(({ ids, key }) => useRowSelection(ids, key), {
        initialProps: { ids, key },
    });
}

test('nothing is ticked at first', () => {
    const { result } = selection([1, 2, 3]);

    expect(result.current.count).toBe(0);
    expect(result.current.pageState).toBe('none');
    expect(result.current.isSelected(1)).toBe(false);
});

test('ticking one row ticks it, and the page is half ticked', () => {
    const { result } = selection([1, 2, 3]);

    act(() => result.current.toggle(2));

    expect(result.current.ids).toEqual([2]);
    expect(result.current.pageState).toBe('some');

    act(() => result.current.toggle(2));
    expect(result.current.count).toBe(0);
});

test('the header checkbox ticks the rows of this page, then unticks them', () => {
    const { result } = selection([1, 2, 3]);

    act(() => result.current.togglePage());
    expect(result.current.ids).toEqual([1, 2, 3]);
    expect(result.current.pageState).toBe('all');
    expect(result.current.allResults).toBe(false);

    act(() => result.current.togglePage());
    expect(result.current.count).toBe(0);
});

test('"Select all N results" marks every result, and every row counts as selected', () => {
    const { result } = selection([1, 2, 3]);

    act(() => result.current.selectAllResults());

    expect(result.current.allResults).toBe(true);
    expect(result.current.isSelected(999)).toBe(true);
});

test('a change of the list address (search, sort, filters, page) starts the selection again', () => {
    const { result, rerender } = selection([1, 2, 3]);

    act(() => result.current.selectAllResults());
    rerender({ ids: [4, 5, 6], key: '/tasks?page=2' });

    expect(result.current.count).toBe(0);
    expect(result.current.allResults).toBe(false);
    expect(result.current.pageState).toBe('none');
});

test('clear unticks everything, also "all results"', () => {
    const { result } = selection([1, 2]);

    act(() => result.current.selectAllResults());
    act(() => result.current.clear());

    expect(result.current.count).toBe(0);
    expect(result.current.allResults).toBe(false);
});

test('selecting the page ticks every row of it and keeps the rows ticked, without "all results"', () => {
    const { result } = selection([1, 2, 3]);

    act(() => result.current.toggle(2));
    act(() => result.current.selectPage());

    expect(result.current.ids).toEqual([1, 2, 3]);
    expect(result.current.pageState).toBe('all');
    expect(result.current.allResults).toBe(false);
});
