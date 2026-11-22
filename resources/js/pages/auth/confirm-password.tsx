import { Head } from '@inertiajs/react';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import PasswordField from '@/components/shared/password-field';
import SubmitButton from '@/components/shared/submit-button';
import StackedForm from '@/components/shared/stacked-form';
import PasskeyVerify from '@/components/users/passkey-verify';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    return (
        <>
            <Head title="Confirm password" />

            <PasskeyVerify
                routes={{
                    options: confirmOptions(),
                    submit: confirmStore(),
                }}
                label="Confirm with passkey"
                loadingLabel="Confirming..."
                separator="Or confirm with password"
            />

            <StackedForm
                wideButtons
                {...store.form()}
                resetOnSuccess={['password']}
            >
                {({ errors }) => (
                    <>
                        <PasswordField
                            label="Password"
                            id="password"
                            name="password"
                            autoComplete="current-password"
                            autoFocus
                            error={errors.password}
                        />

                        <SubmitButton>Confirm</SubmitButton>
                    </>
                )}
            </StackedForm>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'Confirm password',
};
