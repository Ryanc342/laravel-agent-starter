import { Head } from '@inertiajs/react';
import StatusMessage from '@/components/shared/status-message';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import StackedForm from '@/components/shared/stacked-form';
import AuthPrompt from '@/components/users/auth-prompt';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <>
            <Head title="Forgot password" />

            <StatusMessage message={status} />

            <StackedForm wideButtons {...email.form()}>
                {({ errors }) => (
                    <>
                        <TextField
                            label="Email address"
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="off"
                            autoFocus
                            error={errors.email}
                        />

                        <SubmitButton>Send</SubmitButton>

                        <AuthPrompt
                            text="Or, return to"
                            link="log in"
                            href={login()}
                        />
                    </>
                )}
            </StackedForm>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Forgot password',
};
