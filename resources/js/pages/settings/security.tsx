import { Head } from '@inertiajs/react';
import ManagePasskeys from '@/components/users/manage-passkeys';
import type { Props as ManagePasskeysProps } from '@/components/users/manage-passkeys';
import ManageTwoFactor from '@/components/users/manage-two-factor';
import type { Props as ManageTwoFactorProps } from '@/components/users/manage-two-factor';
import NewPasswordFields from '@/components/users/new-password-fields';
import Actions from '@/components/shared/actions';
import PasswordField from '@/components/shared/password-field';
import Section from '@/components/shared/section';
import StackedForm from '@/components/shared/stacked-form';
import SubmitButton from '@/components/shared/submit-button';
import { edit } from '@/routes/security';
import { update } from '@/routes/user-password';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

export default function Security(props: Props) {
    return (
        <>
            <Head title="Security settings" />

            <Section title="Update password">
                <StackedForm
                    {...update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnError={[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]}
                    resetOnSuccess
                >
                    {({ errors }) => (
                        <>
                            <PasswordField
                                label="Current password"
                                id="current_password"
                                name="current_password"
                                autoComplete="current-password"
                                error={errors.current_password}
                            />

                            <NewPasswordFields
                                passwordRules={props.passwordRules}
                                errors={errors}
                                label="New password"
                            />

                            <Actions>
                                <SubmitButton>Save</SubmitButton>
                            </Actions>
                        </>
                    )}
                </StackedForm>
            </Section>

            <ManageTwoFactor
                canManageTwoFactor={props.canManageTwoFactor}
                requiresConfirmation={props.requiresConfirmation}
                twoFactorEnabled={props.twoFactorEnabled}
            />

            <ManagePasskeys
                canManagePasskeys={props.canManagePasskeys}
                passkeys={props.passkeys}
            />
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Security settings',
            href: edit(),
        },
    ],
};
