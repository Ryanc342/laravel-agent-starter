import { render, screen, waitFor } from '@testing-library/react';
import { toast } from 'sonner';
import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
import { useFieldError } from '@/hooks/use-field-error';

vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));

const scrollIntoView = vi.fn();

function setReducedMotion(reduced: boolean) {
    window.matchMedia = vi.fn().mockReturnValue({ matches: reduced });
}

function Field({ id, error }: { id: string; error?: string }) {
    useFieldError(error, id);

    return <input id={id} aria-label={id} />;
}

beforeEach(() => {
    Element.prototype.scrollIntoView = scrollIntoView;
    setReducedMotion(false);
});

afterEach(() => {
    vi.mocked(toast.error).mockClear();
    scrollIntoView.mockClear();
});

test('every error is its own toast, and the field is marked invalid', async () => {
    render(
        <>
            <Field id="title" error="Enter a title." />
            <Field id="due_on" error="Enter a date after today." />
            <Field id="notes" />
        </>,
    );

    await waitFor(() => expect(toast.error).toHaveBeenCalledTimes(2));
    expect(toast.error).toHaveBeenCalledWith('Enter a title.', {
        id: 'field-error-title',
    });
    expect(toast.error).toHaveBeenCalledWith('Enter a date after today.', {
        id: 'field-error-due_on',
    });
    expect(screen.getByLabelText('title').getAttribute('aria-invalid')).toBe(
        'true',
    );
    expect(screen.getByLabelText('notes').hasAttribute('aria-invalid')).toBe(
        false,
    );
});

test('the first field with an error, in page order, is scrolled into view and focused', async () => {
    render(
        <>
            <Field id="first" />
            <Field id="second" error="Second is wrong." />
            <Field id="third" error="Third is wrong." />
        </>,
    );

    await waitFor(() =>
        expect(document.activeElement).toBe(screen.getByLabelText('second')),
    );
    expect(scrollIntoView).toHaveBeenCalledTimes(1);
    expect(scrollIntoView.mock.contexts[0]).toBe(
        screen.getByLabelText('second'),
    );
    expect(scrollIntoView).toHaveBeenCalledWith({
        behavior: 'smooth',
        block: 'center',
    });
});

test('under reduced motion the page jumps to the field without animation', async () => {
    setReducedMotion(true);

    render(<Field id="title" error="Enter a title." />);

    await waitFor(() => expect(scrollIntoView).toHaveBeenCalledTimes(1));
    expect(scrollIntoView).toHaveBeenCalledWith({
        behavior: 'auto',
        block: 'center',
    });
});

test('no error, no toast, no scrolling', async () => {
    render(<Field id="title" />);

    await Promise.resolve();

    expect(toast.error).not.toHaveBeenCalled();
    expect(scrollIntoView).not.toHaveBeenCalled();
    expect(document.activeElement).toBe(document.body);
});

test('a fixed field is no longer marked invalid, and the same error toasts again if it returns', async () => {
    const { rerender } = render(<Field id="title" error="Enter a title." />);
    await waitFor(() => expect(toast.error).toHaveBeenCalledTimes(1));

    rerender(<Field id="title" />);
    expect(screen.getByLabelText('title').hasAttribute('aria-invalid')).toBe(
        false,
    );

    rerender(<Field id="title" error="Enter a title." />);
    await waitFor(() => expect(toast.error).toHaveBeenCalledTimes(2));
});
