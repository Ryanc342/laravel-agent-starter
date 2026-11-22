import { fireEvent, render, screen, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import ConfirmButton from '@/components/shared/confirm-button';

const sent: string[] = [];

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('@inertiajs/react', () => ({
    Form: ({
        action,
        method,
        children,
    }: {
        action: string;
        method: string;
        children:
            | ReactNode
            | ((slot: {
                  errors: Record<string, string>;
                  resetAndClearErrors: () => void;
              }) => ReactNode);
    }) => (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                sent.push(`${method} ${action}`);
            }}
        >
            {typeof children === 'function'
                ? children({
                      errors: { password: 'The password is incorrect.' },
                      resetAndClearErrors: () => undefined,
                  })
                : children}
        </form>
    ),
    useFormContext: () => ({ processing: false }),
}));

beforeEach(() => {
    sent.length = 0;
});

function deleteButton() {
    render(
        <ConfirmButton
            form={{ action: '/items/1', method: 'delete' }}
            title="Delete this item?"
            consequence="“Report” will be deleted. This cannot be undone."
        >
            Delete
        </ConfirmButton>,
    );
}

test('pressing the button only opens the confirmation: nothing is sent yet', () => {
    deleteButton();

    fireEvent.click(screen.getByRole('button', { name: 'Delete' }));

    expect(screen.getByRole('dialog')).toBeTruthy();
    expect(screen.getByText('Delete this item?')).toBeTruthy();
    expect(
        screen.getByText('“Report” will be deleted. This cannot be undone.'),
    ).toBeTruthy();
    expect(sent).toEqual([]);
});

test('only the confirm button sends the request', () => {
    deleteButton();

    fireEvent.click(screen.getByRole('button', { name: 'Delete' }));
    const dialog = screen.getByRole('dialog');
    fireEvent.click(within(dialog).getByRole('button', { name: 'Delete' }));

    expect(sent).toEqual(['delete /items/1']);
});

test('Cancel closes the confirmation without sending anything', () => {
    deleteButton();

    fireEvent.click(screen.getByRole('button', { name: 'Delete' }));
    fireEvent.click(screen.getByRole('button', { name: 'Cancel' }));

    expect(screen.queryByRole('dialog')).toBeNull();
    expect(sent).toEqual([]);
});

test('the fields an action needs are in the dialog and get the errors', () => {
    render(
        <ConfirmButton
            form={{ action: '/account', method: 'post' }}
            title="Delete your account?"
            consequence="Your account will be deleted."
            fields={(errors) => (
                <input
                    aria-label="Password"
                    aria-invalid={Boolean(errors.password)}
                />
            )}
        >
            Delete
        </ConfirmButton>,
    );

    fireEvent.click(screen.getByRole('button', { name: 'Delete' }));

    expect(
        within(screen.getByRole('dialog'))
            .getByLabelText('Password')
            .getAttribute('aria-invalid'),
    ).toBe('true');
});
