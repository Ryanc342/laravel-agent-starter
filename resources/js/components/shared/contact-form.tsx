import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import TextareaField from '@/components/shared/textarea-field';

type ContactValues = Partial<
    Record<'name' | 'email' | 'subject' | 'message', string>
>;

/**
 * The fields and the button of a contact form: name, email, subject and
 * message, then Send. Use it on a contact page, inside the page's Inertia
 * `<Form>`, which gives `errors`. `defaultValues` bring back what was typed.
 * `sending` is only for a form that is not an Inertia `<Form>` (SubmitButton).
 */
export default function ContactForm({
    errors = {},
    defaultValues = {},
    sending,
}: {
    errors?: Record<string, string>;
    defaultValues?: ContactValues;
    sending?: boolean;
}) {
    return (
        <div className="grid gap-6">
            <TextField
                label="Name"
                name="name"
                autoComplete="name"
                defaultValue={defaultValues.name}
                error={errors.name}
            />
            <TextField
                label="Email"
                name="email"
                type="email"
                autoComplete="email"
                defaultValue={defaultValues.email}
                error={errors.email}
            />
            <TextField
                label="Subject"
                name="subject"
                defaultValue={defaultValues.subject}
                error={errors.subject}
            />
            <TextareaField
                label="Message"
                name="message"
                defaultValue={defaultValues.message}
                error={errors.message}
            />
            <div>
                <SubmitButton sending={sending}>Send</SubmitButton>
            </div>
        </div>
    );
}
