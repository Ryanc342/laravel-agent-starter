import { expect, test } from 'vite-plus/test';
import { listParam, listParams, withListParams } from '@/lib/list-query';

test('a change sets the parameter, keeps the rest and goes back to page 1', () => {
    expect(
        withListParams('/tasks?page=3&per_page=50&search=milk', {
            sort: 'title',
            direction: 'asc',
        }),
    ).toBe('/tasks?per_page=50&search=milk&sort=title&direction=asc');
});

test('a null or empty value removes the parameter', () => {
    expect(
        withListParams('/tasks?search=milk&sort=title', {
            search: '',
            sort: null,
        }),
    ).toBe('/tasks');
});

test('a list of values becomes a bracket parameter, replacing the old ones', () => {
    const url = withListParams('/tasks?status[]=open&per_page=10', {
        status: ['open', 'done'],
    });
    const address = new URL(url, 'http://localhost');

    expect(address.searchParams.getAll('status[]')).toEqual(['open', 'done']);
    expect(address.searchParams.get('per_page')).toBe('10');
    expect(withListParams(url, { status: [] })).toBe('/tasks?per_page=10');
});

test('the parameters are read back from the address', () => {
    const url = '/tasks?search=milk&status[]=open&status[]=done';

    expect(listParam(url, 'search')).toBe('milk');
    expect(listParam(url, 'sort')).toBe('');
    expect(listParams(url, 'status')).toEqual(['open', 'done']);
    expect(listParams(url, 'other')).toEqual([]);
});
