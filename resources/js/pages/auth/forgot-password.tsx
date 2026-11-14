// Components
import { Form, Head } from '@inertiajs/react';
import FormField from '@/components/shared/form-field';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { msg, t } from '@/lib/i18n';

export default function ForgotPassword({ status }: { status?: string }) {
    // The link stays a link inside the sentence: split it at its placeholder.
    const returnSentence = t('Or, return to :link', {
        link: '\u0001',
    }).split('\u0001');

    return (
        <>
            <Head title={t('Forgot password')} />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-success">
                    {status}
                </div>
            )}

            <div className="space-y-6">
                <Form noValidate {...email.form()}>
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                label={t('Email address')}
                                htmlFor="email"
                                error={errors.email}
                            >
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="off"
                                    autoFocus
                                    placeholder={t('email@example.com')}
                                />
                            </FormField>

                            <div className="my-6 flex items-center justify-start">
                                <Button
                                    className="w-full"
                                    disabled={processing}
                                    data-test="email-password-reset-link-button"
                                >
                                    {processing && <Spinner />}
                                    {t('Email password reset link')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>{returnSentence[0].trimEnd()}</span>
                    <TextLink href={login()}>{t('log in')}</TextLink>
                    {returnSentence[1]?.trim() && (
                        <span>{returnSentence[1].trim()}</span>
                    )}
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: msg('Forgot password'),
};
