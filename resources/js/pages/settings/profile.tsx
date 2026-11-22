import { Head } from '@inertiajs/react';
import Actions from '@/components/shared/actions';
import Section from '@/components/shared/section';
import StackedForm from '@/components/shared/stacked-form';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import DeleteUser from '@/components/users/delete-user';
import EmailVerificationNotice from '@/components/users/email-verification-notice';
import { edit, update } from '@/routes/profile';

export default function Profile({
    user,
    emailNeedsVerification,
    verificationLinkSent,
    can,
}: {
    user: App.Http.Resources.UserResource;
    emailNeedsVerification: boolean;
    verificationLinkSent: boolean;
    can: {
        delete_account: boolean;
    };
}) {
    return (
        <>
            <Head title="Profile settings" />

            <Section title="Profile">
                <StackedForm
                    {...update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                >
                    {({ errors }) => (
                        <>
                            <TextField
                                label="Name"
                                id="name"
                                defaultValue={user.name}
                                name="name"
                                required
                                autoComplete="name"
                                error={errors.name}
                            />

                            <TextField
                                label="Email address"
                                id="email"
                                type="email"
                                defaultValue={user.email}
                                name="email"
                                required
                                autoComplete="username"
                                error={errors.email}
                            />

                            {emailNeedsVerification && (
                                <EmailVerificationNotice
                                    linkSent={verificationLinkSent}
                                />
                            )}

                            <Actions>
                                <SubmitButton>Save</SubmitButton>
                            </Actions>
                        </>
                    )}
                </StackedForm>
            </Section>

            {can.delete_account && <DeleteUser />}
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
