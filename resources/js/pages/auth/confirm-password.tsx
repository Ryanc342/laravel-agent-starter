import { Form, Head } from '@inertiajs/react';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import PasswordInput from '@/components/password-input';
import FormField from '@/components/shared/form-field';
import PasskeyVerify from '@/components/users/passkey-verify';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';
import { msg, t } from '@/lib/i18n';

export default function ConfirmPassword() {
    return (
        <>
            <Head title={t('Confirm password')} />

            <PasskeyVerify
                routes={{
                    options: confirmOptions(),
                    submit: confirmStore(),
                }}
                label={t('Confirm with passkey')}
                loadingLabel={t('Confirming...')}
                separator={t('Or confirm with password')}
            />

            <Form noValidate {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <FormField
                            label={t('Password')}
                            htmlFor="password"
                            error={errors.password}
                        >
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder={t('Password')}
                                autoComplete="current-password"
                                autoFocus
                            />
                        </FormField>

                        <div className="flex items-center">
                            <Button
                                className="w-full"
                                disabled={processing}
                                data-test="confirm-password-button"
                            >
                                {processing && <Spinner />}
                                {t('Confirm password')}
                            </Button>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}

ConfirmPassword.layout = {
    title: msg('Confirm password'),
};
