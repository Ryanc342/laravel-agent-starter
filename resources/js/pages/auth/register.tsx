import { Form, Head } from '@inertiajs/react';
import FormField from '@/components/shared/form-field';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import NewPasswordFields from '@/components/users/new-password-fields';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { msg, t } from '@/lib/i18n';

type Props = {
    passwordRules: string;
};

export default function Register({ passwordRules }: Props) {
    return (
        <>
            <Head title={t('Register')} />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <FormField
                                label={t('Name')}
                                htmlFor="name"
                                error={errors.name}
                            >
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    autoComplete="name"
                                    name="name"
                                    placeholder={t('Full name')}
                                />
                            </FormField>

                            <FormField
                                label={t('Email address')}
                                htmlFor="email"
                                error={errors.email}
                            >
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    name="email"
                                    placeholder={t('email@example.com')}
                                />
                            </FormField>

                            <NewPasswordFields
                                passwordRules={passwordRules}
                                errors={errors}
                            />

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                {t('Create account')}
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            {t('Already have an account?')}{' '}
                            <TextLink href={login()}>{t('Log in')}</TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: msg('Create an account'),
};
