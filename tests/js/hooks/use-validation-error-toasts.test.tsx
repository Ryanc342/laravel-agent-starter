import { render, screen, waitFor } from '@testing-library/react';
import { toast } from 'sonner';
import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
import { useValidationErrorToasts } from '@/hooks/use-validation-error-toasts';

const on = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/react', () => ({ router: { on } }));
vi.mock('sonner', () => ({ toast: { error: vi.fn() } }));

const scrollIntoView = vi.fn();

function Listener() {
    useValidationErrorToasts();

    return null;
}

/** Fail a submit: Inertia fires its global `error` event with the errors. */
function fail(errors: Record<string, string>) {
    const handler = on.mock.calls[0][1] as (event: CustomEvent) => void;

    handler(new CustomEvent('inertia:error', { detail: { errors } }));
}

function setReducedMotion(reduced: boolean) {
    window.matchMedia = vi.fn().mockReturnValue({ matches: reduced });
}

beforeEach(() => {
    on.mockReturnValue(vi.fn());
    Element.prototype.scrollIntoView = scrollIntoView;
    setReducedMotion(false);
});

afterEach(() => {
    on.mockReset();
    vi.mocked(toast.error).mockClear();
    scrollIntoView.mockClear();
});

function form() {
    render(
        <>
            <Listener />
            <input name="title" aria-label="title" />
            <input name="due_on" aria-label="due_on" />
            <input name="notes" aria-label="notes" />
        </>,
    );
}

test('it listens for failed submits and stops listening when it goes away', () => {
    const stop = vi.fn();
    on.mockReturnValue(stop);

    const { unmount } = render(<Listener />);

    expect(on).toHaveBeenCalledWith('error', expect.any(Function));

    unmount();

    expect(stop).toHaveBeenCalledTimes(1);
});

test('every error is its own toast', () => {
    form();

    fail({ title: 'Enter a title.', due_on: 'Enter a date after today.' });

    expect(toast.error).toHaveBeenCalledTimes(2);
    expect(toast.error).toHaveBeenCalledWith('Enter a title.', {
        id: 'field-error-title',
    });
    expect(toast.error).toHaveBeenCalledWith('Enter a date after today.', {
        id: 'field-error-due_on',
    });
});

test('the same error after a second failed submit is announced again', async () => {
    form();

    fail({ title: 'Enter a title.' });
    document.body.focus();
    fail({ title: 'Enter a title.' });

    expect(toast.error).toHaveBeenCalledTimes(2);
    await waitFor(() =>
        expect(document.activeElement).toBe(screen.getByLabelText('title')),
    );
});

test('the first field with an error, in page order, is scrolled into view and focused', async () => {
    form();

    fail({ due_on: 'Second.', title: 'First.' });

    await waitFor(() =>
        expect(document.activeElement).toBe(screen.getByLabelText('title')),
    );
    expect(scrollIntoView).toHaveBeenCalledTimes(1);
    expect(scrollIntoView.mock.contexts[0]).toBe(
        screen.getByLabelText('title'),
    );
    expect(scrollIntoView).toHaveBeenCalledWith({
        behavior: 'smooth',
        block: 'center',
    });
});

test('under reduced motion the page jumps to the field without animation', async () => {
    setReducedMotion(true);
    form();

    fail({ title: 'Enter a title.' });

    await waitFor(() => expect(scrollIntoView).toHaveBeenCalledTimes(1));
    expect(scrollIntoView).toHaveBeenCalledWith({
        behavior: 'auto',
        block: 'center',
    });
});

test('inside an open dialog the field of the dialog is focused, not one of the same name behind it', async () => {
    render(
        <>
            <Listener />
            <input name="password" aria-label="page password" />
            <div role="dialog">
                <input name="password" aria-label="dialog password" />
            </div>
        </>,
    );

    fail({ password: 'Wrong password.' });

    await waitFor(() =>
        expect(document.activeElement).toBe(
            screen.getByLabelText('dialog password'),
        ),
    );
});

test('an error for a field that is not on the page is still a toast and moves nothing', async () => {
    form();

    fail({ unknown: 'Something went wrong.' });
    await Promise.resolve();

    expect(toast.error).toHaveBeenCalledTimes(1);
    expect(scrollIntoView).not.toHaveBeenCalled();
});

test('no errors, no toast', () => {
    form();

    fail({});

    expect(toast.error).not.toHaveBeenCalled();
});
