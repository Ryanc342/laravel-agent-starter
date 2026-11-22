import { Head } from '@inertiajs/react';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import StackedForm from '@/components/shared/stacked-form';
import NewPasswordFields from '@/components/users/new-password-fields';
import { update } from '@/routes/password';

type Props = {
    token: string;
    email: string;
    passwordRules: string;
};

export default function ResetPassword({ token, email, passwordRules }: Props) {
    return (
        <>
            <Head title="Reset password" />

            <StackedForm
                wideButtons
                {...update.form()}
                transform={(data) => ({ ...data, token, email })}
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ errors }) => (
                    <>
                        <TextField
                            label="Email"
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="email"
                            value={email}
                            readOnly
                            error={errors.email}
                        />

                        <NewPasswordFields
                            passwordRules={passwordRules}
                            errors={errors}
                            autoFocus
                        />

                        <SubmitButton>Reset</SubmitButton>
                    </>
                )}
            </StackedForm>
        </>
    );
}

ResetPassword.layout = {
    title: 'Reset password',
};
