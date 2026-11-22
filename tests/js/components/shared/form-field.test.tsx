import { render, screen, waitFor } from '@testing-library/react';
import { toast } from 'sonner';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import FormField from '@/components/shared/form-field';
import TextField from '@/components/shared/text-field';

vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));

afterEach(() => {
    vi.mocked(toast.error).mockClear();
});

function field(props: Partial<Parameters<typeof FormField>[0]> = {}) {
    return (
        <FormField label="Code" htmlFor="code" {...props}>
            <input id="code" name="code" />
        </FormField>
    );
}

test('a field without a box has its label above the control', () => {
    render(field());
    const input = screen.getByLabelText('Code');
    const label = screen.getByText('Code');

    expect(
        label.compareDocumentPosition(input) & Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
});

test('a hidden label is still there for screen readers', () => {
    render(field({ hideLabel: true }));

    expect(screen.getByLabelText('Code')).toBeTruthy();
});

test('an error is a red border and screen-reader text, with no toast from the field', async () => {
    render(field({ error: 'Enter the code.' }));
    const input = screen.getByLabelText('Code');

    await waitFor(() =>
        expect(input.getAttribute('aria-invalid')).toBe('true'),
    );
    // The toast and the focus belong to useValidationErrorToasts, not to the field.
    expect(toast.error).not.toHaveBeenCalled();
});

test('a field whose error is fixed is no longer marked invalid', async () => {
    const { rerender } = render(field({ error: 'Enter the code.' }));
    const input = screen.getByLabelText('Code');
    await waitFor(() =>
        expect(input.getAttribute('aria-invalid')).toBe('true'),
    );

    rerender(field());

    await waitFor(() => expect(input.hasAttribute('aria-invalid')).toBe(false));
});

test('a boxed field has its label inside the box, after the input, and no placeholder text', () => {
    render(<TextField label="Title" />);
    const input = screen.getByLabelText('Title');
    const label = screen.getByText('Title');

    expect(
        input.compareDocumentPosition(label) & Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
    expect(input.getAttribute('placeholder')).toBe(' ');
    expect(input.classList.contains('peer')).toBe(true);
});

test('a boxed field with an error has a red border and the message for screen readers only', () => {
    render(<TextField label="Title" error="Enter a title." />);

    expect(screen.getByLabelText('Title').getAttribute('aria-invalid')).toBe(
        'true',
    );
    expect(
        screen.getByText('Enter a title.').classList.contains('sr-only'),
    ).toBe(true);
});
