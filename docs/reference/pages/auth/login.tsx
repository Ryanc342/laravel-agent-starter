import { Form, Head } from '@inertiajs/react';
import PasskeyVerify from '@/components/users/passkey-verify';
import PasswordInput from '@/components/password-input';
import FormField from '@/components/shared/form-field';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { msg, t } from '@/lib/i18n';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title={t('Log in')} />

            <PasskeyVerify />

            <Form
                noValidate
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <FormField
                                label={t('Email address')}
                                htmlFor="email"
                                error={errors.email}
                            >
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder={t('email@example.com')}
                                />
                            </FormField>

                            <FormField
                                label={t('Password')}
                                htmlFor="password"
                                error={errors.password}
                                labelAction={
                                    canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="text-sm"
                                            tabIndex={5}
                                        >
                                            {t('Forgot your password?')}
                                        </TextLink>
                                    )
                                }
                            >
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder={t('Password')}
                                />
                            </FormField>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">
                                    {t('Remember me')}
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                {t('Log in')}
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            {t("Don't have an account?")}{' '}
                            <TextLink href={register()} tabIndex={5}>
                                {t('Sign up')}
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-success">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: msg('Log in to your account'),
};
