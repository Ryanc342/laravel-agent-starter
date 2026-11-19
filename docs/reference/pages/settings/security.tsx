import { Form, Head } from '@inertiajs/react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/heading';
import FormField from '@/components/shared/form-field';
import ManagePasskeys from '@/components/users/manage-passkeys';
import type { Props as ManagePasskeysProps } from '@/components/users/manage-passkeys';
import ManageTwoFactor from '@/components/users/manage-two-factor';
import type { Props as ManageTwoFactorProps } from '@/components/users/manage-two-factor';
import NewPasswordFields from '@/components/users/new-password-fields';
import PasswordInput from '@/components/password-input';
import Actions from '@/components/shared/actions';
import { Button } from '@/components/ui/button';
import { edit } from '@/routes/security';
import { msg, t } from '@/lib/i18n';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

export default function Security(props: Props) {
    return (
        <>
            <Head title={t('Security settings')} />

            <h1 className="sr-only">{t('Security settings')}</h1>

            <div className="space-y-6">
                <Heading variant="small" title={t('Update password')} />

                <Form
                    noValidate
                    {...SecurityController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnError={[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]}
                    resetOnSuccess
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <FormField
                                label={t('Current password')}
                                htmlFor="current_password"
                                error={errors.current_password}
                            >
                                <PasswordInput
                                    id="current_password"
                                    name="current_password"
                                    autoComplete="current-password"
                                    placeholder={t('Current password')}
                                />
                            </FormField>

                            <NewPasswordFields
                                passwordRules={props.passwordRules}
                                errors={errors}
                                label={t('New password')}
                            />

                            <Actions>
                                <Button
                                    disabled={processing}
                                    data-test="update-password-button"
                                >
                                    {t('Save')}
                                </Button>
                            </Actions>
                        </>
                    )}
                </Form>
            </div>

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
            title: msg('Security settings'),
            href: edit(),
        },
    ],
};
