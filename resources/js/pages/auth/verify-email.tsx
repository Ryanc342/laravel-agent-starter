// Components
import { Form, Head } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { msg, t } from '@/lib/i18n';

export default function VerifyEmail({
    verificationLinkSent,
}: {
    verificationLinkSent: boolean;
}) {
    return (
        <>
            <Head title={t('Email verification')} />

            {verificationLinkSent && (
                <div className="mb-4 text-center text-sm font-medium text-success">
                    {t(
                        'A new verification link has been sent to the email address you provided during registration.',
                    )}
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            {t('Resend verification email')}
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            {t('Log out')}
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: msg('Email verification'),
};
