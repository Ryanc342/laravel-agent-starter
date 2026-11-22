import PasswordField from '@/components/shared/password-field';

/**
 * A new password and its confirmation, as the register, reset-password and
 * security pages ask for them. The browser gets the server's password rules
 * (passwordRules) for its password generator.
 */
export default function NewPasswordFields({
    passwordRules,
    errors,
    label = 'Password',
    autoFocus = false,
}: {
    passwordRules: string;
    errors: { password?: string; password_confirmation?: string };
    label?: string;
    autoFocus?: boolean;
}) {
    return (
        <>
            <PasswordField
                label={label}
                id="password"
                name="password"
                required
                autoFocus={autoFocus}
                autoComplete="new-password"
                passwordrules={passwordRules}
                error={errors.password}
            />
            <PasswordField
                label="Confirm password"
                id="password_confirmation"
                name="password_confirmation"
                required
                autoComplete="new-password"
                passwordrules={passwordRules}
                error={errors.password_confirmation}
            />
        </>
    );
}
