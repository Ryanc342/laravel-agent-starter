import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import RowActionsDemo from '@/components/style/row-actions-demo';
import { TooltipProvider } from '@/components/ui/tooltip';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));

function openEdit(answer: (name: string) => string | null) {
    render(
        <TooltipProvider>
            <RowActionsDemo
                categories={[{ id: 1, name: 'Travel', expenses: 3 }]}
                answer={answer}
            />
        </TooltipProvider>,
    );

    fireEvent.pointerDown(
        screen.getByRole('button', { name: 'More actions' }),
        {
            button: 0,
            ctrlKey: false,
        },
    );
}

test('Enter in the name field saves, like the Save button', async () => {
    openEdit(() => null);
    fireEvent.click(await screen.findByRole('menuitem', { name: 'Edit' }));

    const field =
        await screen.findByLabelText<HTMLInputElement>('Category name');
    const save = screen.getByRole<HTMLButtonElement>('button', {
        name: 'Save',
    });

    // The browser submits on Enter when the field sits in a form whose
    // submit button is Save (jsdom does not press Enter for us).
    expect(save.type).toBe('submit');
    expect(save.form).not.toBeNull();
    expect(save.form).toBe(field.form);
    // Announced as required; `noValidate` leaves the check to `answer`.
    expect(field.required).toBe(true);
    expect(field.form?.noValidate).toBe(true);

    fireEvent.change(field, { target: { value: 'Trips' } });
    fireEvent.submit(field.form!);

    await waitFor(() => expect(screen.queryByRole('dialog')).toBeNull());
    expect(screen.getByText('Trips')).toBeTruthy();
});

test('a refused name focuses the name field, as a failed submit does', async () => {
    render(
        <TooltipProvider>
            <RowActionsDemo
                categories={[{ id: 1, name: 'Travel', expenses: 3 }]}
                answer={() => 'The name field is required.'}
            />
        </TooltipProvider>,
    );

    fireEvent.pointerDown(
        screen.getByRole('button', { name: 'More actions' }),
        {
            button: 0,
            ctrlKey: false,
        },
    );
    fireEvent.click(await screen.findByRole('menuitem', { name: 'Edit' }));

    const field = await screen.findByLabelText('Category name');
    fireEvent.change(field, { target: { value: '' } });
    // Focus sits on Save when it is clicked, as with a pointer or the keyboard.
    const save = screen.getByRole('button', { name: 'Save' });
    save.focus();
    fireEvent.click(save);

    await waitFor(() => expect(document.activeElement).toBe(field));
});
