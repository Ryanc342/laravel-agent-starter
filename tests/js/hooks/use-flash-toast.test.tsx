import { renderHook } from '@testing-library/react';
import { toast } from 'sonner';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import { useFlashToast } from '@/hooks/use-flash-toast';

const listeners = vi.hoisted(() => new Map<string, (event: Event) => void>());

vi.mock('@inertiajs/react', () => ({
    router: {
        on: (name: string, listener: (event: Event) => void) => {
            listeners.set(name, listener);

            return () => listeners.delete(name);
        },
    },
}));
vi.mock('sonner', () => ({ toast: { success: vi.fn(), error: vi.fn() } }));

afterEach(() => {
    vi.mocked(toast.success).mockClear();
    vi.mocked(toast.error).mockClear();
});

function flash(data: unknown) {
    listeners.get('flash')?.(
        new CustomEvent('flash', { detail: { flash: data } }),
    );
}

test("the server's flashed toast shows in its own type", () => {
    renderHook(() => useFlashToast());

    flash({ toast: { type: 'success', message: 'Profile updated.' } });
    flash({ toast: { type: 'error', message: 'Too many attempts.' } });

    expect(toast.success).toHaveBeenCalledWith('Profile updated.');
    expect(toast.error).toHaveBeenCalledWith('Too many attempts.');
});

test('a flash without a toast shows nothing', () => {
    renderHook(() => useFlashToast());

    flash({ other: 'value' });

    expect(toast.success).not.toHaveBeenCalled();
    expect(toast.error).not.toHaveBeenCalled();
});

test('it stops listening when the layout goes away', () => {
    const { unmount } = renderHook(() => useFlashToast());

    unmount();

    expect(listeners.has('flash')).toBe(false);
});
