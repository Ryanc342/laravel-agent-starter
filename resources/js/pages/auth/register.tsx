import { Head } from '@inertiajs/react';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import StackedForm from '@/components/shared/stacked-form';
import AuthPrompt from '@/components/users/auth-prompt';
import NewPasswordFields from '@/components/users/new-password-fields';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
};

export default function Register({ passwordRules }: Props) {
    return (
        <>
            <Head title="Register" />
            <StackedForm
                wideButtons
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
            >
                {({ errors }) => (
                    <>
                        <TextField
                            label="Name"
                            id="name"
                            type="text"
                            required
                            autoFocus
                            autoComplete="name"
                            name="name"
                            error={errors.name}
                        />

                        <TextField
                            label="Email address"
                            id="email"
                            type="email"
                            required
                            autoComplete="email"
                            name="email"
                            error={errors.email}
                        />

                        <NewPasswordFields
                            passwordRules={passwordRules}
                            errors={errors}
                        />

                        <SubmitButton>Create</SubmitButton>

                        <AuthPrompt
                            text="Already have an account?"
                            link="Log in"
                            href={login()}
                        />
                    </>
                )}
            </StackedForm>
        </>
    );
}

Register.layout = {
    title: 'Create an account',
};
