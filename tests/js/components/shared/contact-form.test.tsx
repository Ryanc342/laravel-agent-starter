import { render, screen } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import ContactForm from '@/components/shared/contact-form';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));

test('name, email, subject and message are labelled fields with the names the server expects', () => {
    render(<ContactForm />);

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

test('one button, named for what happens', () => {
    render(<ContactForm />);

    const buttons = screen.getAllByRole('button');

    expect(buttons).toHaveLength(1);
    expect(buttons[0].textContent).toBe('Send message');
    expect(buttons[0].getAttribute('type')).toBe('submit');
});

test('an error marks its field with a red border and shows no visible text under it', () => {
    render(
        <ContactForm
            errors={{ email: 'Enter an email address like name@example.com.' }}
        />,
    );

    expect(screen.getByLabelText('Email').getAttribute('aria-invalid')).toBe(
        'true',
    );
    expect(
        screen.getByLabelText('Name').getAttribute('aria-invalid'),
    ).toBeNull();
    expect(
        screen.getByText('Enter an email address like name@example.com.')
            .className,
    ).toContain('sr-only');
});

test('while sending the button is disabled and shows a spinner', () => {
    render(<ContactForm processing />);

    expect((screen.getByRole('button') as HTMLButtonElement).disabled).toBe(
        true,
    );
    expect(screen.getByRole('status', { name: 'Loading' })).toBeTruthy();
});

test('typed values come back after a failed send', () => {
    render(
        <ContactForm defaultValues={{ name: '陈美玲', subject: 'Order 12' }} />,
    );

    expect((screen.getByLabelText('Name') as HTMLInputElement).value).toBe(
        '陈美玲',
    );
    expect((screen.getByLabelText('Subject') as HTMLInputElement).value).toBe(
        'Order 12',
    );
});
