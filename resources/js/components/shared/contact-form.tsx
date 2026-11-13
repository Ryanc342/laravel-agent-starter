import FormField from '@/components/shared/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type ContactValues = Partial<
    Record<'name' | 'email' | 'subject' | 'message', string>
>;

/**
 * The fields and the button of a contact form (docs/design/patterns/contact.md):
 * name, email, subject and message as `FormField` rows, and one button. It sits
 * inside the page's own form (Inertia's `<Form>`, which gives `errors` and
 * `processing`). An error gives its field a red border and no text; the toast
 * and the focus come from `useValidationErrorToasts`. `defaultValues` bring
 * back what was typed.
 */
export default function ContactForm({
    errors = {},
    processing = false,
    defaultValues = {},
    submitLabel = 'Send message',
}: {
    errors?: Record<string, string>;
    processing?: boolean;
    defaultValues?: ContactValues;
    submitLabel?: string;
}) {
    return (
        <div className="grid gap-6">
            <FormField label="Name" htmlFor="contact-name" error={errors.name}>
                <Input
                    id="contact-name"
                    name="name"
                    autoComplete="name"
                    defaultValue={defaultValues.name}
                />
            </FormField>
            <FormField
                label="Email"
                htmlFor="contact-email"
                error={errors.email}
            >
                <Input
                    id="contact-email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    defaultValue={defaultValues.email}
                />
            </FormField>
            <FormField
                label="Subject"
                htmlFor="contact-subject"
                error={errors.subject}
            >
                <Input
                    id="contact-subject"
                    name="subject"
                    defaultValue={defaultValues.subject}
                />
            </FormField>
            <FormField
                label="Message"
                htmlFor="contact-message"
                error={errors.message}
            >
                <Textarea
                    id="contact-message"
                    name="message"
                    defaultValue={defaultValues.message}
                />
            </FormField>
            <div>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {submitLabel}
                </Button>
            </div>
        </div>
    );
}
