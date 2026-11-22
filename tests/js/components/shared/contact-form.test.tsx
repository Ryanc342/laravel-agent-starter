import { render, screen } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import ContactForm from '@/components/shared/contact-form';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));
vi.mock('@inertiajs/react', () => ({ useFormContext: () => undefined }));

test('name, email, subject and message are labelled fields with the names the server expects', () => {
    render(<ContactForm sending={false} />);

    expect(screen.getByLabelText('Name').getAttribute('name')).toBe('name');
    expect(screen.getByLabelText('Email').getAttribute('name')).toBe('email');
    expect(screen.getByLabelText('Email').getAttribute('type')).toBe('email');
    expect(screen.getByLabelText('Subject').getAttribute('name')).toBe(
        'subject',
    );

    const message = screen.getByLabelText('Message');

    expect(message.tagName).toBe('TEXTAREA');
    expect(message.getAttribute('name')).toBe('message');
});

test('one button, a verb, that sends the form', () => {
    render(<ContactForm sending={false} />);

    const buttons = screen.getAllByRole('button');

    expect(buttons).toHaveLength(1);
    expect(buttons[0].textContent).toBe('Send');
    expect(buttons[0].getAttribute('type')).toBe('submit');
});

test('an error marks its field with a red border and shows no visible text under it', () => {
    render(
        <ContactForm
            sending={false}
            errors={{ email: 'Enter an email address like name@example.com.' }}
        />,
    );

    expect(screen.getByLabelText('Email').getAttribute('aria-invalid')).toBe(
        'true',
    );
    expect(
        screen.getByLabelText('Name').getAttribute('aria-invalid'),
    ).toBeNull();
});

test('while sending the button is disabled and shows a spinner', () => {
    render(<ContactForm sending />);

    expect((screen.getByRole('button') as HTMLButtonElement).disabled).toBe(
        true,
    );
    expect(screen.getByRole('status', { name: 'Loading' })).toBeTruthy();
});

test('typed values come back after a failed send', () => {
    render(
        <ContactForm
            sending={false}
            defaultValues={{ name: 'Tan Mei Ling', subject: 'Order 12' }}
        />,
    );

    expect((screen.getByLabelText('Name') as HTMLInputElement).value).toBe(
        'Tan Mei Ling',
    );
    expect((screen.getByLabelText('Subject') as HTMLInputElement).value).toBe(
        'Order 12',
    );
});
