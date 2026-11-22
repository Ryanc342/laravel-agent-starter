import AppLogoIcon from '@/components/app-logo-icon';
import PasswordField from '@/components/shared/password-field';
import StatusMessage from '@/components/shared/status-message';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import TextLink from '@/components/text-link';
import { style } from '@/routes';

/**
 * A page a signed-out person sees: the narrow centred column of the auth
 * layout. The pages use the real AuthLayout; this frame shows the same column
 * inside the overview page, with the link to the password reset under the
 * main button.
 */
export default function AuthDemo({
    title,
    status,
    submitLabel,
    footer,
    footerLink,
}: {
    title: string;
    /** The line the server sends back with the page, such as after a password reset. */
    status: string;
    submitLabel: string;
    footer: string;
    footerLink: string;
}) {
    return (
        <div className="mx-auto flex w-full max-w-sm flex-col gap-8">
            <div className="flex flex-col items-center gap-4">
                <AppLogoIcon className="size-9 fill-current text-foreground" />
                <h1 className="text-xl font-medium">{title}</h1>
            </div>

            <StatusMessage message={status} />

            <form
                noValidate
                onSubmit={(event) => event.preventDefault()}
                className="flex flex-col gap-6"
            >
                <TextField label="Email address" type="email" />
                <PasswordField label="Password" />
                <SubmitButton sending={false} className="mt-2 w-full">
                    {submitLabel}
                </SubmitButton>
                <TextLink
                    href={`${style().url}#auth`}
                    className="mx-auto text-sm"
                >
                    Forgot your password?
                </TextLink>
            </form>

            <div className="text-center text-sm text-muted-foreground">
                {footer}{' '}
                <TextLink href={`${style().url}#auth`}>{footerLink}</TextLink>
            </div>
        </div>
    );
}
