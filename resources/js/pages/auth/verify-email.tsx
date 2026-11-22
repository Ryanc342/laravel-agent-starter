import { Head } from '@inertiajs/react';
import StatusMessage from '@/components/shared/status-message';
import SubmitButton from '@/components/shared/submit-button';
import StackedForm from '@/components/shared/stacked-form';
import AuthPrompt from '@/components/users/auth-prompt';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({
    verificationLinkSent,
}: {
    verificationLinkSent: boolean;
}) {
    return (
        <>
            <Head title="Email verification" />

            <StatusMessage
                message={
                    verificationLinkSent
                        ? 'A new verification link has been sent to the email address you provided during registration.'
                        : undefined
                }
            />

            <StackedForm wideButtons {...send.form()}>
                <SubmitButton>Resend</SubmitButton>
                <AuthPrompt link="Log out" href={logout()} />
            </StackedForm>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Email verification',
};
