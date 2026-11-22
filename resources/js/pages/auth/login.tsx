import { Head } from '@inertiajs/react';
import CheckboxField from '@/components/shared/checkbox-field';
import PasswordField from '@/components/shared/password-field';
import StatusMessage from '@/components/shared/status-message';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import StackedForm from '@/components/shared/stacked-form';
import AuthPrompt from '@/components/users/auth-prompt';
import PasskeyVerify from '@/components/users/passkey-verify';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Log in" />

            <StatusMessage message={status} />

            <PasskeyVerify />

            <StackedForm
                wideButtons
                {...store.form()}
                resetOnSuccess={['password']}
            >
                {({ errors }) => (
                    <>
                        <TextField
                            label="Email address"
                            id="email"
                            type="email"
                            name="email"
                            required
                            autoFocus
                            autoComplete="email"
                            error={errors.email}
                        />

                        <PasswordField
                            label="Password"
                            id="password"
                            name="password"
                            required
                            autoComplete="current-password"
                            error={errors.password}
                        />

                        <CheckboxField label="Remember me" name="remember" />

                        <SubmitButton>Log in</SubmitButton>

                        {canResetPassword && (
                            <AuthPrompt
                                link="Forgot your password?"
                                href={request()}
                            />
                        )}

                        <AuthPrompt
                            text="Don't have an account?"
                            link="Sign up"
                            href={register()}
                        />
                    </>
                )}
            </StackedForm>
        </>
    );
}

Login.layout = {
    title: 'Log in to your account',
};
