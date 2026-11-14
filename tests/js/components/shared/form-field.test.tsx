import { render, screen, waitFor } from '@testing-library/react';
import { toast } from 'sonner';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import FormField from '@/components/shared/form-field';

vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));

afterEach(() => {
    vi.mocked(toast.error).mockClear();
});

function field(props: Partial<Parameters<typeof FormField>[0]> = {}) {
    return (
        <FormField label="Title" htmlFor="title" {...props}>
            <input id="title" name="title" />
        </FormField>
    );
}

test('the label names the input and comes before it', () => {
    render(field());
    const input = screen.getByLabelText('Title');
    const label = screen.getByText('Title');

    expect(
        label.compareDocumentPosition(input) & Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
});

test('a hidden label is still there for screen readers', () => {
    render(field({ label: 'Search', htmlFor: 'title', hideLabel: true }));

    expect(screen.getByLabelText('Search')).toBeTruthy();
});

test('an error is a red border and screen-reader text, with no visible text under the input', async () => {
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
    const { rerender } = render(field({ error: 'Enter a title.' }));
    const input = screen.getByLabelText('Title');
    await waitFor(() =>
        expect(input.getAttribute('aria-invalid')).toBe('true'),
    );

    rerender(field());

    await waitFor(() => expect(input.hasAttribute('aria-invalid')).toBe(false));
});

test('no error, no error text', () => {
    const { container } = render(field());

    expect(container.querySelector('p')).toBeNull();
});

test('a link beside the label stays on the label row, above the input', () => {
    render(field({ labelAction: <a href="/forgot">Forgot it?</a> }));
    const input = screen.getByLabelText('Title');
    const action = screen.getByText('Forgot it?');
    const label = screen.getByText('Title');

    expect(label.parentElement).toBe(action.parentElement);
    expect(
        label.compareDocumentPosition(input) & Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
});
