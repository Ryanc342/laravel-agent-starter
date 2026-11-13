import { expect, test } from 'vite-plus/test';
import { withPerPage } from '@/lib/pagination';

test('the rows per page are added to the address and the list goes back to its first page', () => {
    expect(withPerPage('/notifications?page=3', 50)).toBe(
        '/notifications?per_page=50',
    );
});

test('the filters stay and an earlier rows-per-page value is replaced', () => {
    expect(withPerPage('/tasks?status=open&per_page=10&page=2', 100)).toBe(
        '/tasks?status=open&per_page=100',
    );
});

test('an address with no query gets just the rows per page', () => {
    expect(withPerPage('/tasks', 10)).toBe('/tasks?per_page=10');
});
