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
function fail(errors: Record<string, unknown>) {
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

test("errors in an error bag (Fortify's two-factor confirmation) are flattened: a toast per message and the field named inside the bag is focused", async () => {
    render(
        <>
            <Listener />
            <input name="code" aria-label="code" />
        </>,
    );

    fail({ confirmTwoFactorAuthentication: { code: 'The code is invalid.' } });

    expect(toast.error).toHaveBeenCalledTimes(1);
    expect(toast.error).toHaveBeenCalledWith('The code is invalid.', {
        id: 'field-error-code',
    });
    await waitFor(() =>
        expect(document.activeElement).toBe(screen.getByLabelText('code')),
    );
});

test('a value that is not a message is never given to the toast', () => {
    form();

    fail({ title: ['Enter a title.'], notes: null, due_on: 42 });

    expect(toast.error).not.toHaveBeenCalled();
});

test('five errors are five toasts, with no line for the rest', () => {
    form();

    fail({
        'rows.2.due_on': 'Row 2: Due date is not a date.',
        'rows.3.title': 'Row 3: Title is required.',
        'rows.4.due_on': 'Row 4: Due date is not a date.',
        'rows.5.due_on': 'Row 5: Due date is not a date.',
        'rows.6.due_on': 'Row 6: Due date is not a date.',
    });

    expect(toast.error).toHaveBeenCalledTimes(5);
    expect(toast.error).toHaveBeenCalledWith('Row 3: Title is required.', {
        id: 'field-error-rows.3.title',
    });
});

test('a burst of errors shows five toasts and then one "And N more errors"', () => {
    form();

    fail({
        'rows.2.due_on': 'Row 2: Due date is not a date.',
        'rows.3.title': 'Row 3: Title is required.',
        'rows.4.due_on': 'Row 4: Due date is not a date.',
        'rows.5.due_on': 'Row 5: Due date is not a date.',
        'rows.6.due_on': 'Row 6: Due date is not a date.',
        'rows.7.due_on': 'Row 7: Due date is not a date.',
        'rows.8.title': 'Row 8: Title is required.',
    });

    expect(toast.error).toHaveBeenCalledTimes(6);
    expect(toast.error).not.toHaveBeenCalledWith(
        'Row 7: Due date is not a date.',
        expect.anything(),
    );
    expect(toast.error).toHaveBeenLastCalledWith('And 2 more errors.', {
        id: 'field-error-more',
    });
});

test('one error over the five reads "And 1 more error."', () => {
    form();

    fail(
        Object.fromEntries(
            [1, 2, 3, 4, 5, 6].map((row) => [
                `rows.${row}.title`,
                `Row ${row}`,
            ]),
        ),
    );

    expect(toast.error).toHaveBeenLastCalledWith('And 1 more error.', {
        id: 'field-error-more',
    });
});

test('a file error focuses the file field of the open dialog', async () => {
    render(
        <>
            <Listener />
            <div role="dialog">
                <input type="file" name="file" aria-label="CSV file" />
            </div>
        </>,
    );

    fail({ file: 'The file must be a CSV file.' });

    await waitFor(() =>
        expect(document.activeElement).toBe(screen.getByLabelText('CSV file')),
    );
});

test('an error for items.0.quantity focuses the input named items[0][quantity]', async () => {
    render(
        <>
            <Listener />
            <input name="items[0][quantity]" aria-label="first quantity" />
            <input name="items[1][quantity]" aria-label="second quantity" />
        </>,
    );

    fail({ 'items.1.quantity': 'Enter a quantity of 1 or more.' });

    expect(toast.error).toHaveBeenCalledWith('Enter a quantity of 1 or more.', {
        id: 'field-error-items.1.quantity',
    });
    await waitFor(() =>
        expect(document.activeElement).toBe(
            screen.getByLabelText('second quantity'),
        ),
    );
    expect(scrollIntoView.mock.contexts[0]).toBe(
        screen.getByLabelText('second quantity'),
    );
});

test('a key with more than two parts finds its bracket name, and the first field in page order wins', async () => {
    render(
        <>
            <Listener />
            <input name="lines[0][tax][rate]" aria-label="tax rate" />
            <input name="lines.0.name" aria-label="dotted name" />
        </>,
    );

    fail({ 'lines.0.name': 'Second.', 'lines.0.tax.rate': 'First.' });

    await waitFor(() =>
        expect(document.activeElement).toBe(screen.getByLabelText('tax rate')),
    );
});

test('an input named with dots still matches the dotted key', async () => {
    render(
        <>
            <Listener />
            <input name="items.0.quantity" aria-label="dotted quantity" />
        </>,
    );

    fail({ 'items.0.quantity': 'Enter a quantity.' });

    await waitFor(() =>
        expect(document.activeElement).toBe(
            screen.getByLabelText('dotted quantity'),
        ),
    );
});

test('a key with no dots is looked up as it is, and the wrong index finds nothing', async () => {
    render(
        <>
            <Listener />
            <input name="items[0][quantity]" aria-label="first quantity" />
        </>,
    );

    fail({ 'items.5.quantity': 'Enter a quantity.' });
    await Promise.resolve();

    expect(scrollIntoView).not.toHaveBeenCalled();
});
