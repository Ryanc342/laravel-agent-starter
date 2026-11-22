import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import RowActionsDemo from '@/components/style/row-actions-demo';
import { TooltipProvider } from '@/components/ui/tooltip';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));

function demo() {
    render(
        <TooltipProvider>
            <RowActionsDemo
                categories={[{ id: 1, name: 'Travel', expenses: 3 }]}
                nameError="Enter a name for the category."
            />
        </TooltipProvider>,
    );
}

test('Edit in the row menu opens the dialog with the name, and Enter sends it like Save', async () => {
    demo();
    fireEvent.pointerDown(
        screen.getByRole('button', { name: 'More actions' }),
        { button: 0, ctrlKey: false },
    );
    fireEvent.click(await screen.findByRole('menuitem', { name: 'Edit' }));

    const field =
        await screen.findByLabelText<HTMLInputElement>('Category name');
    const save = screen.getByRole<HTMLButtonElement>('button', {
        name: 'Save',
    });

    expect(field.value).toBe('Travel');
    // The browser submits on Enter when the field sits in a form whose
    // submit button is Save (jsdom does not press Enter for us).
    expect(save.type).toBe('submit');
    expect(save.form).toBe(field.form);
    expect(field.required).toBe(true);
    expect(field.form?.noValidate).toBe(true);

    fireEvent.submit(field.form!);

    await waitFor(() => expect(screen.queryByRole('dialog')).toBeNull());
});

test('the state sent back by the server shows the error from the data on the name field', async () => {
    demo();
    fireEvent.click(screen.getByRole('button', { name: 'Edit' }));

    const field = await screen.findByLabelText('Category name');

    expect(field.getAttribute('aria-invalid')).toBe('true');
    expect(screen.getByText('Enter a name for the category.')).toBeTruthy();
});
