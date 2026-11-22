import { useFormContext } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

/**
 * The only button that sends a form. Use it for every form's main button.
 * While the form is being sent it is disabled and shows a spinner, so a
 * request is never sent twice. Inside an Inertia `<Form>` it reads the form's
 * state itself. A form that sends another way (the browser's passkey prompt)
 * passes `sending`. With neither it throws.
 */
export default function SubmitButton({
    children,
    disabled = false,
    sending,
    ...props
}: Omit<ComponentProps<typeof Button>, 'type'> & {
    sending?: boolean;
}) {
    const form = useFormContext();
    const busy = sending ?? form?.processing;

    if (busy === undefined) {
        throw new Error(
            'SubmitButton belongs inside an Inertia <Form>, or is given `sending`.',
        );
    }

    return (
        <Button
            type="submit"
            disabled={disabled || busy}
            aria-busy={busy}
            {...props}
        >
            {busy && <Spinner />}
            {children}
        </Button>
    );
}
