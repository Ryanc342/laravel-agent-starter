import { render, screen, waitFor } from '@testing-library/react';
import { toast } from 'sonner';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import FormField from '@/components/shared/form-field';
import { useIsMobile } from '@/hooks/use-mobile';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: vi.fn() }));
vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));

afterEach(() => {
    vi.mocked(useIsMobile).mockReset();
    vi.mocked(toast.error).mockClear();
});

function field(props: Partial<Parameters<typeof FormField>[0]> = {}) {
    return (
        <FormField label="Title" htmlFor="title" {...props}>
            <input id="title" name="title" />
        </FormField>
    );
}

function layoutOf(input: HTMLElement): string | null {
    return input.closest('[data-layout]')?.getAttribute('data-layout') ?? null;
}

test('on desktop the label sits to the left of the input', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(field());
    const input = screen.getByLabelText('Title');

    expect(layoutOf(input)).toBe('row');
    expect(screen.getByText('Title').className).toContain('col-span-1');
    expect(input.parentElement?.className).toContain('col-span-2');
});

test('on a phone the label moves above the input', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);

    render(field());
    const input = screen.getByLabelText('Title');
    const label = screen.getByText('Title');

    expect(layoutOf(input)).toBe('stacked');
    expect(
        label.compareDocumentPosition(input) & Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
    expect(label.className).not.toContain('col-span-1');
});

test('a stacked field keeps the label above the input on desktop', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(field({ stacked: true }));

    expect(layoutOf(screen.getByLabelText('Title'))).toBe('stacked');
});

test('a hidden label is still there for screen readers and the field takes the full width', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(field({ label: 'Search', htmlFor: 'title', hideLabel: true }));
    const input = screen.getByLabelText('Search');

    expect(screen.getByText('Search').className).toContain('sr-only');
    expect(layoutOf(input)).toBe('stacked');
});

test('an error is a red border and screen-reader text, with no visible text under the input', async () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(field({ error: 'Enter a title.' }));
    const input = screen.getByLabelText('Title');

    await waitFor(() =>
        expect(input.getAttribute('aria-invalid')).toBe('true'),
    );
    expect(screen.getByText('Enter a title.').className).toContain('sr-only');
    // The toast and the focus belong to useValidationErrorToasts, not to the field.
    expect(toast.error).not.toHaveBeenCalled();
});

test('a field whose error is fixed is no longer marked invalid', async () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    const { rerender } = render(field({ error: 'Enter a title.' }));
    const input = screen.getByLabelText('Title');
    await waitFor(() =>
        expect(input.getAttribute('aria-invalid')).toBe('true'),
    );

    rerender(field());

    await waitFor(() => expect(input.hasAttribute('aria-invalid')).toBe(false));
});

test('no error, no error text', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    const { container } = render(field());

    expect(container.querySelector('p')).toBeNull();
});

test('a link beside the label stays on the label row, and the field is stacked even on desktop', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(field({ labelAction: <a href="/forgot">Forgot it?</a> }));
    const input = screen.getByLabelText('Title');
    const action = screen.getByText('Forgot it?');
    const label = screen.getByText('Title');

    expect(layoutOf(input)).toBe('stacked');
    expect(label.parentElement).toBe(action.parentElement);
    expect(
        label.compareDocumentPosition(input) & Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
});
