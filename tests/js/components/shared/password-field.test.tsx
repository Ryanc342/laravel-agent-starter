import { fireEvent, render, screen } from '@testing-library/react';
import { expect, test } from 'vite-plus/test';
import PasswordField from '@/components/shared/password-field';
import { TooltipProvider } from '@/components/ui/tooltip';

function field() {
    render(
        <TooltipProvider>
            <PasswordField label="Password" name="password" />
        </TooltipProvider>,
    );

    return screen.getByLabelText('Password') as HTMLInputElement;
}

test('what is typed is hidden until the user shows it, and hidden again', () => {
    const input = field();

    expect(input.type).toBe('password');

    fireEvent.click(screen.getByRole('button', { name: 'Show password' }));
    expect(input.type).toBe('text');

    fireEvent.click(screen.getByRole('button', { name: 'Hide password' }));
    expect(input.type).toBe('password');
});

test('the show button never sends the form', () => {
    field();

    expect(
        screen
            .getByRole('button', { name: 'Show password' })
            .getAttribute('type'),
    ).toBe('button');
});
