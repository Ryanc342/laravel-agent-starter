import { render, screen } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import SubmitButton from '@/components/shared/submit-button';

const form: { current?: { processing: boolean } } = {};

vi.mock('@inertiajs/react', () => ({
    useFormContext: () => form.current,
}));

test('it submits its form and can be pressed while the form is not being sent', () => {
    form.current = { processing: false };
    render(<SubmitButton>Save</SubmitButton>);

    const button = screen.getByRole('button', { name: 'Save' });

    expect(button.getAttribute('type')).toBe('submit');
    expect((button as HTMLButtonElement).disabled).toBe(false);
});

test('while the form is being sent it is disabled, busy and shows a spinner', () => {
    form.current = { processing: true };
    render(<SubmitButton>Save</SubmitButton>);

    const button = screen.getByRole('button', { name: /Save/ });

    expect((button as HTMLButtonElement).disabled).toBe(true);
    expect(button.getAttribute('aria-busy')).toBe('true');
    expect(screen.getByRole('status')).toBeTruthy();
});

test('a form that is not an Inertia form passes `sending`', () => {
    form.current = undefined;
    const { rerender } = render(
        <SubmitButton sending={false}>Save</SubmitButton>,
    );

    expect((screen.getByRole('button') as HTMLButtonElement).disabled).toBe(
        false,
    );

    rerender(<SubmitButton sending>Save</SubmitButton>);

    expect((screen.getByRole('button') as HTMLButtonElement).disabled).toBe(
        true,
    );
});

test('outside a form and without `sending` it is a programming error', () => {
    form.current = undefined;
    const silence = vi.spyOn(console, 'error').mockImplementation(() => {});

    expect(() => render(<SubmitButton>Save</SubmitButton>)).toThrow(
        'SubmitButton belongs inside an Inertia <Form>, or is given `sending`.',
    );

    silence.mockRestore();
});
