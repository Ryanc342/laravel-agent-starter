import type { Ref } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Label } from '@/components/ui/label';

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
    passwordRef,
}: {
    passwordRules: string;
    errors: { password?: string; password_confirmation?: string };
    label?: string;
    autoFocus?: boolean;
    passwordRef?: Ref<HTMLInputElement>;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="password">{label}</Label>
                <PasswordInput
                    id="password"
                    ref={passwordRef}
                    name="password"
                    required
                    autoFocus={autoFocus}
                    autoComplete="new-password"
                    placeholder={label}
                    passwordrules={passwordRules}
                />
                <InputError message={errors.password} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autoComplete="new-password"
                    placeholder="Confirm password"
                    passwordrules={passwordRules}
                />
                <InputError message={errors.password_confirmation} />
            </div>
        </>
    );
}
