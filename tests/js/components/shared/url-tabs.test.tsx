import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import UrlTabs from '@/components/shared/url-tabs';

const state = vi.hoisted(() => ({ url: '/tasks/4', get: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    router: { get: state.get },
    usePage: () => ({ url: state.url }),
}));

const tabs = [
    { value: 'overview', label: 'Overview', content: 'The facts' },
    { value: 'history', label: 'History', content: 'The events' },
];

beforeEach(() => {
    state.url = '/tasks/4';
    state.get.mockReset();
});

test('with no tab in the URL the first tab shows', () => {
    render(<UrlTabs tabs={tabs} />);

    expect(screen.getByText('The facts')).toBeTruthy();
    expect(screen.queryByText('The events')).toBeNull();
});

test('the tab in the URL shows, and an unknown one falls back to the first', () => {
    state.url = '/tasks/4?tab=history';
    const { unmount } = render(<UrlTabs tabs={tabs} />);

    expect(screen.getByText('The events')).toBeTruthy();
    unmount();

    state.url = '/tasks/4?tab=nonsense';
    render(<UrlTabs tabs={tabs} />);
    expect(screen.getByText('The facts')).toBeTruthy();
});

test('choosing a tab asks for the same page with that tab and keeps the other parameters', async () => {
    state.url = '/tasks/4?per_page=50';
    render(<UrlTabs tabs={tabs} />);

    fireEvent.mouseDown(screen.getByRole('tab', { name: 'History' }), {
        button: 0,
    });

    await waitFor(() => expect(state.get).toHaveBeenCalled());
    const address = new URL(state.get.mock.calls[0][0], 'http://localhost');

    expect(address.pathname).toBe('/tasks/4');
    expect(address.searchParams.get('tab')).toBe('history');
    expect(address.searchParams.get('per_page')).toBe('50');
});
