import PasswordInput from '@/components/password-input';
import FormField from '@/components/shared/form-field';
import { t } from '@/lib/i18n';

/**
 * A new password and its confirmation, as the register, reset-password and
 * security pages ask for them. The browser gets the server's password rules
 * (passwordRules) for its password generator.
 */
export default function NewPasswordFields({
    passwordRules,
    errors,
    label = t('Password'),
    autoFocus = false,
}: {
    passwordRules: string;
    errors: { password?: string; password_confirmation?: string };
    label?: string;
    autoFocus?: boolean;
}) {
    return (
        <>
            <FormField label={label} htmlFor="password" error={errors.password}>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autoFocus={autoFocus}
                    autoComplete="new-password"
                    placeholder={label}
                    passwordrules={passwordRules}
                />
            </FormField>

            <FormField
                label={t('Confirm password')}
                htmlFor="password_confirmation"
                error={errors.password_confirmation}
            >
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autoComplete="new-password"
                    placeholder={t('Confirm password')}
                    passwordrules={passwordRules}
                />
            </FormField>
        </>
    );
}
