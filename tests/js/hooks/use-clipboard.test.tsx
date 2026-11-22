import { act, renderHook } from '@testing-library/react';
import { toast } from 'sonner';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import { useClipboard } from '@/hooks/use-clipboard';

vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));

afterEach(() => {
    vi.mocked(toast.error).mockClear();
    vi.unstubAllGlobals();
});

function clipboard(writeText: (text: string) => Promise<void>) {
    vi.stubGlobal('navigator', { clipboard: { writeText } });
}

test('a copy remembers what was copied, so the button can show a tick', async () => {
    const writeText = vi.fn(() => Promise.resolve());
    clipboard(writeText);
    const { result } = renderHook(() => useClipboard());

    await act(async () => {
        expect(await result.current[1]('ABCD-1234')).toBe(true);
    });

    expect(writeText).toHaveBeenCalledWith('ABCD-1234');
    expect(result.current[0]).toBe('ABCD-1234');
    expect(toast.error).not.toHaveBeenCalled();
});

test('a copy the browser refuses tells the user, and nothing counts as copied', async () => {
    clipboard(() => Promise.reject(new Error('denied')));
    const { result } = renderHook(() => useClipboard());

    await act(async () => {
        expect(await result.current[1]('ABCD-1234')).toBe(false);
    });

    expect(result.current[0]).toBeNull();
    expect(toast.error).toHaveBeenCalledWith(
        'Could not copy. Select the text and copy it yourself.',
        { id: 'clipboard-error' },
    );
});

test('a browser without a clipboard tells the user the same way', async () => {
    vi.stubGlobal('navigator', {});
    const { result } = renderHook(() => useClipboard());

    await act(async () => {
        expect(await result.current[1]('ABCD-1234')).toBe(false);
    });

    expect(toast.error).toHaveBeenCalledTimes(1);
});
