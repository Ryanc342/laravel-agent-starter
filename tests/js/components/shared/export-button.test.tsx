import { usePage } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import ExportButton from '@/components/shared/export-button';

vi.mock('@inertiajs/react', () => ({ usePage: vi.fn() }));

test('the link downloads the list as it is shown now, every page of it', () => {
    vi.mocked(usePage).mockReturnValue({
        url: '/tasks?search=invoice&status[]=open&sort=-due_on&page=3',
    } as ReturnType<typeof usePage>);

    render(<ExportButton>Export</ExportButton>);

    const link = screen.getByRole('link', { name: 'Export' });
    expect(link.hasAttribute('download')).toBe(true);
    expect(link.getAttribute('href')).toBe(
        `/tasks?search=invoice&${encodeURIComponent('status[]')}=open&sort=-due_on&export=csv`,
    );
});
